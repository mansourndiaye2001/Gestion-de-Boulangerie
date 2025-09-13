<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\EmployeCommandeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StatistiqueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/getAll', [AuthController::class, 'getAll']);
    Route::delete('/delete/{id}', [AuthController::class, 'deleteUser']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::resource('categories', CategorieController::class);
});
//Routes pour les produits 
Route::middleware('auth:sanctum')->group(function () {
 Route::resource('products', ProductController::class);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:sanctum')->group(function () {
    
    // Catalogue pour les clients
    Route::get('/produits/catalogue', [ProductController::class, 'catalogue']);
    Route::get('/produits/catalogue/promotion', [ProductController::class, 'productsOnPromotion']);
    
    // Gestion des commandes
    Route::prefix('commandes')->group(function () {
        Route::get('/', [CommandeController::class, 'index']); // Mes commandes
        Route::post('/', [CommandeController::class, 'store']); // Créer une commande
        Route::get('/{commande}', [CommandeController::class, 'show']); // Détails commande
        Route::patch('/{commande}/annuler', [CommandeController::class, 'annuler']); // Annuler
    });
    
});
Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::prefix('employe/commandes')->group(function () {
        
        // Liste des commandes avec filtres et statistiques
        Route::get('/', [EmployeCommandeController::class, 'index']);
        
    
        Route::get('/{commande}', [EmployeCommandeController::class, 'show']);
        
        Route::put('/{commande}/statut', [EmployeCommandeController::class, 'updateStatut']);
        
        // Assigner une commande à un employé
        Route::patch('/{commande}/assigner', [EmployeCommandeController::class, 'assigner']);
        
    
        Route::get('/employes/liste', [EmployeCommandeController::class, 'getEmployes']);
    });
});
// Dans routes/api.php
Route::get('storage/uploads/{filename}', function ($filename) {
    $path = storage_path('app/public/uploads/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    return response()->file($path);
});
Route::prefix('admin/stats')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/chiffre-affaires-total', [StatistiqueController::class, 'chiffreAffairesTotal']);
    Route::get('/chiffre-affaires-mensuel', [StatistiqueController::class, 'chiffreAffairesMensuel']);
    Route::get('/produits-plus-vendus', [StatistiqueController::class, 'produitsLesPlusVendus']);
    Route::get('/commandes-par-statut', [StatistiqueController::class, 'commandesParStatut']);
    Route::get('/nouveaux-clients', [StatistiqueController::class, 'nouveauxClientsParMois']);
    Route::get('/ventes-promotions', [StatistiqueController::class, 'performancesPromotions']);
});