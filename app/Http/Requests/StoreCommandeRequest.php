<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommandeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}




// <?php

// namespace App\Http\Requests;

// use Illuminate\Foundation\Http\FormRequest;

// class StoreCommandeRequest extends FormRequest
// {
//     public function authorize(): bool
//     {
//         return true; // Tous les utilisateurs authentifiés peuvent créer des commandes
//     }

//     public function rules(): array
//     {
//         return [
//             'adresse_livraison' => 'required|string|max:500',
//             'methode_paiement' => 'required|in:a_la_livraison,en_ligne',
//             'elements' => 'required|array|min:1',
//             'elements.*.produit_id' => 'required|exists:produits,id',
//             'elements.*.quantite' => 'required|integer|min:1',
//         ];
//     }

//     public function messages(): array
//     {
//         return [
//             'adresse_livraison.required' => 'L\'adresse de livraison est obligatoire.',
//             'methode_paiement.required' => 'La méthode de paiement est obligatoire.',
//             'methode_paiement.in' => 'La méthode de paiement doit être "à la livraison" ou "en ligne".',
//             'elements.required' => 'La commande doit contenir au moins un produit.',
//             'elements.min' => 'La commande doit contenir au moins un produit.',
//             'elements.*.produit_id.required' => 'L\'ID du produit est obligatoire.',
//             'elements.*.produit_id.exists' => 'Le produit sélectionné n\'existe pas.',
//             'elements.*.quantite.required' => 'La quantité est obligatoire.',
//             'elements.*.quantite.integer' => 'La quantité doit être un nombre entier.',
//             'elements.*.quantite.min' => 'La quantité doit être au moins de 1.',
//         ];
//     }
// }