<?php

namespace App\Services\Pricing;

use App\Models\CommercialCampaign;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\Audit\AuditLogService;

class PricingEngine
{
    /**
     * Resolução de preço determinística conforme regras comerciais e faixas de quantidade.
     */
    public function resolvePrice(
        Product $product,
        int $quantity = 1,
        ?Company $company = null,
        ?Contact $contact = null,
        bool $logAudit = false
    ): PriceResolutionResult {
        if ($quantity <= 0) {
            $quantity = 1;
        }

        // 1. Validar disponibilidade do produto
        if (!$product->is_active) {
            return new PriceResolutionResult(
                product: $product,
                quantity: $quantity,
                baseUnitPrice: (float) $product->base_price,
                finalUnitPrice: (float) $product->base_price,
                totalAmount: (float) ($product->base_price * $quantity),
                discountAmount: 0.00,
                discountPercentage: 0.00,
                conditionSource: 'base_price',
                isAvailable: false,
                unavailableReason: 'Produto inativo no catálogo.'
            );
        }

        $basePrice = (float) $product->base_price;
        $bestUnitPrice = $basePrice;
        $selectedSource = 'base_price';
        $selectedCampaign = null;
        $selectedTier = null;

        // 2. Verificar faixa de preço por quantidade (Tabela escalonada)
        $matchingTier = $product->activePrices()
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->whereNull('max_quantity')
                    ->orWhere('max_quantity', '>=', $quantity);
            })
            ->where(function ($query) {
                $today = now()->format('Y-m-d');
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', $today);
            })
            ->where(function ($query) {
                $today = now()->format('Y-m-d');
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', $today);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('min_quantity', 'desc')
            ->first();

        if ($matchingTier) {
            $tierPrice = (float) $matchingTier->unit_price;
            if ($tierPrice < $bestUnitPrice) {
                $bestUnitPrice = $tierPrice;
                $selectedSource = 'tier_price';
                $selectedTier = $matchingTier;
            }
        }

        // 3. Verificar Campanhas Comerciais Vigentes
        $now = now();
        $activeCampaigns = CommercialCampaign::where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($activeCampaigns as $campaign) {
            if (!$campaign->isEligibleForCustomer($company, $contact)) {
                continue;
            }

            $campaignProduct = $campaign->products()
                ->where('product_id', $product->id)
                ->where('min_quantity', '<=', $quantity)
                ->where(function ($query) use ($quantity) {
                    $query->whereNull('max_quantity')
                        ->orWhere('max_quantity', '>=', $quantity);
                })
                ->first();

            if ($campaignProduct) {
                $candidatePrice = null;

                if ($campaignProduct->special_price !== null) {
                    $candidatePrice = (float) $campaignProduct->special_price;
                } elseif ($campaignProduct->discount_pct !== null && $campaignProduct->discount_pct > 0) {
                    $candidatePrice = $basePrice * (1 - ((float) $campaignProduct->discount_pct / 100));
                }

                if ($candidatePrice !== null && $candidatePrice < $bestUnitPrice) {
                    $bestUnitPrice = $candidatePrice;
                    $selectedSource = 'campaign';
                    $selectedCampaign = $campaign;
                    $selectedTier = null; // Campanha tem precedência
                }
            }
        }

        $totalAmount = round($bestUnitPrice * $quantity, 2);
        $totalBase = round($basePrice * $quantity, 2);
        $discountAmount = max(0.0, round($totalBase - $totalAmount, 2));
        $discountPercentage = $basePrice > 0 ? round((($basePrice - $bestUnitPrice) / $basePrice) * 100, 2) : 0.0;

        $result = new PriceResolutionResult(
            product: $product,
            quantity: $quantity,
            baseUnitPrice: $basePrice,
            finalUnitPrice: round($bestUnitPrice, 2),
            totalAmount: $totalAmount,
            discountAmount: $discountAmount,
            discountPercentage: $discountPercentage,
            conditionSource: $selectedSource,
            appliedCampaign: $selectedCampaign,
            appliedTier: $selectedTier,
            isAvailable: true,
            metadata: [
                'resolved_at' => now()->toIso8601String(),
                'company_id' => $company?->id,
                'contact_id' => $contact?->id,
            ]
        );

        if ($logAudit) {
            AuditLogService::log(
                action: 'pricing.resolved',
                auditable: $product,
                oldValues: null,
                newValues: $result->toArray(),
                actorType: 'system',
                actorName: 'PricingEngine'
            );
        }

        return $result;
    }
}
