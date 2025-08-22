<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,employee,customer',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:500'
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.in' => 'Le rôle doit être admin, employee ou customer.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->nom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->telephone,
            'address' => $request->adresse
        ]);

        $token = $user->createToken('bakery_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            'message' => 'Inscription réussie'
        ], 201);
    }

    /**
     * Connexion de l'utilisateur
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ], [
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        $token = $user->createToken('bakery_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            'message' => 'Connexion réussie'
        ]);
    }

    /**
     * Déconnexion de l'utilisateur
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Récupérer l'utilisateur courant
     */
    public function user(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     */
    public function checkAuth(Request $request)
    {
        return response()->json([
            'authenticated' => $request->user() !== null,
            'user' => $request->user() ? new UserResource($request->user()) : null
        ]);
    }

    /**
     * Mettre à jour le profil utilisateur
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:500'
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
        ]);

        $user->update([
            'name' => $validated['nom'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'phone' => $validated['telephone'] ?? $user->phone,
            'address' => $validated['adresse'] ?? $user->address
        ]);

        return new UserResource($user);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed'
        ], [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'new_password.required' => 'Le nouveau mot de passe est obligatoire.',
            'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'new_password.confirmed' => 'La confirmation du nouveau mot de passe ne correspond pas.',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès'
        ]);
    }

    /**
     * Lister tous les utilisateurs (admin seulement)
     */
    public function indexUsers(Request $request)
    {
        // Vérifier que l'utilisateur est admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Accès non autorisé. Rôle admin requis.'
            ], 403);
        }

        $users = User::all();
        return UserResource::collection($users);
    }

    /**
     * Afficher un utilisateur spécifique (admin seulement)
     */
    public function showUser(Request $request, User $user)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Accès non autorisé. Rôle admin requis.'
            ], 403);
        }

        return new UserResource($user);
    }

    /**
     * Mettre à jour un utilisateur (admin seulement)
     */
    public function updateUser(Request $request, User $user)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Accès non autorisé. Rôle admin requis.'
            ], 403);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'sometimes|required|in:admin,employee,customer',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:500'
        ]);

        $user->update([
            'name' => $validated['nom'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'role' => $validated['role'] ?? $user->role,
            'phone' => $validated['telephone'] ?? $user->phone,
            'address' => $validated['adresse'] ?? $user->address
        ]);

        return new UserResource($user);
    }

    /**
     * Supprimer un utilisateur (admin seulement)
     */
    public function deleteUser(Request $request, User $user)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Accès non autorisé. Rôle admin requis.'
            ], 403);
        }

        // Empêcher la suppression de soi-même
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }
}