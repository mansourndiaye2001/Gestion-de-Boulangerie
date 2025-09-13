<?php
namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationStatutCommandeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $commande;
    public $ancienStatut;
    public $nouveauStatut;

    public function __construct(Commande $commande, $ancienStatut, $nouveauStatut)
    {
        $this->commande = $commande;
        $this->ancienStatut = $ancienStatut;
        $this->nouveauStatut = $nouveauStatut;
    }

    public function build()
    {
        return $this->subject('Mise à jour du statut de votre commande #' . $this->commande->numero_commande)
                    ->view('emails.commandes.statut_notification');
    }
}
