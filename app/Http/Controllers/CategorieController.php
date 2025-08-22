<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategorieResource;
use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategorieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Categorie::withCount(['produits' => function($query) {
            $query->where('est_actif', true);
        }])
        ->where('est_actif', true)
        ->get();

        return CategorieResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:categories,nom',
            'description' => 'nullable|string',
            'est_actif' => 'boolean'
        ]);

        $categorie = Categorie::create($validated);

        return new CategorieResource($categorie);
    }

    /**
     * Display the specified resource.
     */
    public function show(Categorie $categorie)
    {
        return new CategorieResource($categorie->load('produits'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categorie $categorie)
    {
        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255|unique:categories,nom,' . $categorie->id,
            'description' => 'nullable|string',
            'est_actif' => 'boolean'
        ]);

        $categorie->update($validated);

        return new CategorieResource($categorie);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categorie $categorie)
    {
        // Vérifier si la catégorie contient des produits
        if ($categorie->produits()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette catégorie car elle contient des produits.'
            ], Response::HTTP_CONFLICT);
        }

        $categorie->delete();

        return response()->noContent();
    }

    /**
     * Get products by category
     */
    public function produits(Categorie $categorie)
    {
        $produits = $categorie->produits()
            ->where('est_actif', true)
            ->with('categorie')
            ->get();

        return response()->json($produits);
    }
}