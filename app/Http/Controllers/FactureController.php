<?php

namespace App\Http\Controllers;

use App\Http\Resources\FactureResource;
use App\Models\Facture;
use App\Models\Commande;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class FactureController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $factures = Facture::with(['commande.utilisateur', 'commande.elements.produit'])
            ->orderBy('created_at', 'desc')
            ->get();

        return FactureResource::collection($factures);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'commande_id' => 'required|exists:commandes,id|unique:factures,commande_id',
            'montant_ht' => 'required|numeric|min:0',
            'tva' => 'required|numeric|min:0',
            'montant_ttc' => 'required|numeric|min:0',
            'statut_paiement' => 'required|in:en_attente,paye,annule'
        ]);

        // Vérifier que la commande existe et est livrée/annulée
        $commande = Commande::find($validated['commande_id']);
        if (!in_array($commande->statut, ['livree', 'annulee'])) {
            return response()->json([
                'message' => 'Impossible de créer une facture pour une commande non livrée ou non annulée'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Générer le numéro de facture
        $numeroFacture = 'FAC-' . now()->format('Ymd') . '-' . str_pad(Facture::count() + 1, 4, '0', STR_PAD_LEFT);

        $facture = Facture::create([
            'commande_id' => $validated['commande_id'],
            'numero_facture' => $numeroFacture,
            'date_emission' => now(),
            'montant_ht' => $validated['montant_ht'],
            'tva' => $validated['tva'],
            'montant_ttc' => $validated['montant_ttc'],
            'statut_paiement' => $validated['statut_paiement']
        ]);

        // Générer le PDF de la facture
        $this->pdfService->genererFacturePdf($facture);

        return new FactureResource($facture->load(['commande.utilisateur', 'commande.elements.produit']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Facture $facture)
    {
        return new FactureResource($facture->load(['commande.utilisateur', 'commande.elements.produit']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Facture $facture)
    {
        $validated = $request->validate([
            'statut_paiement' => 'sometimes|required|in:en_attente,paye,annule',
            'montant_ht' => 'sometimes|required|numeric|min:0',
            'tva' => 'sometimes|required|numeric|min:0',
            'montant_ttc' => 'sometimes|required|numeric|min:0'
        ]);

        $facture->update($validated);

        return new FactureResource($facture->load(['commande.utilisateur', 'commande.elements.produit']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Facture $facture)
    {
        // Supprimer le fichier PDF associé
        if ($facture->chemin_pdf && file_exists(storage_path('app/public/' . $facture->chemin_pdf))) {
            unlink(storage_path('app/public/' . $facture->chemin_pdf));
        }

        $facture->delete();

        return response()->json([
            'message' => 'Facture supprimée avec succès'
        ]);
    }

    /**
     * Générer une facture pour une commande
     */
    public function genererPourCommande(Commande $commande)
    {
        // Vérifier si une facture existe déjà
        if ($commande->facture) {
            return response()->json([
                'message' => 'Une facture existe déjà pour cette commande'
            ], Response::HTTP_CONFLICT);
        }

        // Vérifier que la commande est livrée ou annulée
        if (!in_array($commande->statut, ['livree', 'annulee'])) {
            return response()->json([
                'message' => 'Impossible de générer une facture pour une commande non livrée ou non annulée'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Calculer les montants
        $montantHT = $commande->total;
        $tva = $montantHT * 0.1; // 10% de TVA
        $montantTTC = $montantHT + $tva;

        // Créer la facture
        $numeroFacture = 'FAC-' . now()->format('Ymd') . '-' . str_pad(Facture::count() + 1, 4, '0', STR_PAD_LEFT);

        $facture = Facture::create([
            'commande_id' => $commande->id,
            'numero_facture' => $numeroFacture,
            'date_emission' => now(),
            'montant_ht' => $montantHT,
            'tva' => $tva,
            'montant_ttc' => $montantTTC,
            'statut_paiement' => $commande->statut === 'annulee' ? 'annule' : 'en_attente'
        ]);

        // Générer le PDF
        $this->pdfService->genererFacturePdf($facture);

        return new FactureResource($facture->load(['commande.utilisateur', 'commande.elements.produit']));
    }

    /**
     * Télécharger le PDF de la facture
     */
    public function downloadPdf(Facture $facture)
    {
        if (!$facture->chemin_pdf || !file_exists(storage_path('app/public/' . $facture->chemin_pdf))) {
            return response()->json([
                'message' => 'Fichier PDF non disponible'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->download(
            storage_path('app/public/' . $facture->chemin_pdf),
            'facture-' . $facture->numero_facture . '.pdf'
        );
    }

    /**
     * Marquer une facture comme payée
     */
    public function marquerPayee(Facture $facture)
    {
        $facture->update(['statut_paiement' => 'paye']);

        return new FactureResource($facture->load(['commande.utilisateur', 'commande.elements.produit']));
    }

    /**
     * Marquer une facture comme annulée
     */
    public function marquerAnnulee(Facture $facture)
    {
        $facture->update(['statut_paiement' => 'annule']);

        return new FactureResource($facture->load(['commande.utilisateur', 'commande.elements.produit']));
    }

    /**
     * Regénérer le PDF d'une facture
     */
    public function regenererPdf(Facture $facture)
    {
        $cheminPdf = $this->pdfService->genererFacturePdf($facture);

        $facture->update(['chemin_pdf' => $cheminPdf]);

        return response()->json([
            'message' => 'PDF regénéré avec succès',
            'chemin_pdf' => $cheminPdf
        ]);
    }

    /**
     * Obtenir les factures d'un utilisateur
     */
    public function parUtilisateur(Request $request)
    {
        $user = $request->user();

        $factures = Facture::with(['commande', 'commande.elements.produit'])
            ->whereHas('commande', function($query) use ($user) {
                $query->where('utilisateur_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return FactureResource::collection($factures);
    }

    /**
     * Statistiques des factures
     */
    public function statistiques(Request $request)
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut'
        ]);

        $query = Facture::query();

        if ($request->has('date_debut')) {
            $query->where('date_emission', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date_emission', '<=', $request->date_fin);
        }

        $statistiques = [
            'total_factures' => $query->count(),
            'chiffre_affaires_ht' => $query->sum('montant_ht'),
            'chiffre_affaires_ttc' => $query->sum('montant_ttc'),
            'tva_collectee' => $query->sum('tva'),
            'factures_payees' => $query->where('statut_paiement', 'paye')->count(),
            'factures_en_attente' => $query->where('statut_paiement', 'en_attente')->count(),
            'factures_annulees' => $query->where('statut_paiement', 'annule')->count(),
        ];

        return response()->json($statistiques);
    }

    /**
     * Recherche de factures
     */
    public function search(Request $request)
    {
        $request->validate([
            'numero_facture' => 'nullable|string',
            'statut_paiement' => 'nullable|in:en_attente,paye,annule',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date'
        ]);

        $query = Facture::with(['commande.utilisateur', 'commande.elements.produit']);

        if ($request->has('numero_facture')) {
            $query->where('numero_facture', 'like', '%' . $request->numero_facture . '%');
        }

        if ($request->has('statut_paiement')) {
            $query->where('statut_paiement', $request->statut_paiement);
        }

        if ($request->has('date_debut')) {
            $query->where('date_emission', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date_emission', '<=', $request->date_fin);
        }

        $factures = $query->orderBy('date_emission', 'desc')->get();

        return FactureResource::collection($factures);
    }
}