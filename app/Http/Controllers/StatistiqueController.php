<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\ArticleCommande;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StatistiqueController extends Controller
{
    private function checkAdminRole()
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Accès interdit. Vous devez être administrateur.'], 403);
        }
        return null;
    }

    /**
     * Chiffre d'affaires total
     */
    public function chiffreAffairesTotal()
    {
        $checkRole = $this->checkAdminRole();
        if ($checkRole) {
            return $checkRole;
        }

        try {
            $total = Commande::where('statut', 'livree')->sum('montant_total');
            return response()->json(['chiffre_affaires_total' => $total]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du calcul du chiffre d\'affaires total.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Chiffre d'affaires par mois (12 derniers mois)
     */
    public function chiffreAffairesMensuel()
    {
        $checkRole = $this->checkAdminRole();
        if ($checkRole) {
            return $checkRole;
        }
    
        try {
            $result = Commande::select(
                    DB::raw("TO_CHAR(created_at, 'YYYY-MM') as mois"),
                    DB::raw('SUM(montant_total) as total')
                )
                ->where('statut', 'livree')
                ->groupBy('mois')
                ->orderBy('mois', 'desc')
                ->take(12)
                ->get();
    
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du calcul du chiffre d\'affaires mensuel.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Produits les plus vendus
     */
    public function produitsLesPlusVendus()
    {
        $checkRole = $this->checkAdminRole();
        if ($checkRole) {
            return $checkRole;
        }

        try {
            $produits = ArticleCommande::select(
                    'product_id',
                    'nom_produit',
                    DB::raw('SUM(quantite) as quantite_totale')
                )
                ->groupBy('product_id', 'nom_produit')
                ->orderByDesc('quantite_totale')
                ->take(5)
                ->get();

            return response()->json($produits);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des produits les plus vendus.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Répartition des commandes par statut
     */
    public function commandesParStatut()
    {
        $checkRole = $this->checkAdminRole();
        if ($checkRole) {
            return $checkRole;
        }

        try {
            $stats = Commande::select('statut', DB::raw('COUNT(*) as total'))
                ->groupBy('statut')
                ->get()
                ->pluck('total', 'statut');

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des commandes par statut.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Nombre de nouveaux clients (par mois)
     */
    public function nouveauxClientsParMois()
    {
        $checkRole = $this->checkAdminRole();
        if ($checkRole) {
            return $checkRole;
        }
    
        try {
            $clients = User::where('role', 'client')
                ->select(
                    DB::raw("TO_CHAR(created_at, 'YYYY-MM') as mois"),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('mois')
                ->orderBy('mois', 'desc')
                ->take(12)
                ->get();
    
            return response()->json($clients);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des nouveaux clients.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    

    /**
     * Performances des promotions (nombre de ventes en promo)
     */
    public function performancesPromotions()
    {
        $checkRole = $this->checkAdminRole();
        if ($checkRole) {
            return $checkRole;
        }

        try {
            $ventesPromos = ArticleCommande::whereHas('product', function ($query) {
                    $query->where('is_promotion', true);
                })
                ->select('product_id', 'nom_produit', DB::raw('SUM(quantite) as total_vendus'))
                ->groupBy('product_id', 'nom_produit')
                ->orderByDesc('total_vendus')
                ->get();

            return response()->json($ventesPromos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des performances des promotions.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
