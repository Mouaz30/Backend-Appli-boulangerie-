<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProduitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'prix' => $this->prix,
            'categorie' => new CategorieResource($this->whenLoaded('categorie')),
            'categorie_id' => $this->categorie_id,
            'stock' => $this->stock,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'allergenes' => $this->allergenes,
            'est_actif' => $this->est_actif,
            'promotions' => PromotionResource::collection($this->whenLoaded('promotions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}