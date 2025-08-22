<?php

namespace App\Services;

use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function genererFacturePdf(Facture $facture): string
    {
        $data = [
            'facture' => $facture->load('commande.elements.produit', 'commande.utilisateur'),
            'date_emission' => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('pdf.facture', $data);
        $nomFichier = "facture-{$facture->numero_facture}.pdf";
        $chemin = "factures/{$nomFichier}";

        // Stocker le PDF
        Storage::disk('public')->put($chemin, $pdf->output());

        // Mettre à jour le chemin de la facture
        $facture->update(['chemin_pdf' => $chemin]);

        return $chemin;
    }

    public function genererRapportCommandes($dateDebut, $dateFin): string
    {
        $commandes = Commande::with('utilisateur', 'elements.produit')
            ->whereBetween('created_at', [$dateDebut, $dateFin])
            ->where('statut', '!=', 'annulee')
            ->get();

        $data = [
            'commandes' => $commandes,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'total_chiffre_affaires' => $commandes->sum('total'),
        ];

        $pdf = Pdf::loadView('pdf.rapport-commandes', $data);
        $nomFichier = "rapport-commandes-{$dateDebut}-{$dateFin}.pdf";
        $chemin = "rapports/{$nomFichier}";

        Storage::disk('public')->put($chemin, $pdf->output());

        return $chemin;
    }
}