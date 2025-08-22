<?php

namespace App\Http\Controllers;

use App\Http\Resources\PromotionResource;
use App\Models\Promotion;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PromotionController extends Controller
{
    protected $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $promotions = Promotion::with('produits')->get();
        return PromotionResource::collection($promotions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_reduction' => 'required|in:pourcentage,fixe',
            'valeur_reduction' => 'required|numeric|min:0',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'est_active' => 'boolean',
            'produit_ids' => 'nullable|array',
            'produit_ids.*' => 'exists:produits,id'
        ]);

        try {
            $promotion = $this->promotionService->creerPromotion(
                $validated,
                $request->produit_ids ?? []
            );

            return new PromotionResource($promotion);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la promotion',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Promotion $promotion)
    {
        return new PromotionResource($promotion->load('produits'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type_reduction' => 'sometimes|required|in:pourcentage,fixe',
            'valeur_reduction' => 'sometimes|required|numeric|min:0',
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'est_active' => 'boolean',
            'produit_ids' => 'nullable|array',
            'produit_ids.*' => 'exists:produits,id'
        ]);

        try {
            $promotion = $this->promotionService->mettreAJourPromotion(
                $promotion,
                $validated,
                $request->produit_ids ?? []
            );

            return new PromotionResource($promotion);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la promotion',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Promotion $promotion)
    {
        $promotion->delete();
        return response()->noContent();
    }

    /**
     * Get active promotions
     */
    public function actives()
    {
        $promotions = $this->promotionService->getPromotionsActives();
        return PromotionResource::collection($promotions);
    }

    /**
     * Apply promotion to a product
     */
    public function appliquer(Request $request, Promotion $promotion)
    {
        $request->validate([
            'produit_id' => 'required|exists:produits,id'
        ]);

        $produit = Produit::find($request->produit_id);
        $prixReduit = $promotion->appliquerReduction($produit->prix);

        return response()->json([
            'prix_original' => $produit->prix,
            'prix_reduit' => $prixReduit,
            'reduction' => $produit->prix - $prixReduit
        ]);
    }
}