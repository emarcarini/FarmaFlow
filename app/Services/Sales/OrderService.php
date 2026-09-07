<?php

namespace App\Services\Sales;

use App\Models\Approval;
use App\Models\CustomerProductHistory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quote;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Pricing\PricingEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        protected QuoteService $quoteService,
        protected PricingEngine $pricingEngine
    ) {}

    /**
     * Converter uma Cotação em Pedido com TRAVA DETERMINÍSTICA OBRIGATÓRIA.
     *
     * @throws CommercialValidationException
     */
    public function createFromQuote(Quote $quote, array $options = []): Order
    {
        // 1. TRAVA DETERMINÍSTICA: Revalidar rigorosamente a cotação
        $validation = $this->quoteService->revalidateQuote($quote);

        if (!$validation['is_valid']) {
            throw new CommercialValidationException(
                message: "Não foi possível fechar o pedido: as condições comerciais ou produtos da cotação foram alterados ou expiraram.",
                validationErrors: $validation['errors']
            );
        }

        return DB::transaction(function () use ($quote, $options, $validation) {
            $quote->load('items.product', 'company', 'contact', 'representative');

            $orderNumber = 'PED-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
            $representative = $quote->representative;

            // Verificar se alguma condição requer aprovação de alçada
            $requiresApproval = false;
            $approvalReason = null;

            // Alçada 1: Desconto geral excede o limite do representante
            $overallDiscountPct = $quote->subtotal > 0
                ? (($quote->discount_total / $quote->subtotal) * 100)
                : 0.0;

            if ($representative && $overallDiscountPct > (float) $representative->max_discount_pct) {
                $requiresApproval = true;
                $approvalReason = "Desconto aplicado ({$overallDiscountPct}%) excede a alçada permitida de {$representative->max_discount_pct}%.";
            }

            $orderStatus = $requiresApproval ? 'pending_approval' : 'confirmed';

            $order = Order::create([
                'order_number' => $orderNumber,
                'quote_id' => $quote->id,
                'company_id' => $quote->company_id,
                'contact_id' => $quote->contact_id,
                'representative_id' => $quote->representative_id,
                'status' => $orderStatus,
                'requires_approval' => $requiresApproval,
                'subtotal' => $quote->subtotal,
                'discount_total' => $quote->discount_total,
                'total_amount' => $quote->total_amount,
                'payment_terms' => $options['payment_terms'] ?? $quote->payment_terms,
                'notes' => $options['notes'] ?? $quote->notes,
                'validated_at' => now(),
                'validation_snapshot' => [
                    'revalidation_result' => $validation,
                    'quote_number' => $quote->quote_number,
                    'created_via' => $options['created_via'] ?? 'portal',
                ],
            ]);

            // Se necessitar de aprovação, criar solicitação de aprovação
            if ($requiresApproval) {
                Approval::create([
                    'approvable_type' => Order::class,
                    'approvable_id' => $order->id,
                    'requested_by' => auth()->id(),
                    'status' => 'pending',
                    'reason' => $approvalReason,
                    'requested_at' => now(),
                ]);
            }

            // Criar itens do pedido e atualizar histórico/estoque
            foreach ($quote->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_pct' => $item->discount_pct,
                    'total_price' => $item->total_price,
                    'condition_source' => $item->condition_source,
                    'commercial_campaign_id' => $item->commercial_campaign_id,
                    'product_price_id' => $item->product_price_id,
                ]);

                // Atualizar estoque se não for backorder
                if ($item->product && $item->product->stock_quantity >= $item->quantity) {
                    $item->product->decrement('stock_quantity', $item->quantity);
                }

                // Atualizar histórico de compras do cliente para inteligência de reposição
                $history = CustomerProductHistory::firstOrNew([
                    'company_id' => $quote->company_id,
                    'contact_id' => $quote->contact_id,
                    'product_id' => $item->product_id,
                ]);

                $history->last_order_id = $order->id;
                $history->last_purchased_at = now();
                $history->total_quantity_purchased = ($history->total_quantity_purchased ?? 0) + $item->quantity;
                $history->total_spent = ($history->total_spent ?? 0.0) + (float) $item->total_price;
                
                // Estimativa de reposição padrão: 30 dias
                $history->next_estimated_purchase_at = now()->addDays(30);
                $history->save();
            }

            // Atualizar status da cotação
            $quote->update(['status' => 'converted_to_order']);

            AuditLogService::log(
                action: 'order.created',
                auditable: $order,
                oldValues: null,
                newValues: [
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'requires_approval' => $requiresApproval,
                ],
                actorType: $options['actor_type'] ?? 'user'
            );

            return $order->load('items.product');
        });
    }

    /**
     * Aprovar um pedido pendente.
     */
    public function approveOrder(Order $order, User $approver, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($order, $approver, $notes) {
            $order->update([
                'status' => 'confirmed',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            $order->approvals()->where('status', 'pending')->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'reviewer_notes' => $notes,
                'reviewed_at' => now(),
            ]);

            AuditLogService::log(
                action: 'order.approved',
                auditable: $order,
                oldValues: ['status' => 'pending_approval'],
                newValues: ['status' => 'confirmed', 'approver' => $approver->name],
                actorType: 'user',
                userId: $approver->id
            );

            return $order;
        });
    }
}
