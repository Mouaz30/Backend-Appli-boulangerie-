<?php

namespace App\Services;

use App\Models\User;
use App\Models\Commande;
use App\Notifications\CommandeStatutModifie;
use App\Notifications\NouvellePromotion;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function notifierChangementStatutCommande(Commande $commande, string $ancienStatut, string $nouveauStatut): void
    {
        if ($commande->utilisateur) {
            $commande->utilisateur->notify(
                new CommandeStatutModifie($commande, $ancienStatut, $nouveauStatut)
            );
        }
    }

    public function notifierNouvellePromotion(Promotion $promotion): void
    {
        $clients = User::where('role', 'customer')->get();
        
        Notification::send($clients, new NouvellePromotion($promotion));
    }

    public function notifierStockFaible(Produit $produit): void
    {
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            // Envoyer une notification ou email pour stock faible
            // Implementation dépend de votre système de notification
        }
    }

    public function envoyerFactureParEmail(Facture $facture): void
    {
        if ($facture->commande->utilisateur) {
            $utilisateur = $facture->commande->utilisateur;
            $cheminPdf = Storage::disk('public')->path($facture->chemin_pdf);
            
            // Envoyer l'email avec la facture en pièce jointe
            // Implementation dépend de votre système d'email
        }
    }
}