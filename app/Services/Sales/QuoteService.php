<?php

namespace App\Services\Sales;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Services\Audit\AuditLogService;
use App\Services\Pricing\PricingEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteService
{
    public function __construct(
        protected PricingEngine $pricingEngine
    ) {}

    /**
     * Criar uma nova cotação com cálculo determinístico de cada item.
     */
    public function createQuote(array $data, array $items): Quote
    {
        return DB::transaction(function () use ($data, $items) {
            $company = isset($data['company_id']) ? Company::find($data['company_id']) : null;
            $contact = isset($data['contact_id']) ? Contact::find($data['contact_id']) : null;
            $repId = $data['representative_id'] ?? $company?->representative_id ?? $contact?->representative_id;

            $subtotal = 0.0;
            $discountTotal = 0.0;
            $totalAmount = 0.0;
            $processedItems = [];

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = (int) ($item['quantity'] ?? 1);
                $product = Product::findOrFail($productId);

                $priceResult = $this->pricingEngine->resolvePrice(
                    product: $product,
                    quantity: $quantity,
                    company: $company,
                    contact: $contact
                );

                $subtotal += ($priceResult->baseUnitPrice * $quantity);
                $discountTotal += $priceResult->discountAmount;
                $totalAmount += $priceResult->totalAmount;

                $processedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price_result' => $priceResult,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $quoteNumber = 'COT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

            $quote = Quote::create([
                'quote_number' => $quoteNumber,
                'company_id' => $company?->id,
                'contact_id' => $contact?->id,
                'representative_id' => $repId,
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'subtotal' => round($subtotal, 2),
                'discount_total' => round($discountTotal, 2),
                'total_amount' => round($totalAmount, 2),
                'payment_terms' => $data['payment_terms'] ?? 'À vista / 30 dias',
                'notes' => $data['notes'] ?? null,
                'expires_at' => $data['expires_at'] ?? now()->addDays(7),
                'revalidated_at' => now(),
                'snapshot_data' => [
                    'items_count' => count($processedItems),
                    'created_source' => $data['created_source'] ?? 'portal',
                ],
            ]);

            foreach ($processedItems as $itemData) {
                /** @var \App\Services\Pricing\PriceResolutionResult $res */
                $res = $itemData['price_result'];

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $itemData['product']->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $res->finalUnitPrice,
                    'discount_pct' => $res->discountPercentage,
                    'total_price' => $res->totalAmount,
                    'condition_source' => $res->conditionSource,
                    'commercial_campaign_id' => $res->appliedCampaign?->id,
                    'product_price_id' => $res->appliedTier?->id,
                    'notes' => $itemData['notes'],
                ]);
            }

            AuditLogService::log(
                action: 'quote.created',
                auditable: $quote,
                oldValues: null,
                newValues: [
                    'quote_number' => $quote->quote_number,
                    'total_amount' => $quote->total_amount,
                    'items_count' => count($processedItems),
                ],
                actorType: $data['actor_type'] ?? 'user'
            );

            return $quote->load('items.product');
        });
    }

    /**
     * Revalidação rigorosa de cotação contra preços, campanhas e estoque atuais.
     */
    public function revalidateQuote(Quote $quote): array
    {
        $quote->load('items.product', 'company', 'contact');

        $isValid = true;
        $errors = [];
        $changes = [];

        if ($quote->isExpired()) {
            $isValid = false;
            $errors[] = "A cotação {$quote->quote_number} expirou em {$quote->expires_at->format('d/m/Y H:i')}.";
        }

        foreach ($quote->items as $item) {
            $product = $item->product;

            if (!$product || !$product->is_active) {
                $isValid = false;
                $errors[] = "O produto '{$product?->name}' está indisponível ou inativo.";
                continue;
            }

            $currentPrice = $this->pricingEngine->resolvePrice(
                product: $product,
                quantity: $item->quantity,
                company: $quote->company,
                contact: $quote->contact
            );

            // Comparar preço unitário
            if (abs((float) $item->unit_price - $currentPrice->finalUnitPrice) > 0.009) {
                $isValid = false;
                $diffMsg = "Preço do produto '{$product->name}' mudou de R$ {$item->unit_price} para R$ {$currentPrice->finalUnitPrice}.";
                $errors[] = $diffMsg;
                $changes[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'old_unit_price' => (float) $item->unit_price,
                    'new_unit_price' => $currentPrice->finalUnitPrice,
                    'old_source' => $item->condition_source,
                    'new_source' => $currentPrice->conditionSource,
                ];
            }
        }

        $quote->update(['revalidated_at' => now()]);

        return [
            'is_valid' => $isValid,
            'errors' => $errors,
            'changes' => $changes,
            'revalidated_at' => now()->toIso8601String(),
        ];
    }
}
