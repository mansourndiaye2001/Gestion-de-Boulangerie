<!-- resources/views/emails/commandes/simple.blade.php -->
<p>Bonjour {{ $commande->user->name }},</p>
<p>Veuillez trouver ci-joint la facture de votre commande #{{ $commande->numero_commande }}.</p>
<p>Merci pour votre commande !</p>
