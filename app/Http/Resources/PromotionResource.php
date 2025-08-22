<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'type_reduction' => $this->type_reduction,
            'valeur_reduction' => $this->valeur_reduction,
            'date_debut' => $this->date_debut,
            'date_fin' => $this->date_fin,
            'est_active' => $this->est_active,
            'est_valide' => $this->est_active && 
            now()->between($this->date_debut, $this->date_fin),
            'produits' => ProduitResource::collection($this->whenLoaded('produits')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}