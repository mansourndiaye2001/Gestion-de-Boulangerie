<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;  

class ProductController extends Controller
{
  
    private function checkAdminRole()
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Accès interdit. Vous devez être administrateur.'], 403);
        }
        return null; 
    }
    
    private function checkAdminRole1()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié.'], 401);
        }
        
        if (!in_array($user->role, ['employe', 'admin', 'client'])) {
            return response()->json(['error' => 'Accès interdit. Vous devez être employé ou administrateur.'], 403);
        }
        return null; 
    }

    /**
     * Validation personnalisée pour les promotions
     */
    private function getValidationRules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'category_id' => 'required|exists:categories,id',
            'is_promotion' => 'nullable|boolean',
            'promotion_start_date' => 'nullable|date|required_if:is_promotion,true',
            'promotion_end_date' => 'nullable|date|after_or_equal:promotion_start_date|required_if:is_promotion,true',
          
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    private function getValidationMessages()
    {
        return [
            'promotion_start_date.required_if' => 'La date de début de promotion est obligatoire quand la promotion est activée.',
            'promotion_end_date.required_if' => 'La date de fin de promotion est obligatoire quand la promotion est activée.',
            'promotion_end_date.after_or_equal' => 'La date de fin doit être supérieure ou égale à la date de début.',
            'promotion_price.required_if' => 'Le prix promotionnel est obligatoire quand la promotion est activée.',
         
        ];
    }
    
    public function index()
    {
        $checkRole = $this->checkAdminRole();
        if ($checkRole) {
            return $checkRole;
        }

        $products = Product::all();
        return response()->json([
            'data' => $products,
        ], 200)->header('Content-type', 'application/json');
    }
    
    public function store(Request $request)
    {
        $checkRole = $this->checkAdminRole();
        if ($checkRole) {
            return $checkRole;
        }
        
        try {
            $validator = Validator::make(
                $request->all(), 
                $this->getValidationRules(),
                $this->getValidationMessages()
            );
            
            if ($validator->fails()) {
                return response([
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()->all(),
                ], 400)->header('Content-type', 'application/json');
            }
            
            // Gestion de l'image
            $imagePath = null;
            $imageUrl = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('uploads', $filename, 'public');
                
                $imagePath = $filename;
                $imageUrl = url('files/uploads/' . $filename);
            }
            
            // Préparation des données du produit
            $productData = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'stock' => $request->stock,
                'category_id' => $request->category_id,
                'image' => $imagePath,
            ];
    
            // Ajout des champs de promotion si nécessaires
            if ($request->has('is_promotion') && $request->is_promotion) {
                $productData['is_promotion'] = true;
                $productData['promotion_start_date'] = $request->promotion_start_date;
                $productData['promotion_end_date'] = $request->promotion_end_date;
                $productData['promotion_price'] = $request->promotion_price;
            } else {
                $productData['is_promotion'] = false;
                $productData['promotion_start_date'] = null;
                $productData['promotion_end_date'] = null;
                $productData['promotion_price'] = null;
            }
            
            // Création du produit
            $product = Product::create($productData);
            
            return response([
                'message' => 'Produit créé avec succès',
                'data' => $product,
                'image_url' => $imageUrl
            ], 201);
            
        } catch (\Throwable $th) {
            return response([
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500)->header('Content-type', 'application/json');
        }
    }
    
    /**
 * Validation personnalisée pour vérifier le prix promotionnel
 */



    
    public function update(Request $request, Product $product)
    {
        $checkRole = $this->checkAdminRole();
        if ($checkRole) {
            return $checkRole;
        }
    
        try {
            // Normaliser la valeur is_promotion
            if ($request->has('is_promotion')) {
                $request->merge([
                    'is_promotion' => filter_var($request->is_promotion, FILTER_VALIDATE_BOOLEAN)
                ]);
            }

            $validator = Validator::make(
                $request->all(), 
                $this->getValidationRules(),
                $this->getValidationMessages()
            );
    
            if ($validator->fails()) {
                return response([
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()->all(),
                ], 422)->header('Content-type', 'application/json');
            }

            // Validation personnalisée du prix promotionnel
     
    
            // Gestion de l'image
            if ($request->hasFile('image')) {
                if ($product->image && Storage::disk('public')->exists('uploads/' . $product->image)) {
                    Storage::disk('public')->delete('uploads/' . $product->image);
                }
    
                $file = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('uploads', $filename, 'public');
    
                $product->image = $filename;
            }
    
            // Mise à jour des champs de base
            $product->name = $request->name;
            $product->description = $request->description;
            $product->price = (float) $request->price;
            $product->stock = (int) $request->stock;
            $product->category_id = (int) $request->category_id;
            $product->is_promotion = $request->is_promotion ? true : false;

            // Gestion des promotions
            if ($request->is_promotion) {
                $product->promotion_start_date = $request->promotion_start_date;
                $product->promotion_end_date = $request->promotion_end_date;
                $product->promotion_price = (float) $request->promotion_price;
            } else {
                $product->promotion_start_date = null;
                $product->promotion_end_date = null;
                $product->promotion_price = null;
            }

            $product->save();
    
            return response([
                'message' => 'Produit mis à jour avec succès',
                'data' => $product,
                'image_url' => $product->image ? url('files/uploads/' . $product->image) : null
            ], 200)->header('Content-type', 'application/json');
    
        } catch (\Exception $e) {
            return response([
                'message' => 'Erreur de validation',
                'error' => $e->getMessage(),
            ], 422)->header('Content-type', 'application/json');
        } catch (\Throwable $th) {
            return response([
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500)->header('Content-type', 'application/json');
        }
    }
    
    public function destroy(Product $product)
    {
        $checkRole = $this->checkAdminRole();
        if ($checkRole) {
            return $checkRole;
        }

        try {
            if ($product->image && Storage::disk('public')->exists('uploads/' . $product->image)) {
                Storage::disk('public')->delete('uploads/' . $product->image);
            }

            $product->delete();

            return response([
                'message' => 'Produit supprimé avec succès',
                'data' => $product,
            ], 200)->header('Content-type', 'application/json');
        } catch (\Throwable $th) {
            return response([
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500)->header('Content-type', 'application/json');
        }
    }

    public function catalogue(Request $request)
    {
        try {
            $query = Product::with('category')
                ->where('stock', '>', 0)
                ->where('is_promotion', false); 


            if ($request->has('recherche') && $request->recherche) {
                $recherche = $request->recherche;
                $query->where('name', 'like', "%{$recherche}%");
            }

            if ($request->has('category_id') && $request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            $produits = $query->orderBy('name', 'asc')->get();

            return response()->json([
                'message' => 'Catalogue récupéré avec succès',
                'data' => $produits,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
    
    public function show(Product $product)
    {
       $checkRole = $this->checkAdminRole1();
       if ($checkRole) {
           return $checkRole;
       }

       try {
           return response()->json($product, 200);
       } catch (\Throwable $th) {
           return response()->json([
               'message' => 'Erreur serveur',
               'error' => $th->getMessage(),
           ], 500);
       }
    }

   // Ajoutez cette fonction temporairement à votre ProductController pour diagnostiquer le problème


// Fonction corrigée pour les promotions
public function productsOnPromotion(Request $request)
{
    $checkRole = $this->checkAdminRole1();
    if ($checkRole) {
        return $checkRole;
    }
    
    try {
        $now = Carbon::now();
        
        // Requête simplifiée pour diagnostiquer
        $products = Product::with('category')
            ->where('is_promotion', 1) // Utiliser 1 au lieu de true
            ->whereDate('promotion_start_date', '<=', $now->toDateString()) // Comparaison par date seulement
            ->whereDate('promotion_end_date', '>=', $now->toDateString()) // Comparaison par date seulement
            ->where('stock', '>', 0)
            ->orderBy('name', 'asc')
            ->get();

        if ($products->isEmpty()) {
            // Test sans filtre de dates pour voir si c'est le problème
            $allPromotionProducts = Product::where('is_promotion', 1)->get([
                'id', 'name', 'is_promotion', 'promotion_start_date', 'promotion_end_date', 'stock'
            ]);
            
            return response()->json([
                'message' => 'Aucun produit en promotion actuellement.',
                'data' => [],
                'count' => 0,
                'debug' => [
                    'current_date' => $now->toDateString(),
                    'total_promotion_products' => $allPromotionProducts->count(),
                    'promotion_products' => $allPromotionProducts
                ]
            ], 200);
        }

        // Formatter les données
        $formattedProducts = $products->map(function ($product) {
            $discountPercentage = 0;
            if ($product->promotion_price && $product->price > 0) {
                $discountPercentage = round((($product->price - $product->promotion_price) / $product->price) * 100, 2);
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) $product->price,
                'promotion_price' => (float) $product->promotion_price,
                'stock' => (int) $product->stock,
                'category_id' => $product->category_id,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name ?? ''
                ] : null,
                'image' => $product->image ? url('files/uploads/' . $product->image) : null,
                'promotion_start_date' => $product->promotion_start_date,
                'promotion_end_date' => $product->promotion_end_date,
                'discount_percentage' => $discountPercentage,
                'savings' => (float) ($product->price - $product->promotion_price)
            ];
        });

        return response()->json([
            'message' => 'Produits en promotion récupérés avec succès',
            'data' => $formattedProducts,
        
          
        ], 200);

    } catch (\Throwable $th) {
        return response()->json([
            'message' => 'Erreur serveur',
            'error' => $th->getMessage(),
            'line' => $th->getLine(),
            'file' => basename($th->getFile())
        ], 500);
    }
}










    
    

}