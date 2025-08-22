<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FactureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_facture' => $this->numero_facture,
            'date_emission' => $this->date_emission,
            'montant_ht' => $this->montant_ht,
            'tva' => $this->tva,
            'montant_ttc' => $this->montant_ttc,
            'statut_paiement' => $this->statut_paiement,
            'chemin_pdf' => $this->chemin_pdf,
            'url_pdf' => $this->chemin_pdf ? asset('storage/' . $this->chemin_pdf) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}