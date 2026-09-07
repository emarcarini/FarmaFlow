<?php

namespace Database\Seeders;

use App\Models\CommercialCampaign;
use App\Models\Company;
use App\Models\ConsentPreference;
use App\Models\Contact;
use App\Models\CustomerTag;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Representative;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuários e Perfis
        $adminUser = User::create([
            'name' => 'Administrador do Sistema',
            'email' => 'admin@comercial.com.br',
            'password' => Hash::make('senha123'),
            'role' => 'admin',
            'phone' => '5511999990000',
            'is_active' => true,
        ]);

        $repUser = User::create([
            'name' => 'Carlos Silva',
            'email' => 'carlos@comercial.com.br',
            'password' => Hash::make('senha123'),
            'role' => 'representative',
            'phone' => '5511988887777',
            'is_active' => true,
        ]);

        $representative = Representative::create([
            'user_id' => $repUser->id,
            'name' => 'Carlos Silva',
            'email' => 'carlos@comercial.com.br',
            'phone' => '5511988887777',
            'code' => 'REP-01',
            'commission_rate' => 5.00,
            'max_discount_pct' => 15.00,
            'is_active' => true,
            'settings' => [
                'bot_name' => 'Assistente do Carlos',
                'greeting' => 'Opa, tudo bem? Sou o assistente comercial do Carlos.',
                'allow_ai_quotes' => true,
                'notify_on_handover' => true,
            ],
        ]);

        // 2. Tags Comerciais
        $tags = [
            'Farmácia' => '#3b82f6',
            'Hospitalar' => '#10b981',
            'Clínica' => '#8b5cf6',
            'VIP' => '#f59e0b',
            'Distribuidora' => '#ec4899',
        ];

        $tagModels = [];
        foreach ($tags as $name => $color) {
            $tagModels[$name] = CustomerTag::create([
                'name' => $name,
                'slug' => strtolower($name),
                'color' => $color,
            ]);
        }

        // 3. Empresas e Contatos
        $saoBento = Company::create([
            'representative_id' => $representative->id,
            'name' => 'Drogarias São Bento Ltda',
            'trade_name' => 'Rede São Bento',
            'document' => '12.345.678/0001-90',
            'email' => 'compras@saobento.com.br',
            'phone' => '551133334444',
            'address' => 'Av. Paulista, 1500',
            'city' => 'São Paulo',
            'state' => 'SP',
            'postal_code' => '01310-200',
            'segment' => 'Farmácia',
            'classification' => 'A',
            'status' => 'active',
            'notes' => 'Rede com 12 lojas, bom pagador, prefere entregas nas terças-feiras.',
        ]);
        $saoBento->tags()->attach([$tagModels['Farmácia']->id, $tagModels['VIP']->id]);

        $contatoCelso = Contact::create([
            'company_id' => $saoBento->id,
            'representative_id' => $representative->id,
            'name' => 'Celso Portiolli',
            'role_position' => 'Gerente de Compras',
            'phone' => '5511999991111',
            'email' => 'celso@saobento.com.br',
            'is_primary' => true,
            'notes' => 'Gosta de atendimento ágil e direto.',
        ]);
        ConsentPreference::create([
            'contact_id' => $contatoCelso->id,
            'company_id' => $saoBento->id,
            'channel' => 'whatsapp',
            'is_opted_out' => false,
        ]);

        $santaClara = Company::create([
            'representative_id' => $representative->id,
            'name' => 'Hospital Santa Clara S/A',
            'trade_name' => 'Hospital Santa Clara',
            'document' => '98.765.432/0001-10',
            'email' => 'suprimentos@santaclara.org.br',
            'phone' => '551134567890',
            'address' => 'Rua das Palmeiras, 500',
            'city' => 'Campinas',
            'state' => 'SP',
            'postal_code' => '13010-100',
            'segment' => 'Hospitalar',
            'classification' => 'A',
            'status' => 'active',
            'notes' => 'Grande volume mensal em antibióticos e analgésicos.',
        ]);
        $santaClara->tags()->attach([$tagModels['Hospitalar']->id, $tagModels['VIP']->id]);

        $contatoMariana = Contact::create([
            'company_id' => $santaClara->id,
            'representative_id' => $representative->id,
            'name' => 'Dra. Mariana Ramos',
            'role_position' => 'Coordenadora de Suprimentos',
            'phone' => '5511988882222',
            'email' => 'mariana.ramos@santaclara.org.br',
            'is_primary' => true,
        ]);
        ConsentPreference::create([
            'contact_id' => $contatoMariana->id,
            'company_id' => $santaClara->id,
            'channel' => 'whatsapp',
            'is_opted_out' => false,
        ]);

        $clinicaVida = Company::create([
            'representative_id' => $representative->id,
            'name' => 'Clínica Médica Vida & Saúde Ltda',
            'trade_name' => 'Clínica Vida',
            'document' => '45.123.789/0001-55',
            'email' => 'financeiro@clinicavida.med.br',
            'phone' => '551132221111',
            'address' => 'Rua Oscar Freire, 800',
            'city' => 'São Paulo',
            'state' => 'SP',
            'postal_code' => '01426-001',
            'segment' => 'Clínica',
            'classification' => 'B',
            'status' => 'at_risk',
            'notes' => 'Sem compras há mais de 45 dias. Costumava comprar Paracetamol e Dipirona.',
        ]);
        $clinicaVida->tags()->attach([$tagModels['Clínica']->id]);

        $contatoRoberto = Contact::create([
            'company_id' => $clinicaVida->id,
            'representative_id' => $representative->id,
            'name' => 'Roberto Alencar',
            'role_position' => 'Administrador',
            'phone' => '5511977773333',
            'email' => 'roberto@clinicavida.med.br',
            'is_primary' => true,
        ]);
        ConsentPreference::create([
            'contact_id' => $contatoRoberto->id,
            'company_id' => $clinicaVida->id,
            'channel' => 'whatsapp',
            'is_opted_out' => false,
        ]);

        // 4. Catálogo de Produtos e Faixas de Preço Determinísticas
        $p1 = Product::create([
            'code' => 'PROD-001',
            'sku' => 'MED-DIP-500',
            'name' => 'Dipirona Monoidratada 500mg',
            'presentation' => 'Caixa com 20 comprimidos',
            'description' => 'Analgésico e antipirético indicado para dores e febre.',
            'unit' => 'cx',
            'base_price' => 12.00,
            'stock_quantity' => 2500,
            'is_active' => true,
            'category' => 'Analgésicos',
        ]);
        ProductPrice::create([
            'product_id' => $p1->id,
            'min_quantity' => 1,
            'max_quantity' => 9,
            'unit_price' => 12.00,
            'discount_pct' => 0.00,
            'priority' => 1,
        ]);
        ProductPrice::create([
            'product_id' => $p1->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'unit_price' => 10.50,
            'discount_pct' => 12.50,
            'priority' => 2,
        ]);
        ProductPrice::create([
            'product_id' => $p1->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'unit_price' => 8.90,
            'discount_pct' => 25.83,
            'priority' => 3,
        ]);

        $p2 = Product::create([
            'code' => 'PROD-002',
            'sku' => 'MED-AMX-875',
            'name' => 'Amoxicilina + Clavulanato 875mg',
            'presentation' => 'Caixa com 14 comprimidos revestidos',
            'description' => 'Antibiótico de amplo espectro para infecções bacterianas.',
            'unit' => 'cx',
            'base_price' => 48.00,
            'stock_quantity' => 800,
            'is_active' => true,
            'category' => 'Antibióticos',
        ]);
        ProductPrice::create([
            'product_id' => $p2->id,
            'min_quantity' => 1,
            'max_quantity' => 9,
            'unit_price' => 48.00,
            'discount_pct' => 0.00,
            'priority' => 1,
        ]);
        ProductPrice::create([
            'product_id' => $p2->id,
            'min_quantity' => 10,
            'max_quantity' => 29,
            'unit_price' => 42.00,
            'discount_pct' => 12.50,
            'priority' => 2,
        ]);
        ProductPrice::create([
            'product_id' => $p2->id,
            'min_quantity' => 30,
            'max_quantity' => null,
            'unit_price' => 36.00,
            'discount_pct' => 25.00,
            'priority' => 3,
        ]);

        $p3 = Product::create([
            'code' => 'PROD-003',
            'sku' => 'MED-OMP-020',
            'name' => 'Omeprazol 20mg',
            'presentation' => 'Frasco com 28 cápsulas',
            'description' => 'Inibidor da bomba de prótons para tratamento de refluxo e gastrite.',
            'unit' => 'fr',
            'base_price' => 22.00,
            'stock_quantity' => 1200,
            'is_active' => true,
            'category' => 'Gastroenterologia',
        ]);
        ProductPrice::create([
            'product_id' => $p3->id,
            'min_quantity' => 1,
            'max_quantity' => 19,
            'unit_price' => 22.00,
            'priority' => 1,
        ]);
        ProductPrice::create([
            'product_id' => $p3->id,
            'min_quantity' => 20,
            'max_quantity' => 99,
            'unit_price' => 18.50,
            'discount_pct' => 15.90,
            'priority' => 2,
        ]);
        ProductPrice::create([
            'product_id' => $p3->id,
            'min_quantity' => 100,
            'max_quantity' => null,
            'unit_price' => 15.00,
            'discount_pct' => 31.81,
            'priority' => 3,
        ]);

        $p4 = Product::create([
            'code' => 'PROD-004',
            'sku' => 'MED-LOS-050',
            'name' => 'Losartana Potássica 50mg',
            'presentation' => 'Caixa com 30 comprimidos',
            'description' => 'Anti-hipertensivo indicado para controle da pressão arterial.',
            'unit' => 'cx',
            'base_price' => 16.00,
            'stock_quantity' => 3000,
            'is_active' => true,
            'category' => 'Cardiologia',
        ]);
        ProductPrice::create([
            'product_id' => $p4->id,
            'min_quantity' => 1,
            'max_quantity' => 14,
            'unit_price' => 16.00,
            'priority' => 1,
        ]);
        ProductPrice::create([
            'product_id' => $p4->id,
            'min_quantity' => 15,
            'max_quantity' => 49,
            'unit_price' => 13.50,
            'discount_pct' => 15.62,
            'priority' => 2,
        ]);
        ProductPrice::create([
            'product_id' => $p4->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'unit_price' => 11.00,
            'discount_pct' => 31.25,
            'priority' => 3,
        ]);

        $p5 = Product::create([
            'code' => 'PROD-005',
            'sku' => 'MED-PAR-750',
            'name' => 'Paracetamol 750mg',
            'presentation' => 'Caixa com 20 comprimidos',
            'description' => 'Analgésico e antitérmico para alívio sintomático de dores leves a moderadas.',
            'unit' => 'cx',
            'base_price' => 14.00,
            'stock_quantity' => 1800,
            'is_active' => true,
            'category' => 'Analgésicos',
        ]);
        ProductPrice::create([
            'product_id' => $p5->id,
            'min_quantity' => 1,
            'max_quantity' => 9,
            'unit_price' => 14.00,
            'priority' => 1,
        ]);
        ProductPrice::create([
            'product_id' => $p5->id,
            'min_quantity' => 10,
            'max_quantity' => 49,
            'unit_price' => 12.00,
            'discount_pct' => 14.28,
            'priority' => 2,
        ]);
        ProductPrice::create([
            'product_id' => $p5->id,
            'min_quantity' => 50,
            'max_quantity' => null,
            'unit_price' => 9.50,
            'discount_pct' => 32.14,
            'priority' => 3,
        ]);

        // 5. Campanhas Comerciais Vigentes
        $campanhaInverno = CommercialCampaign::create([
            'name' => 'Super Campanha de Inverno 2026',
            'code' => 'CAMP-INVERNO-2026',
            'description' => 'Condição especial para analgésicos em compras de volume acima de 100 unidades.',
            'discount_type' => 'fixed_price',
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(30),
            'is_active' => true,
            'priority' => 50,
        ]);

        $campanhaInverno->products()->create([
            'product_id' => $p1->id,
            'special_price' => 7.90, // Dipirona a 7,90 para 100+
            'min_quantity' => 100,
            'max_quantity' => null,
        ]);

        $campanhaInverno->products()->create([
            'product_id' => $p5->id,
            'special_price' => 8.50, // Paracetamol a 8,50 para 80+
            'min_quantity' => 80,
            'max_quantity' => null,
        ]);
    }
}
