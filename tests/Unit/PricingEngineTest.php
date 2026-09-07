<?php

namespace Tests\Unit;

use App\Models\CommercialCampaign;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\Pricing\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected PricingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PricingEngine();
    }

    public function test_resolves_base_price_when_no_tier_or_campaign_applies(): void
    {
        $product = Product::create([
            'code' => 'TEST-001',
            'name' => 'Dipirona 500mg',
            'base_price' => 20.00,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        $result = $this->engine->resolvePrice($product, 5);

        $this->assertEquals(20.00, $result->finalUnitPrice);
        $this->assertEquals(100.00, $result->totalAmount);
        $this->assertEquals(0.00, $result->discountAmount);
        $this->assertEquals('base_price', $result->conditionSource);
        $this->assertTrue($result->isAvailable);
    }

    public function test_resolves_quantity_tier_pricing(): void
    {
        $product = Product::create([
            'code' => 'TEST-002',
            'name' => 'Amoxicilina 875mg',
            'base_price' => 50.00,
            'stock_quantity' => 500,
            'is_active' => true,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'min_quantity' => 10,
            'max_quantity' => 29,
            'unit_price' => 42.00,
            'priority' => 1,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'min_quantity' => 30,
            'max_quantity' => null,
            'unit_price' => 35.00,
            'priority' => 2,
        ]);

        // Quantidade 15 deve cair na faixa de 10-29 (R$ 42,00)
        $res15 = $this->engine->resolvePrice($product, 15);
        $this->assertEquals(42.00, $res15->finalUnitPrice);
        $this->assertEquals(630.00, $res15->totalAmount);
        $this->assertEquals('tier_price', $res15->conditionSource);

        // Quantidade 50 deve cair na faixa de 30+ (R$ 35,00)
        $res50 = $this->engine->resolvePrice($product, 50);
        $this->assertEquals(35.00, $res50->finalUnitPrice);
        $this->assertEquals(1750.00, $res50->totalAmount);
        $this->assertEquals('tier_price', $res50->conditionSource);
    }

    public function test_campaign_overrides_tier_when_better_or_higher_priority(): void
    {
        $product = Product::create([
            'code' => 'TEST-003',
            'name' => 'Paracetamol 750mg',
            'base_price' => 15.00,
            'stock_quantity' => 500,
            'is_active' => true,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'unit_price' => 12.00,
            'priority' => 1,
        ]);

        $campaign = CommercialCampaign::create([
            'name' => 'Campanha Especial',
            'code' => 'CAMP-SPEC-01',
            'discount_type' => 'fixed_price',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(5),
            'is_active' => true,
            'priority' => 10,
        ]);

        $campaign->products()->create([
            'product_id' => $product->id,
            'special_price' => 9.50,
            'min_quantity' => 50,
            'max_quantity' => null,
        ]);

        $res = $this->engine->resolvePrice($product, 50);
        $this->assertEquals(9.50, $res->finalUnitPrice);
        $this->assertEquals(475.00, $res->totalAmount);
        $this->assertEquals('campaign', $res->conditionSource);
        $this->assertEquals($campaign->id, $res->appliedCampaign->id);
    }

    public function test_expired_campaign_is_ignored(): void
    {
        $product = Product::create([
            'code' => 'TEST-004',
            'name' => 'Omeprazol 20mg',
            'base_price' => 25.00,
            'stock_quantity' => 500,
            'is_active' => true,
        ]);

        $campaign = CommercialCampaign::create([
            'name' => 'Campanha Vencida',
            'code' => 'CAMP-EXP-01',
            'discount_type' => 'fixed_price',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(), // Venceu ontem!
            'is_active' => true,
            'priority' => 10,
        ]);

        $campaign->products()->create([
            'product_id' => $product->id,
            'special_price' => 10.00,
            'min_quantity' => 1,
            'max_quantity' => null,
        ]);

        $res = $this->engine->resolvePrice($product, 10);
        // Não deve aplicar os R$ 10,00 da campanha vencida, e sim o base_price R$ 25,00
        $this->assertEquals(25.00, $res->finalUnitPrice);
        $this->assertEquals('base_price', $res->conditionSource);
    }

    public function test_ineligible_customer_does_not_receive_restricted_campaign(): void
    {
        $product = Product::create([
            'code' => 'TEST-005',
            'name' => 'Losartana 50mg',
            'base_price' => 30.00,
            'stock_quantity' => 500,
            'is_active' => true,
        ]);

        $campaign = CommercialCampaign::create([
            'name' => 'Exclusivo Hospitalar',
            'code' => 'CAMP-HOSP-01',
            'discount_type' => 'fixed_price',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(10),
            'is_active' => true,
            'priority' => 10,
        ]);

        $campaign->products()->create([
            'product_id' => $product->id,
            'special_price' => 18.00,
            'min_quantity' => 1,
            'max_quantity' => null,
        ]);

        $campaign->audiences()->create([
            'segment' => 'Hospitalar',
        ]);

        $farmaCompany = Company::create([
            'name' => 'Drogaria Teste',
            'segment' => 'Farmácia',
        ]);

        $resFarma = $this->engine->resolvePrice($product, 5, $farmaCompany);
        $this->assertEquals(30.00, $resFarma->finalUnitPrice);
        $this->assertEquals('base_price', $resFarma->conditionSource);

        $hospCompany = Company::create([
            'name' => 'Hospital Teste',
            'segment' => 'Hospitalar',
        ]);

        $resHosp = $this->engine->resolvePrice($product, 5, $hospCompany);
        $this->assertEquals(18.00, $resHosp->finalUnitPrice);
        $this->assertEquals('campaign', $resHosp->conditionSource);
    }
}
