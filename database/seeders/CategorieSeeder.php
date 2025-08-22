<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Pains', 'description' => 'Pains frais et artisanaux'],
            ['nom' => 'Viennoiseries', 'description' => 'Croissants, pains au chocolat et autres viennoiseries'],
            ['nom' => 'Pâtisseries', 'description' => 'Gâteaux et desserts'],
            ['nom' => 'Sandwichs', 'description' => 'Sandwichs frais'],
            ['nom' => 'Boissons', 'description' => 'Boissons chaudes et froides'],
        ];

        foreach ($categories as $categorie) {
            Categorie::create($categorie);
        }
    }
}