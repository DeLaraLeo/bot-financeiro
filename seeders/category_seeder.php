<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use Carbon\Carbon;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            ['code' => 'mercado', 'name' => 'Mercado'],
            ['code' => 'farmacia', 'name' => 'Farmácia'],
            ['code' => 'combustivel', 'name' => 'Combustível'],
            ['code' => 'restaurante', 'name' => 'Restaurante'],
            ['code' => 'transporte', 'name' => 'Transporte'],
            ['code' => 'saude', 'name' => 'Saúde'],
            ['code' => 'lazer', 'name' => 'Lazer'],
            ['code' => 'moradia', 'name' => 'Moradia'],
            ['code' => 'educacao', 'name' => 'Educação'],
            ['code' => 'assinaturas', 'name' => 'Assinaturas'],
            ['code' => 'outros', 'name' => 'Outros'],
        ];

        foreach ($categories as $category) {
            \Hyperf\DbConnection\Db::table('categories')->insert([
                'code' => $category['code'],
                'name' => $category['name'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
