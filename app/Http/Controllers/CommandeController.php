<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Mail\FactureCommandeMail;
use Illuminate\Support\Facades\Mail;

class CommandeController extends Controller
{
    /**
     * Liste les commandes du client connecté
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            $commandes = Commande::with(['articlesCommandes.product'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Commandes récupérées avec succès',
                'data' => $commandes,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Affiche une commande spécifique
     */
    public function show(Commande $commande)
    {
        try {
            $user = Auth::user();
            
            // Vérifier que la commande appartient au client
            if ($commande->user_id !== $user->id) {
                return response()->json([
                    'error' => 'Accès interdit.'
                ], 403);
            }

            $commande->load(['articlesCommandes.product']);

            return response()->json([
                'message' => 'Commande récupérée avec succès',
                'data' => $commande,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Crée une nouvelle commande
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // Validation simple
            $validator = Validator::make($request->all(), [
                'articles' => 'required|array|min:1',
                'articles.*.product_id' => 'required|exists:products,id',
                'articles.*.quantite' => 'required|integer|min:1',
                'mode_paiement' => 'required|in:especes,en_ligne',
                'adresse_livraison' => 'required|string|max:500',
                'telephone_livraison' => 'required|string|max:20',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()->all(),
                ], 400);
            }

            DB::beginTransaction();

            $montantTotal = 0;
            $articlesData = [];

            // Vérifier les produits et calculer le total
            foreach ($request->articles as $article) {
                $product = Product::find($article['product_id']);
                
                if (!$product) {
                    DB::rollback();
                    return response()->json([
                        'message' => 'Produit non trouvé',
                    ], 404);
                }

                // Vérifier le stock
                if ($product->stock < $article['quantite']) {
                    DB::rollback();
                    return response()->json([
                        'message' => 'Stock insuffisant pour ' . $product->name,
                    ], 400);
                }

                $prixTotal = $product->price * $article['quantite'];
                $montantTotal += $prixTotal;

                $articlesData[] = [
                    'product_id' => $product->id,
                    'nom_produit' => $product->name,
                    'prix_produit' => $product->price,
                    'quantite' => $article['quantite'],
                    'prix_total' => $prixTotal,
                ];
            }

            // Créer la commande
            $commande = Commande::create([
                'numero_commande' => Commande::genererNumeroCommande(),
                'user_id' => $user->id,
                'statut' => 'en_attente',
                'montant_total' => $montantTotal,
                'mode_paiement' => $request->mode_paiement,
                'adresse_livraison' => $request->adresse_livraison,
                'telephone_livraison' => $request->telephone_livraison,
                'notes' => $request->notes,
            ]);

            // Créer les articles et mettre à jour les stocks
            foreach ($articlesData as $articleData) {
                $commande->articlesCommandes()->create($articleData);
                
                // Décrémenter le stock
                $product = Product::find($articleData['product_id']);
                $product->decrement('stock', $articleData['quantite']);
            }

            DB::commit();
            $commande->load(['articlesCommandes', 'user']);

            // Debug pour vérifier
            //\Log::info('User chargé: ' . ($commande->user ? $commande->user->name : 'NULL'));
            
            Mail::to($user->email)->send(new FactureCommandeMail($commande));
            
            $commande->load(['articlesCommandes.product']);

            return response()->json([
                'message' => 'Commande créée avec succès',
                'data' => $commande,
            ], 201);

        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Annule une commande
     */
    public function annuler(Commande $commande)
    {
        try {
            $user = Auth::user();
            
            // Vérifier que la commande appartient au client
            if ($commande->user_id !== $user->id) {
                return response()->json([
                    'error' => 'Accès interdit.'
                ], 403);
            }

            // Vérifier si elle peut être annulée
            if (!$commande->peutEtreAnnulee()) {
                return response()->json([
                    'message' => 'Cette commande ne peut plus être annulée.'
                ], 400);
            }

            DB::beginTransaction();

            // Remettre les produits en stock
            foreach ($commande->articlesCommandes as $article) {
                $product = Product::find($article->product_id);
                if ($product) {
                    $product->increment('stock', $article->quantite);
                }
            }

            // Mettre à jour le statut
            $commande->update(['statut' => 'annulee']);

            DB::commit();

            return response()->json([
                'message' => 'Commande annulée avec succès',
                'data' => $commande,
            ], 200);

        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}