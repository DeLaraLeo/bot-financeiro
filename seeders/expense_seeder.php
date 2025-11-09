<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use Carbon\Carbon;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = \Hyperf\DbConnection\Db::table('categories')->get();
        if ($categories->isEmpty()) {
            echo "❌ No categories found. Please run category seeder first.\n";
            return;
        }

        $userId = \Hyperf\DbConnection\Db::table('users')->insertGetId([
            'phone_e164' => '554288872501',
            'name' => 'Leonardo',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        
        echo "✅ User created (ID: {$userId}, Name: Leonardo, Phone: 554288872501)\n";

        $categoryIds = $categories->pluck('id')->toArray();
        $descriptions = [
            'mercado' => [
                'Compra no supermercado', 'Feira da semana', 'Produtos de limpeza',
                'Alimentos básicos', 'Carnes e verduras', 'Produtos de higiene'
            ],
            'farmacia' => [
                'Medicamentos', 'Vitamina C', 'Remédio para dor de cabeça',
                'Produtos de higiene', 'Protetor solar', 'Suplementos'
            ],
            'combustivel' => [
                'Gasolina', 'Abastecimento', 'Combustível do carro',
                'Posto de gasolina', 'Etanol', 'Diesel'
            ],
            'restaurante' => [
                'Almoço no restaurante', 'Jantar fora', 'Delivery de comida',
                'Lanche da tarde', 'Café da manhã', 'Pizza delivery'
            ],
            'transporte' => [
                'Uber', 'Taxi', 'Ônibus', 'Metrô', 'Passagem aérea',
                'Trem', 'Bilhete único', 'Corrida de aplicativo'
            ],
            'saude' => [
                'Consulta médica', 'Exame de sangue', 'Fisioterapia',
                'Dentista', 'Plano de saúde', 'Medicamentos'
            ],
            'lazer' => [
                'Cinema', 'Show', 'Teatro', 'Parque de diversões',
                'Viagem', 'Hobby', 'Jogos', 'Livros'
            ],
            'moradia' => [
                'Aluguel', 'Condomínio', 'Conta de luz', 'Conta de água',
                'Internet', 'Gás', 'Manutenção', 'Reforma'
            ],
            'educacao' => [
                'Curso online', 'Livros didáticos', 'Material escolar',
                'Faculdade', 'Curso de idiomas', 'Workshop'
            ],
            'assinaturas' => [
                'Netflix', 'Spotify', 'Amazon Prime', 'Gym',
                'Revista digital', 'Software', 'Cloud storage'
            ],
            'outros' => [
                'Gasto diverso', 'Emergência', 'Presente', 'Doação',
                'Taxa bancária', 'Multa', 'Seguro', 'Investimento'
            ]
        ];

        $expenses = [];
        $now = Carbon::now();

        for ($i = 0; $i < 500; $i++) {
            $categoryId = $categoryIds[array_rand($categoryIds)];
            $category = $categories->firstWhere('id', $categoryId);
            $categoryCode = $category->code;

            $amount = rand(500, 50000);

            $categoryDescriptions = $descriptions[$categoryCode] ?? $descriptions['outros'];
            $description = $categoryDescriptions[array_rand($categoryDescriptions)];
            $randomDays = rand(0, 180);
            $occurredAt = $now->copy()->subDays($randomDays);

            $expenses[] = [
                'user_id' => $userId,
                'amount_cents' => $amount,
                'currency' => 'BRL',
                'category_id' => $categoryId,
                'description' => $description,
                'occurred_at' => $occurredAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($expenses) >= 50) {
                \Hyperf\DbConnection\Db::table('expenses')->insert($expenses);
                $expenses = [];
                echo "✅ Inserted 50 expenses\n";
            }
        }

        if (!empty($expenses)) {
            \Hyperf\DbConnection\Db::table('expenses')->insert($expenses);
            echo "✅ Inserted " . count($expenses) . " remaining expenses\n";
        }

        echo "🎉 Successfully created 500 random expenses!\n";
    }
}
