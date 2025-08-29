<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProduitSeeder extends Seeder
{
    public function run(): void
    {
        $produits = [
            // PAINS TRADITIONNELS
            [
                'nom' => 'Baguette Tradition',
                'description' => 'Baguette croustillante à la farine de tradition française',
                'prix' => 1.20,
                'categorie_id' => 1,
                'stock' => 50,
                'image' => 'baguette-tradition.jpg',
                'allergenes' => json_encode(['gluten']),
                'est_actif' => true
            ],
            [
                'nom' => 'Pain de Campagne',
                'description' => 'Pain rustique au levain naturel',
                'prix' => 3.50,
                'categorie_id' => 1,
                'stock' => 30,
                'image' => 'pain-campagne.jpg',
                'allergenes' => json_encode(['gluten']),
                'est_actif' => true
            ],
            [
                'nom' => 'Pain Complet',
                'description' => 'Pain riche en fibres à la farine complète',
                'prix' => 2.80,
                'categorie_id' => 1,
                'stock' => 25,
                'image' => 'pain-complet.jpg',
                'allergenes' => json_encode(['gluten']),
                'est_actif' => true
            ],

            // VIENNOISERIES
            [
                'nom' => 'Croissant au Beurre',
                'description' => 'Croissant pur beurre feuilleté',
                'prix' => 1.10,
                'categorie_id' => 2,
                'stock' => 40,
                'image' => 'croissant-beurre.jpg',
                'allergenes' => json_encode(['gluten', 'lait']),
                'est_actif' => true
            ],
            [
                'nom' => 'Pain au Chocolat',
                'description' => 'Viennoiserie au chocolat',
                'prix' => 1.30,
                'categorie_id' => 2,
                'stock' => 35,
                'image' => 'pain-chocolat.jpg',
                'allergenes' => json_encode(['gluten', 'lait']),
                'est_actif' => true
            ],
            [
                'nom' => 'Chausson aux Pommes',
                'description' => 'Chausson fourré à la compote de pommes',
                'prix' => 1.60,
                'categorie_id' => 2,
                'stock' => 20,
                'image' => 'chausson-pommes.jpg',
                'allergenes' => json_encode(['gluten', 'lait']),
                'est_actif' => true
            ],

            // PÂTISSERIES
            [
                'nom' => 'Éclair au Chocolat',
                'description' => 'Éclair garni de crème pâtissière au chocolat',
                'prix' => 2.80,
                'categorie_id' => 3,
                'stock' => 15,
                'image' => 'eclair-chocolat.jpg',
                'allergenes' => json_encode(['gluten', 'lait', 'œufs']),
                'est_actif' => true
            ],
            [
                'nom' => 'Tarte aux Fraises',
                'description' => 'Tartelette aux fraises fraîches sur crème pâtissière',
                'prix' => 3.20,
                'categorie_id' => 3,
                'stock' => 12,
                'image' => 'tarte-fraises.jpg',
                'allergenes' => json_encode(['gluten', 'lait', 'œufs']),
                'est_actif' => true
            ],
            [
                'nom' => 'Millefeuille',
                'description' => 'Feuilleté garni de crème pâtissière',
                'prix' => 3.50,
                'categorie_id' => 3,
                'stock' => 10,
                'image' => 'millefeuille.jpg',
                'allergenes' => json_encode(['gluten', 'lait', 'œufs']),
                'est_actif' => true
            ],

            // SANDWICHS
            [
                'nom' => 'Sandwich Jambon-Beurre',
                'description' => 'Sandwich traditionnel au jambon et beurre',
                'prix' => 3.80,
                'categorie_id' => 4,
                'stock' => 18,
                'image' => 'sandwich-jambon-beurre.jpg',
                'allergenes' => json_encode(['gluten', 'lait']),
                'est_actif' => true
            ],
            [
                'nom' => 'Sandwich Poulet-Crudités',
                'description' => 'Sandwich au poulet et crudités fraîches',
                'prix' => 4.20,
                'categorie_id' => 4,
                'stock' => 15,
                'image' => 'sandwich-poulet.jpg',
                'allergenes' => json_encode(['gluten']),
                'est_actif' => true
            ],

            // SPÉCIALITÉS
            [
                'nom' => 'Fougasse aux Olives',
                'description' => 'Pain provençal aux olives noires',
                'prix' => 2.90,
                'categorie_id' => 5,
                'stock' => 22,
                'image' => 'fougasse-olives.jpg',
                'allergenes' => json_encode(['gluten', 'olives']),
                'est_actif' => true
            ],
            [
                'nom' => 'Brioche Nanterre',
                'description' => 'Brioche moelleuse traditionnelle',
                'prix' => 4.50,
                'categorie_id' => 5,
                'stock' => 14,
                'image' => 'brioche-nanterre.jpg',
                'allergenes' => json_encode(['gluten', 'lait', 'œufs']),
                'est_actif' => true
            ]
        ];

        foreach ($produits as $produit) {
            DB::table('produits')->insert([
                'nom' => $produit['nom'],
                'description' => $produit['description'],
                'prix' => $produit['prix'],
                'categorie_id' => $produit['categorie_id'],
                'stock' => $produit['stock'],
                'image' => $produit['image'],
                'allergenes' => $produit['allergenes'],
                'est_actif' => $produit['est_actif'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
