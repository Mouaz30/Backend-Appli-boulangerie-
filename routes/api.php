<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ElementCommandeController;
use App\Http\Controllers\FactureController;

Route::prefix('v1')->group(function () {
    // Routes publiques
    Route::post('/inscription', [AuthController::class, 'register']);
    Route::post('/connexion', [AuthController::class, 'login']);
    Route::get('/verifier-auth', [AuthController::class, 'checkAuth']);
    
    // Produits et catégories publics
    Route::get('/produits', [ProduitController::class, 'index']);
    Route::get('/produits/{produit}', [ProduitController::class, 'show']);
    Route::get('/produits/promotions/actives', [ProduitController::class, 'avecPromotions']);
    Route::get('/produits/categorie/{categorieId}', [ProduitController::class, 'parCategorie']);
    Route::get('/produits/search', [ProduitController::class, 'search']);
    
    Route::get('/categories', [CategorieController::class, 'index']);
    Route::get('/categories/{categorie}', [CategorieController::class, 'show']);
    Route::get('/categories/{categorie}/produits', [CategorieController::class, 'produits']);

    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        // Authentification
        Route::post('/deconnexion', [AuthController::class, 'logout']);
        Route::get('/utilisateur', [AuthController::class, 'user']);
        Route::put('/profil', [AuthController::class, 'updateProfile']);
        Route::put('/changer-mot-de-passe', [AuthController::class, 'changePassword']);

        // Commandes (clients)
        Route::middleware('role:customer')->group(function () {
            Route::apiResource('commandes', CommandeController::class)->only(['index', 'store', 'show']);
            Route::post('/commandes/{commande}/annuler', [CommandeController::class, 'annuler']);
        });

        // Employés et admin
        Route::middleware('role:employee,admin')->group(function () {
            Route::apiResource('commandes', CommandeController::class)->except(['store']);
            Route::put('/commandes/{commande}/statut', [CommandeController::class, 'updateStatut']);
            Route::get('/commandes/statut/{statut}', [CommandeController::class, 'parStatut']);
        });

        // Routes pour les éléments de commande (employés et admin)
        Route::middleware('role:employee,admin')->group(function () {
            Route::get('/elements-commande', [ElementCommandeController::class, 'index']);
            Route::get('/elements-commande/{elementCommande}', [ElementCommandeController::class, 'show']);
            Route::post('/elements-commande', [ElementCommandeController::class, 'store']);
            Route::put('/elements-commande/{elementCommande}', [ElementCommandeController::class, 'update']);
            Route::delete('/elements-commande/{elementCommande}', [ElementCommandeController::class, 'destroy']);
            
            Route::get('/commandes/{commande}/elements', [ElementCommandeController::class, 'parCommande']);
            Route::post('/elements-commande/multiple', [ElementCommandeController::class, 'storeMultiple']);
            Route::get('/statistiques/produits-vendus', [ElementCommandeController::class, 'statistiquesProduits']);
            Route::put('/elements-commande/{elementCommande}/mettre-a-jour-prix', [ElementCommandeController::class, 'mettreAJourPrixAvecPromotions']);
        });

        // Routes pour les factures
        // Clients peuvent voir leurs factures et les télécharger
        Route::middleware('role:customer')->group(function () {
            Route::get('/mes-factures', [FactureController::class, 'parUtilisateur']);
            Route::get('/factures/{facture}/download', [FactureController::class, 'downloadPdf']);
        });

        // Employés et admin peuvent gérer toutes les factures
        Route::middleware('role:employee,admin')->group(function () {
            Route::get('/factures', [FactureController::class, 'index']);
            Route::get('/factures/{facture}', [FactureController::class, 'show']);
            Route::post('/factures', [FactureController::class, 'store']);
            Route::put('/factures/{facture}', [FactureController::class, 'update']);
            Route::delete('/factures/{facture}', [FactureController::class, 'destroy']);
            
            Route::post('/commandes/{commande}/generer-facture', [FactureController::class, 'genererPourCommande']);
            Route::put('/factures/{facture}/marquer-payee', [FactureController::class, 'marquerPayee']);
            Route::put('/factures/{facture}/marquer-annulee', [FactureController::class, 'marquerAnnulee']);
            Route::post('/factures/{facture}/regenerer-pdf', [FactureController::class, 'regenererPdf']);
            Route::get('/factures/{facture}/download', [FactureController::class, 'downloadPdf']);
            Route::get('/statistiques/factures', [FactureController::class, 'statistiques']);
            Route::get('/recherche/factures', [FactureController::class, 'search']);
        });

        // Admin seulement
        Route::middleware('role:admin')->group(function () {
            // Gestion des produits - Routes supplémentaires
            Route::put('/produits/{produit}/stock', [ProduitController::class, 'updateStock']);
            Route::put('/produits/{produit}/toggle-status', [ProduitController::class, 'toggleStatus']);
            Route::get('/produits/stock-faible', [ProduitController::class, 'stockFaible']);
            Route::put('/produits/bulk-update', [ProduitController::class, 'bulkUpdate']);
            
            // Gestion des produits - CRUD complet
            Route::apiResource('produits', ProduitController::class)->except(['index', 'show']);
            
            // Gestion des catégories
            Route::apiResource('categories', CategorieController::class);
            
            // Gestion des promotions
            Route::apiResource('promotions', PromotionController::class);
            
            // Statistiques
            Route::get('/statistiques/chiffre-affaires', [CommandeController::class, 'chiffreAffaires']);
            
            // Gestion des utilisateurs
            Route::get('/utilisateurs', [AuthController::class, 'indexUsers']);
            Route::get('/utilisateurs/{user}', [AuthController::class, 'showUser']);
            Route::put('/utilisateurs/{user}', [AuthController::class, 'updateUser']);
            Route::delete('/utilisateurs/{user}', [AuthController::class, 'deleteUser']);
        });
    });
});