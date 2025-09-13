<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class FactureCommandeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $commande;

    public function __construct($commande)
    {
        $this->commande = $commande;
        //log::info('Construction de FactureCommandeMail pour commande: ' . $commande->numero_commande);
    }

    public function build()
    {
        try {
           // \Log::info('Début de build() pour commande: ' . $this->commande->numero_commande);
            
            // Vérifier que les relations sont chargées
            if (!$this->commande->relationLoaded('articlesCommandes')) {
               // \Log::warning('Relation articlesCommandes non chargée, chargement en cours...');
                $this->commande->load('articlesCommandes');
            }
            
            if (!$this->commande->relationLoaded('user')) {
              //  \Log::warning('Relation user non chargée, chargement en cours...');
                $this->commande->load('user');
            }

            // Génération du PDF avec gestion d'erreur
           // \Log::info('Génération du PDF...');
            $pdf = Pdf::loadView('pdf.facture', ['commande' => $this->commande]);
           // \Log::info('PDF généré avec succès');

            return $this->subject('Votre facture - Commande #' . $this->commande->numero_commande)
                        ->view('emails.commandes.simple')
                        ->with(['commande' => $this->commande])
                        ->attachData($pdf->output(), 'facture_' . $this->commande->numero_commande . '.pdf', [
                            'mime' => 'application/pdf',
                        ]);
                        
        } catch (\Exception $e) {
           // \Log::error('Erreur dans build(): ' . $e->getMessage());
           // \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}