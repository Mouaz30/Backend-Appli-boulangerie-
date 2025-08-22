<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategorieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'est_actif' => $this->est_actif,
            'nombre_produits' => $this->whenCounted('produits'),
            'produits' => ProduitResource::collection($this->whenLoaded('produits')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}