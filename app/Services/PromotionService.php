<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\Produit;
use Carbon\Carbon;

class PromotionService
{
    public function creerPromotion(array $data, array $produitIds = [])
    {
        $promotion = Promotion::create($data);

        if (!empty($produitIds)) {
            $promotion->produits()->sync($produitIds);
        }

        return $promotion->load('produits');
    }

    public function mettreAJourPromotion(Promotion $promotion, array $data, array $produitIds = [])
    {
        $promotion->update($data);

        if (!empty($produitIds)) {
            $promotion->produits()->sync($produitIds);
        }

        return $promotion->load('produits');
    }

    public function getPromotionsActives()
    {
        return Promotion::with('produits')
            ->where('est_active', true)
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->get();
    }

    public function verifierPromotionValide(Promotion $promotion): bool
    {
        return $promotion->est_active &&
               Carbon::parse($promotion->date_debut)->lte(now()) &&
               Carbon::parse($promotion->date_fin)->gte(now());
    }

    public function appliquerPromotionProduit(Produit $produit): ?float
    {
        $promotion = $produit->promotions()
            ->where('est_active', true)
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->first();

        if ($promotion) {
            return $promotion->appliquerReduction($produit->prix);
        }

        return null;
    }

    public function desactiverPromotionsExpirees(): void
    {
        Promotion::where('date_fin', '<', now())
                 ->update(['est_active' => false]);
    }
}