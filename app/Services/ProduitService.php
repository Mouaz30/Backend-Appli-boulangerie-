<?php

namespace App\Services;

use App\Models\Produit;
use App\Models\Categorie;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProduitService
{
    public function creerProduit(array $data, ?UploadedFile $image = null)
    {
        $produit = Produit::create($data);

        if ($image) {
            $this->stockerImage($produit, $image);
        }

        return $produit->load('categorie');
    }

    public function mettreAJourProduit(Produit $produit, array $data, ?UploadedFile $image = null)
    {
        $produit->update($data);

        if ($image) {
            $this->supprimerAncienneImage($produit);
            $this->stockerImage($produit, $image);
        }

        return $produit->load('categorie');
    }

    public function supprimerProduit(Produit $produit)
    {
        $this->supprimerAncienneImage($produit);
        $produit->update(['est_actif' => false]);
        
        return $produit;
    }

    private function stockerImage(Produit $produit, UploadedFile $image): void
    {
        $chemin = $image->store('produits', 'public');
        $produit->update(['image' => $chemin]);
    }

    private function supprimerAncienneImage(Produit $produit): void
    {
        if ($produit->image && Storage::disk('public')->exists($produit->image)) {
            Storage::disk('public')->delete($produit->image);
        }
    }

    public function getProduitsAvecPromotions()
    {
        return Produit::with(['promotions' => function($query) {
            $query->where('est_active', true)
                  ->where('date_debut', '<=', now())
                  ->where('date_fin', '>=', now());
        }])
        ->actif()
        ->get();
    }

    public function verifierStock(Produit $produit, int $quantiteDemandee): bool
    {
        return $produit->stock >= $quantiteDemandee;
    }

    public function ajusterStock(Produit $produit, int $quantite): void
    {
        $produit->increment('stock', $quantite);
    }
}