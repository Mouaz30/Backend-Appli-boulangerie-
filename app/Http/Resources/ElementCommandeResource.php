<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElementCommandeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'produit' => new ProduitResource($this->whenLoaded('produit')),
            'produit_id' => $this->produit_id,
            'quantite' => $this->quantite,
            'prix_unitaire' => $this->prix_unitaire,
            'prix_total' => $this->prix_unitaire * $this->quantite,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}