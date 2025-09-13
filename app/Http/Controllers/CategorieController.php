<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 

class CategorieController extends Controller
{


    public function index()
    {
    
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Accès interdit. Vous devez être administrateur.'], 403);
        }

      
        $categories = Categorie::all();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
    
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Accès interdit. Vous devez être administrateur.'], 403);
        }

 
        $request->validate([
            'name' => 'required|string|max:255',
        
        ]);

       
        $category = Categorie::create([
            'name' => $request->name,
          
        ]);

        return response()->json($category, 201); 
    }

    public function update(Request $request, $id)
    {
      
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Accès interdit. Vous devez être administrateur.'], 403);
        }

     
        $category = Categorie::findOrFail($id);

        // Valider les données
        $request->validate([
            'name' => 'required|string|max:255',
          
        ]);

        
        $category->update([
            'name' => $request->name,
       
        ]);

        return response()->json($category);  
    }

    public function destroy($id)
    {
       
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Accès interdit. Vous devez être administrateur.'], 403);
        }

       
        $category = Categorie::findOrFail($id);

      
        $category->delete();

        return response()->json(['message' => 'Catégorie supprimée avec succès.']);
    }
}
