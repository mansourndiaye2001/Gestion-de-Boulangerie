<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Inscription d'un utilisateur
     */
    public function register(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'sometimes|in:admin,employe,client',
            'telephone' => 'nullable|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Création de l'utilisateur
            $user = User::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'client',
                'telephone' => $request->telephone,
            ]);

            // Création du token
            $token = $user->createToken('BoulangerieApp')->plainTextToken;

            return response()->json([
                'user' => $user,
                'role' => $user->role,
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'inscription : ' . $e->getMessage());

            return response()->json([
                'error' => 'Une erreur est survenue lors de l\'inscription.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Connexion de l'utilisateur
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Identifiants incorrects.'], 401);
        }

        $token = $user->createToken('BoulangerieApp')->plainTextToken;

        return response()->json([
            'user' => $user,
            'role' => $user->role,
            'token' => $token,
        ], 200);
    }

    /**
     * Récupérer tous les utilisateurs (uniquement pour l'admin)
     */
    public function getAll()
    {
        // Vérifier si l'utilisateur est un admin
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        $users = User::all();

        return response()->json([
            'users' => $users,
        ], 200);
    }

    /**
     * Supprimer un utilisateur (uniquement pour l'admin)
     */
    public function deleteUser($id)
    {
        // Vérifier si l'utilisateur est un admin
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'message' => 'Utilisateur supprimé avec succès.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'L\'utilisateur n\'a pas pu être supprimé.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer un utilisateur par son ID
     */
    public function getUserById($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json([
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Utilisateur non trouvé.',
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
