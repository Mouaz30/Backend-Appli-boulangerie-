<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seuls les employés et admin peuvent modifier les commandes
        return $this->user() && in_array($this->user()->role, ['employee', 'admin']);
    }

    public function rules(): array
    {
        return [
            'statut' => 'sometimes|required|in:en_attente,en_preparation,prete,en_livraison,livree,annulee',
            'adresse_livraison' => 'sometimes|required|string|max:500',
            'numero_suivi' => 'nullable|string|max:100',
            'date_livraison' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'statut.required' => 'Le statut est obligatoire.',
            'statut.in' => 'Le statut doit être valide.',
            'adresse_livraison.required' => 'L\'adresse de livraison est obligatoire.',
            'numero_suivi.max' => 'Le numéro de suivi ne peut pas dépasser 100 caractères.',
            'date_livraison.date' => 'La date de livraison doit être une date valide.',
        ];
    }
}