<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\ElementCommande;
use App\Models\Produit;
use App\Models\User;
use App\Notifications\CommandeStatutModifie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommandeService
{
    public function creerCommande(array $data, User $utilisateur)
    {
        return DB::transaction(function () use ($data, $utilisateur) {
            $commande = Commande::create([
                'utilisateur_id' => $utilisateur->id,
                'total' => 0,
                'statut' => 'en_attente',
                'adresse_livraison' => $data['adresse_livraison'] ?? $utilisateur->adresse,
                'methode_paiement' => $data['methode_paiement'],
            ]);

            $total = 0;

            foreach ($data['elements'] as $element) {
                $produit = Produit::findOrFail($element['produit_id']);
                
                if ($produit->stock < $element['quantite']) {
                    throw new \Exception("Stock insuffisant pour le produit: {$produit->nom}");
                }

                $prix = $this->calculerPrixAvecPromotions($produit);

                ElementCommande::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $produit->id,
                    'quantite' => $element['quantite'],
                    'prix_unitaire' => $prix,
                ]);

                $total += $prix * $element['quantite'];
                
                // Mettre à jour le stock
                $produit->decrement('stock', $element['quantite']);
            }

            $commande->update(['total' => $total]);

            // Générer la facture
            $this->genererFacture($commande);

            return $commande->load('elements.produit');
        });
    }

    public function mettreAJourStatut(Commande $commande, string $statut)
    {
        DB::transaction(function () use ($commande, $statut) {
            $ancienStatut = $commande->statut;
            $commande->update(['statut' => $statut]);

            // Notifier le client du changement de statut
            if ($commande->utilisateur) {
                $commande->utilisateur->notify(
                    new CommandeStatutModifie($commande, $ancienStatut, $statut)
                );
            }

            // Si la commande est annulée, restocker les produits
            if ($statut === 'annulee') {
                $this->restockerProduits($commande);
            }
        });

        return $commande;
    }

    private function calculerPrixAvecPromotions(Produit $produit): float
    {
        $prix = $produit->prix;

        // Vérifier les promotions actives
        $promotionActive = $produit->promotions()
            ->where('est_active', true)
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->first();

        if ($promotionActive) {
            $prix = $promotionActive->appliquerReduction($prix);
        }

        return $prix;
    }

    private function restockerProduits(Commande $commande): void
    {
        foreach ($commande->elements as $element) {
            $element->produit->increment('stock', $element->quantite);
        }
    }

    private function genererFacture(Commande $commande): void
    {
        $tva = $commande->total * 0.1; // 10% de TVA

        $commande->facture()->create([
            'numero_facture' => 'FAC-' . now()->format('Ymd') . '-' . $commande->id,
            'date_emission' => now(),
            'montant_ht' => $commande->total,
            'tva' => $tva,
            'montant_ttc' => $commande->total + $tva,
            'statut_paiement' => $commande->methode_paiement === 'a_la_livraison' ? 'en_attente' : 'paye',
        ]);
    }

    public function getCommandesParStatut(string $statut = null)
    {
        $query = Commande::with(['utilisateur', 'elements.produit']);

        if ($statut) {
            $query->where('statut', $statut);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getChiffreAffairesPeriodique($dateDebut, $dateFin)
    {
        return Commande::whereBetween('created_at', [$dateDebut, $dateFin])
            ->where('statut', '!=', 'annulee')
            ->sum('total');
    }
}