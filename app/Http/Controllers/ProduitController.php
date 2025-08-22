<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProduitRequest;
use App\Http\Requests\UpdateProduitRequest;
use App\Http\Resources\ProduitResource;
use App\Models\Produit;
use App\Services\ProduitService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProduitController extends Controller
{
    protected $produitService;

    public function __construct(ProduitService $produitService)
    {
        $this->produitService = $produitService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $produits = Produit::with('categorie')
            ->where('est_actif', true)
            ->get();

        return ProduitResource::collection($produits);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProduitRequest $request)
    {
        $this->authorize('create', Produit::class);
        
        try {
            $produit = $this->produitService->creerProduit(
                $request->validated(),
                $request->file('image')
            );

            return new ProduitResource($produit);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du produit',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Produit $produit)
    {
        return new ProduitResource($produit->load('categorie', 'promotions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProduitRequest $request, Produit $produit)
    {
        $this->authorize('update', $produit);
        
        try {
            $produit = $this->produitService->mettreAJourProduit(
                $produit,
                $request->validated(),
                $request->file('image')
            );

            return new ProduitResource($produit);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du produit',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Produit $produit)
    {
        $this->authorize('delete', $produit);
        
        try {
            $this->produitService->supprimerProduit($produit);

            return response()->json([
                'message' => 'Produit désactivé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du produit',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get products with active promotions
     */
    public function avecPromotions()
    {
        $produits = $this->produitService->getProduitsAvecPromotions();
        return ProduitResource::collection($produits);
    }

    /**
     * Search products
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'categorie_id' => 'nullable|exists:categories,id'
        ]);

        $query = Produit::with('categorie')
            ->where('est_actif', true)
            ->where(function ($q) use ($request) {
                $q->where('nom', 'like', '%' . $request->query . '%')
                  ->orWhere('description', 'like', '%' . $request->query . '%');
            });

        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        return ProduitResource::collection($query->get());
    }

    /**
     * Get products by category
     */
    public function parCategorie($categorieId)
    {
        $produits = Produit::with('categorie')
            ->where('categorie_id', $categorieId)
            ->where('est_actif', true)
            ->get();

        return ProduitResource::collection($produits);
    }

    /**
     * Update product stock
     */
    public function updateStock(Request $request, Produit $produit)
    {
        $this->authorize('update', $produit);
        
        $request->validate([
            'stock' => 'required|integer|min:0'
        ]);

        $produit->update(['stock' => $request->stock]);

        return new ProduitResource($produit);
    }

    /**
     * Activate/Deactivate product
     */
    public function toggleStatus(Produit $produit)
    {
        $this->authorize('update', $produit);
        
        $produit->update(['est_actif' => !$produit->est_actif]);

        return response()->json([
            'message' => 'Statut du produit mis à jour',
            'est_actif' => $produit->est_actif
        ]);
    }

    /**
     * Get low stock products
     */
    public function stockFaible()
    {
        $this->authorize('viewAny', Produit::class);
        
        $produits = Produit::with('categorie')
            ->where('est_actif', true)
            ->where('stock', '<', 10)
            ->get();

        return ProduitResource::collection($produits);
    }

    /**
     * Bulk update products
     */
    public function bulkUpdate(Request $request)
    {
        $this->authorize('update', Produit::class);
        
        $request->validate([
            'produits' => 'required|array',
            'produits.*.id' => 'required|exists:produits,id',
            'produits.*.prix' => 'nullable|numeric|min:0',
            'produits.*.stock' => 'nullable|integer|min:0',
            'produits.*.est_actif' => 'nullable|boolean'
        ]);

        $updated = [];
        
        foreach ($request->produits as $produitData) {
            $produit = Produit::find($produitData['id']);
            
            if ($produit) {
                $produit->update(array_filter([
                    'prix' => $produitData['prix'] ?? null,
                    'stock' => $produitData['stock'] ?? null,
                    'est_actif' => $produitData['est_actif'] ?? null
                ]));
                
                $updated[] = $produit;
            }
        }

        return ProduitResource::collection(collect($updated));
    }
}