<?php

namespace App\Http\Controllers;

use App\Http\Resources\ElementCommandeResource;
use App\Models\ElementCommande;
use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ElementCommandeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $elements = ElementCommande::with(['commande', 'produit'])->get();
        return ElementCommandeResource::collection($elements);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'commande_id' => 'required|exists:commandes,id',
            'produit_id' => 'required|exists:produits,id',
            'quantite' => 'required|integer|min:1',
            'prix_unitaire' => 'required|numeric|min:0'
        ]);

        // Vérifier le stock
        $produit = Produit::find($validated['produit_id']);
        if ($produit->stock < $validated['quantite']) {
            return response()->json([
                'message' => 'Stock insuffisant pour le produit: ' . $produit->nom,
                'stock_disponible' => $produit->stock
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $element = ElementCommande::create($validated);

        // Mettre à jour le stock du produit
        $produit->decrement('stock', $validated['quantite']);

        // Recalculer le total de la commande
        $this->recalculerTotalCommande($validated['commande_id']);

        return new ElementCommandeResource($element->load(['commande', 'produit']));
    }

    /**
     * Display the specified resource.
     */
    public function show(ElementCommande $elementCommande)
    {
        return new ElementCommandeResource($elementCommande->load(['commande', 'produit']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ElementCommande $elementCommande)
    {
        $validated = $request->validate([
            'quantite' => 'sometimes|required|integer|min:1',
            'prix_unitaire' => 'sometimes|required|numeric|min:0'
        ]);

        // Si la quantité change, gérer le stock
        if (isset($validated['quantite']) && $validated['quantite'] != $elementCommande->quantite) {
            $difference = $validated['quantite'] - $elementCommande->quantite;
            $produit = $elementCommande->produit;

            if ($difference > 0 && $produit->stock < $difference) {
                return response()->json([
                    'message' => 'Stock insuffisant pour augmenter la quantité',
                    'stock_disponible' => $produit->stock
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Ajuster le stock
            if ($difference > 0) {
                $produit->decrement('stock', $difference);
            } else {
                $produit->increment('stock', abs($difference));
            }
        }

        $elementCommande->update($validated);

        // Recalculer le total de la commande
        $this->recalculerTotalCommande($elementCommande->commande_id);

        return new ElementCommandeResource($elementCommande->load(['commande', 'produit']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ElementCommande $elementCommande)
    {
        DB::transaction(function () use ($elementCommande) {
            $commandeId = $elementCommande->commande_id;
            
            // Restocker le produit
            $elementCommande->produit->increment('stock', $elementCommande->quantite);
            
            // Supprimer l'élément
            $elementCommande->delete();
            
            // Recalculer le total de la commande
            $this->recalculerTotalCommande($commandeId);
        });

        return response()->json([
            'message' => 'Élément de commande supprimé avec succès'
        ]);
    }

    /**
     * Récupérer les éléments d'une commande spécifique
     */
    public function parCommande(Commande $commande)
    {
        $elements = ElementCommande::with('produit')
            ->where('commande_id', $commande->id)
            ->get();

        return ElementCommandeResource::collection($elements);
    }

    /**
     * Ajouter plusieurs éléments à une commande
     */
    public function storeMultiple(Request $request)
    {
        $validated = $request->validate([
            'commande_id' => 'required|exists:commandes,id',
            'elements' => 'required|array|min:1',
            'elements.*.produit_id' => 'required|exists:produits,id',
            'elements.*.quantite' => 'required|integer|min:1',
            'elements.*.prix_unitaire' => 'required|numeric|min:0'
        ]);

        $elementsCrees = [];

        DB::transaction(function () use ($validated, &$elementsCrees) {
            foreach ($validated['elements'] as $elementData) {
                // Vérifier le stock
                $produit = Produit::find($elementData['produit_id']);
                if ($produit->stock < $elementData['quantite']) {
                    throw new \Exception("Stock insuffisant pour le produit: " . $produit->nom);
                }

                $element = ElementCommande::create([
                    'commande_id' => $validated['commande_id'],
                    'produit_id' => $elementData['produit_id'],
                    'quantite' => $elementData['quantite'],
                    'prix_unitaire' => $elementData['prix_unitaire']
                ]);

                // Mettre à jour le stock
                $produit->decrement('stock', $elementData['quantite']);

                $elementsCrees[] = $element;
            }

            // Recalculer le total de la commande
            $this->recalculerTotalCommande($validated['commande_id']);
        });

        return ElementCommandeResource::collection(collect($elementsCrees)->load('produit'));
    }

    /**
     * Recalculer le total d'une commande
     */
    private function recalculerTotalCommande($commandeId)
    {
        $total = ElementCommande::where('commande_id', $commandeId)
            ->sum(DB::raw('quantite * prix_unitaire'));

        Commande::where('id', $commandeId)->update(['total' => $total]);
    }

    /**
     * Statistiques des produits les plus vendus
     */
    public function statistiquesProduits(Request $request)
    {
        $statistiques = ElementCommande::select(
            'produit_id',
            DB::raw('SUM(quantite) as total_vendu'),
            DB::raw('SUM(quantite * prix_unitaire) as chiffre_affaires')
        )
        ->with('produit')
        ->groupBy('produit_id')
        ->orderBy('total_vendu', 'desc')
        ->get();

        return response()->json($statistiques);
    }

    /**
     * Mettre à jour le prix unitaire basé sur les promotions
     */
    public function mettreAJourPrixAvecPromotions(ElementCommande $elementCommande)
    {
        $produit = $elementCommande->produit;
        $nouveauPrix = $produit->prix;

        // Appliquer les promotions actives
        $promotionActive = $produit->promotions()
            ->where('est_active', true)
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->first();

        if ($promotionActive) {
            $nouveauPrix = $promotionActive->appliquerReduction($produit->prix);
        }

        $elementCommande->update(['prix_unitaire' => $nouveauPrix]);

        // Recalculer le total de la commande
        $this->recalculerTotalCommande($elementCommande->commande_id);

        return new ElementCommandeResource($elementCommande->load(['commande', 'produit']));
    }
}