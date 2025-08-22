<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommandeRequest;
use App\Http\Requests\UpdateCommandeRequest;
use App\Http\Resources\CommandeResource;
use App\Models\Commande;
use App\Services\CommandeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommandeController extends Controller
{
    protected $commandeService;

    public function __construct(CommandeService $commandeService)
    {
        $this->commandeService = $commandeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'customer') {
            $commandes = Commande::with(['elements.produit'])
                ->where('utilisateur_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $commandes = Commande::with(['utilisateur', 'elements.produit'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return CommandeResource::collection($commandes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommandeRequest $request)
    {
        try {
            $commande = $this->commandeService->creerCommande(
                $request->validated(),
                $request->user()
            );

            return new CommandeResource($commande);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Commande $commande)
    {
        $this->authorize('view', $commande);

        return new CommandeResource($commande->load([
            'utilisateur',
            'elements.produit',
            'facture'
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommandeRequest $request, Commande $commande)
    {
        try {
            $commande->update($request->validated());

            return new CommandeResource($commande->load(['utilisateur', 'elements.produit']));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la commande',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update order status
     */
    public function updateStatut(Request $request, Commande $commande)
    {
        $request->validate([
            'statut' => 'required|in:en_attente,en_preparation,prete,en_livraison,livree,annulee'
        ]);

        try {
            $commande = $this->commandeService->mettreAJourStatut(
                $commande,
                $request->statut
            );

            return new CommandeResource($commande);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du statut',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get orders by status
     */
    public function parStatut(Request $request, $statut)
    {
        $request->validate([
            'statut' => 'in:en_attente,en_preparation,prete,en_livraison,livree,annulee'
        ]);

        $commandes = $this->commandeService->getCommandesParStatut($statut);
        return CommandeResource::collection($commandes);
    }

    /**
     * Cancel an order
     */
    public function annuler(Commande $commande)
    {
        $this->authorize('update', $commande);

        try {
            $commande = $this->commandeService->mettreAJourStatut($commande, 'annulee');
            return new CommandeResource($commande);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'annulation de la commande',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}