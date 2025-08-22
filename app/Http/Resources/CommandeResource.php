<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommandeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'utilisateur' => new UserResource($this->whenLoaded('utilisateur')),
            'utilisateur_id' => $this->utilisateur_id,
            'total' => $this->total,
            'statut' => $this->statut,
            'statut_libelle' => $this->getStatutLibelle(),
            'adresse_livraison' => $this->adresse_livraison,
            'methode_paiement' => $this->methode_paiement,
            'date_livraison' => $this->date_livraison,
            'numero_suivi' => $this->numero_suivi,
            'elements' => ElementCommandeResource::collection($this->whenLoaded('elements')),
            'facture' => new FactureResource($this->whenLoaded('facture')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}