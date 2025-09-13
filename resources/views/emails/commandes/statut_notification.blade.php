<!-- resources/views/emails/commandes/statut_notification.blade.php -->
<p>Bonjour {{ $commande->user->name }},</p>

<p>Nous vous informons que le statut de votre commande #{{ $commande->numero_commande }} a été mis à jour.</p>

<p><strong>Ancien statut :</strong> {{ $ancienStatut }}</p>
<p><strong>Nouveau statut :</strong> {{ $nouveauStatut }}</p>

<p>Merci de votre confiance !</p>
<p>Cordialement,</p>
<p>L'équipe de votre boutique</p>
