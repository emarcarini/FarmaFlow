<?php

namespace App\Services\Pricing;

use App\Models\CommercialCampaign;
use App\Models\Product;
use App\Models\ProductPrice;

class PriceResolutionResult
{
    public function __construct(
        public readonly Product $product,
        public readonly int $quantity,
        public readonly float $baseUnitPrice,
        public readonly float $finalUnitPrice,
        public readonly float $totalAmount,
        public readonly float $discountAmount,
        public readonly float $discountPercentage,
        public readonly string $conditionSource, // 'base_price', 'tier_price', 'campaign'
        public readonly ?CommercialCampaign $appliedCampaign = null,
        public readonly ?ProductPrice $appliedTier = null,
        public readonly bool $isAvailable = true,
        public readonly ?string $unavailableReason = null,
        public readonly array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'product_code' => $this->product->code,
            'product_name' => $this->product->name,
            'quantity' => $this->quantity,
            'base_unit_price' => $this->baseUnitPrice,
            'final_unit_price' => $this->finalUnitPrice,
            'total_amount' => $this->totalAmount,
            'discount_amount' => $this->discountAmount,
            'discount_percentage' => $this->discountPercentage,
            'condition_source' => $this->conditionSource,
            'campaign_id' => $this->appliedCampaign?->id,
            'campaign_name' => $this->appliedCampaign?->name,
            'product_price_id' => $this->appliedTier?->id,
            'is_available' => $this->isAvailable,
            'unavailable_reason' => $this->unavailableReason,
            'metadata' => $this->metadata,
        ];
    }
}
