<?php

namespace Tests\Feature;

use App\Models\CommercialCampaign;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Representative;
use App\Models\User;
use App\Services\Pricing\PricingEngine;
use App\Services\Sales\CommercialValidationException;
use App\Services\Sales\OrderService;
use App\Services\Sales\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderClosingValidationTest extends TestCase
{
    use RefreshDatabase;

    protected PricingEngine $pricingEngine;
    protected QuoteService $quoteService;
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingEngine = new PricingEngine();
        $this->quoteService = new QuoteService($this->pricingEngine);
        $this->orderService = new OrderService($this->quoteService, $this->pricingEngine);
    }

    public function test_can_create_quote_and_close_order_successfully(): void
    {
        $rep = Representative::create([
            'name' => 'Carlos Rep',
            'code' => 'REP-01',
            'max_discount_pct' => 20.00,
        ]);

        $company = Company::create([
            'name' => 'Drogaria Central',
            'representative_id' => $rep->id,
            'segment' => 'Farmácia',
        ]);

        $contact = Contact::create([
            'company_id' => $company->id,
            'name' => 'João Silva',
            'phone' => '5511999998888',
        ]);

        $product = Product::create([
            'code' => 'PROD-A',
            'name' => 'Dipirona 500mg',
            'base_price' => 10.00,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        $quote = $this->quoteService->createQuote([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'representative_id' => $rep->id,
        ], [
            ['product_id' => $product->id, 'quantity' => 5],
        ]);

        $this->assertEquals(50.00, $quote->total_amount);
        $this->assertEquals('draft', $quote->status);

        $order = $this->orderService->createFromQuote($quote);

        $this->assertNotNull($order->id);
        $this->assertEquals('confirmed', $order->status);
        $this->assertEquals(50.00, $order->total_amount);
        $this->assertEquals('converted_to_order', $quote->fresh()->status);
        $this->assertEquals(95, $product->fresh()->stock_quantity); // Estoque decrementado
    }

    public function test_closing_fails_when_campaign_expires_between_quote_and_closing(): void
    {
        $product = Product::create([
            'code' => 'PROD-B',
            'name' => 'Amoxicilina 875mg',
            'base_price' => 50.00,
            'stock_quantity' => 100,
            'is_active' => true,
        ]);

        $campaign = CommercialCampaign::create([
            'name' => 'Flash Sale',
            'code' => 'CAMP-FLASH',
            'discount_type' => 'fixed_price',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
            'priority' => 10,
        ]);

        $campaign->products()->create([
            'product_id' => $product->id,
            'special_price' => 30.00,
            'min_quantity' => 1,
        ]);

        // Cria cotação com a campanha ativa (R$ 30,00)
        $quote = $this->quoteService->createQuote([], [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);
        $this->assertEquals(30.00, $quote->total_amount);

        // Força expiração da campanha
        $campaign->update([
            'ends_at' => now()->subMinutes(5),
        ]);

        // Tentativa de fechamento DEVE falhar determinísticamente
        $this->expectException(CommercialValidationException::class);
        $this->orderService->createFromQuote($quote);
    }

    public function test_order_requires_approval_when_discount_exceeds_representative_threshold(): void
    {
        $rep = Representative::create([
            'name' => 'Carlos Rep',
            'code' => 'REP-02',
            'max_discount_pct' => 10.00, // Limite de 10%
        ]);

        $product = Product::create([
            'code' => 'PROD-C',
            'name' => 'Omeprazol 20mg',
            'base_price' => 100.00,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);

        $campaign = CommercialCampaign::create([
            'name' => 'Mega Desconto',
            'code' => 'CAMP-MEGA',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $campaign->products()->create([
            'product_id' => $product->id,
            'special_price' => 70.00, // 30% de desconto! Acima da alçada de 10% do representante
            'min_quantity' => 1,
        ]);

        $quote = $this->quoteService->createQuote([
            'representative_id' => $rep->id,
        ], [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        $order = $this->orderService->createFromQuote($quote);

        $this->assertEquals('pending_approval', $order->status);
        $this->assertTrue($order->requires_approval);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => \App\Models\Order::class,
            'approvable_id' => $order->id,
            'status' => 'pending',
        ]);
    }
}
