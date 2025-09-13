<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\User;
use App\Notifications\CommandeStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use App\Mail\NotificationStatutCommandeMail;
class EmployeCommandeController extends Controller
{
    private function checkEmployeRole()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié.'], 401);
        }
        
        if (!in_array($user->role, ['employe', 'admin'])) {
            return response()->json(['error' => 'Accès interdit. Vous devez être employé ou administrateur.'], 403);
        }
        return null; 
    }

    /**
     * Liste toutes les commandes pour les employés
     */
    public function index(Request $request)
    {
        try {
            $checkRole = $this->checkEmployeRole();
            if ($checkRole) {
                return $checkRole;
            }

            $query = Commande::with(['user', 'articlesCommandes.product', 'employe'])
                ->orderBy('created_at', 'desc');

            // Filtrer par statut si spécifié
            if ($request->has('statut') && $request->statut !== 'tous') {
                $query->where('statut', $request->statut);
            }

            // Filtrer par date si spécifié
            if ($request->has('date_debut') && $request->date_debut) {
                $query->whereDate('created_at', '>=', $request->date_debut);
            }

            if ($request->has('date_fin') && $request->date_fin) {
                $query->whereDate('created_at', '<=', $request->date_fin);
            }

            $commandes = $query->paginate(20);

            // Statistiques rapides
            $statistiques = [
                'total' => Commande::count(),
                'en_attente' => Commande::where('statut', 'en_attente')->count(),
                'confirmee' => Commande::where('statut', 'confirmee')->count(),
                'en_preparation' => Commande::where('statut', 'en_preparation')->count(),
                'prete' => Commande::where('statut', 'prete')->count(),
                'en_livraison' => Commande::where('statut', 'en_livraison')->count(),
                'livree' => Commande::where('statut', 'livree')->count(),
                'annulee' => Commande::where('statut', 'annulee')->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Commandes récupérées avec succès',
                'data' => $commandes,
                'statistiques' => $statistiques,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Erreur dans EmployeCommandeController@index: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Affiche une commande spécifique avec tous ses détails
     */
    public function show(Commande $commande)
    {
        try {
            $checkRole = $this->checkEmployeRole();
            if ($checkRole) {
                return $checkRole;
            }

            $commande->load(['user', 'articlesCommandes.product', 'employe']);

            return response()->json([
                'success' => true,
                'message' => 'Commande récupérée avec succès',
                'data' => $commande,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Erreur dans EmployeCommandeController@show: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Met à jour le statut d'une commande et envoie automatiquement une notification
     */
    public function updateStatut(Request $request, Commande $commande)
    {
        try {
            $checkRole = $this->checkEmployeRole();
            if ($checkRole) {
                return $checkRole;
            }

  
            $validator = Validator::make($request->all(), [
                'nouveau_statut' => 'required|in:en_attente,confirmee,en_preparation,prete,en_livraison,livree,annulee',
                'commentaire_employe' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()->all(),
                ], 400);
            }

            $ancienStatut = $commande->statut;
            $nouveauStatut = $request->nouveau_statut;

            // Debug : Afficher les statuts
            Log::info("Tentative de changement de statut", [
                'commande_id' => $commande->id,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $nouveauStatut
            ]);

            // Vérifier si le changement de statut est valide
            if (!$this->statutChangeValide($ancienStatut, $nouveauStatut)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Changement de statut invalide',
                    'error' => "Impossible de passer de '$ancienStatut' à '$nouveauStatut'",
                ], 400);
            }

            DB::beginTransaction();

            // Mettre à jour la commande
            $commande->update([
                'statut' => $nouveauStatut,
                'commentaire_employe' => $request->commentaire_employe,
                'employe_id' => Auth::id(),
                'updated_at' => now(),
            ]);
              // ENVOI AUTOMATIQUE DE LA NOTIFICATION ICI
        $notificationEnvoyee = $this->envoyerNotificationClient($commande, $ancienStatut, $nouveauStatut);

        // Envoi de l'email de notification au client
        Mail::to($commande->user->email)->send(new NotificationStatutCommandeMail($commande, $ancienStatut, $nouveauStatut));

        // Enregistrer l'historique du changement
        $this->enregistrerHistorique($commande, $ancienStatut, $nouveauStatut, $request->commentaire_employe);

            // ENVOI AUTOMATIQUE DE LA NOTIFICATION ICI
            $notificationEnvoyee = $this->envoyerNotificationClient($commande, $ancienStatut, $nouveauStatut);

            // Enregistrer l'historique du changement
            $this->enregistrerHistorique($commande, $ancienStatut, $nouveauStatut, $request->commentaire_employe);

            DB::commit();

            $commande->load(['user', 'articlesCommandes.product', 'employe']);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => $commande,
                'notification_envoyee' => $notificationEnvoyee,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $nouveauStatut,
            ], 200);

        } catch (\Throwable $th) {
            DB::rollback();
            Log::error('Erreur dans EmployeCommandeController@updateStatut: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifie si un changement de statut est valide
     */
    private function statutChangeValide($ancienStatut, $nouveauStatut)
    {
        $transitionsValides = [
            'en_attente' => ['confirmee', 'en_preparation', 'annulee'],
            'confirmee' => ['en_preparation', 'annulee'],
            'en_preparation' => ['prete', 'annulee'],
            'prete' => ['en_livraison', 'livree'], // livree pour retrait sur place
            'en_livraison' => ['livree'],
            'livree' => [], // Statut final
            'annulee' => [], // Statut final
        ];

        return in_array($nouveauStatut, $transitionsValides[$ancienStatut] ?? []);
    }

    /**
     * Envoie automatiquement une notification au client
     */
    private function envoyerNotificationClient($commande, $ancienStatut, $nouveauStatut)
    {
        try {
            $client = $commande->user;
            
            if (!$client || !$client->email) {
                Log::warning('Client non trouvé ou email manquant', ['commande_id' => $commande->id]);
                return false;
            }

            // Envoi de la notification (décommentez quand prêt)
            // $client->notify(new CommandeStatusChanged($commande, $ancienStatut, $nouveauStatut));
            
            Log::info('Notification envoyée', [
                'client_email' => $client->email,
                'commande_id' => $commande->id,
                'nouveau_statut' => $nouveauStatut
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Erreur envoi notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enregistre l'historique des changements de statut
     */
    private function enregistrerHistorique($commande, $ancienStatut, $nouveauStatut, $commentaire = null)
    {
        try {
            // Vérifier si la table existe avant d'insérer
            if (DB::getSchemaBuilder()->hasTable('commande_historiques')) {
                DB::table('commande_historiques')->insert([
                    'commande_id' => $commande->id,
                    'ancien_statut' => $ancienStatut,
                    'nouveau_statut' => $nouveauStatut,
                    'employe_id' => Auth::id(),
                    'commentaire' => $commentaire,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                Log::warning('Table commande_historiques non trouvée');
            }
        } catch (\Exception $e) {
            Log::error('Erreur enregistrement historique: ' . $e->getMessage());
        }
    }

    /**
     * Assigner une commande à un employé
     */
    public function assigner(Request $request, Commande $commande)
    {
        try {
            $checkRole = $this->checkEmployeRole();
            if ($checkRole) {
                return $checkRole;
            }

            $validator = Validator::make($request->all(), [
                'employe_id' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()->all(),
                ], 400);
            }

            // Vérifier que l'utilisateur assigné est bien un employé
            if ($request->employe_id) {
                $employe = User::find($request->employe_id);
                if (!$employe || !in_array($employe->role, ['employe', 'admin'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Employé invalide',
                    ], 400);
                }
            }

            $commande->update([
                'employe_id' => $request->employe_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande assignée avec succès',
                'data' => $commande->load(['user', 'employe', 'articlesCommandes.product']),
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Erreur dans EmployeCommandeController@assigner: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir la liste des employés pour assignation
     */
    public function getEmployes()
    {
        try {
            $checkRole = $this->checkEmployeRole();
            if ($checkRole) {
                return $checkRole;
            }

            $employes = User::whereIn('role', ['employe', 'admin'])
                ->select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Liste des employés récupérée',
                'data' => $employes,
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Erreur dans EmployeCommandeController@getEmployes: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}