<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture #{{ $commande->numero_commande ?? 'N/A' }}</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 14px; 
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left;
        }
        th { 
            background-color: #f4f4f4; 
            font-weight: bold;
        }
        .total-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FACTURE</h1>
        <h2>Commande #{{ $commande->numero_commande ?? 'Non défini' }}</h2>
    </div>

    <div class="info-section">
        <!-- Debug: vérifier si user existe -->
        @if($commande->user)
            <p><strong>Nom du client :</strong> {{ $commande->user->nom }}</p>
            <p><strong>Prenom du client :</strong> {{ $commande->user->prenom }}</p>
            <p><strong>Email :</strong> {{ $commande->user->email }}</p>
        @else
            <p style="color: red;"><strong>ERREUR: Informations client non chargées</strong></p>
           / <p><strong>User ID :</strong> {{ $commande->user_id ?? 'Non défini' }}</p>
        @endif
        
        <p><strong>Adresse de livraison :</strong> {{ $commande->adresse_livraison ?? 'Non définie' }}</p>
        <p><strong>Téléphone :</strong> {{ $commande->telephone_livraison ?? 'Non défini' }}</p>
        <p><strong>Date de commande :</strong> {{ $commande->created_at ? $commande->created_at->format('d/m/Y H:i') : 'Non définie' }}</p>
        <p><strong>Mode de paiement :</strong> {{ ucfirst($commande->mode_paiement ?? 'Non défini') }}</p>
        @if($commande->notes)
        <p><strong>Notes :</strong> {{ $commande->notes }}</p>
        @endif
    </div>

    @if($commande->articlesCommandes && $commande->articlesCommandes->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Quantité</th>
                <th>Prix unitaire (FCFA)</th>
                <th>Total (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commande->articlesCommandes as $article)
                <tr>
                    <td>{{ $article->nom_produit ?? 'Produit non défini' }}</td>
                    <td>{{ $article->quantite ?? 0 }}</td>
                    <td>{{ number_format($article->prix_produit ?? 0, 0, ',', ' ') }}</td>
                    <td>{{ number_format($article->prix_total ?? 0, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3"><strong>TOTAL À PAYER</strong></td>
                <td><strong>{{ number_format($commande->montant_total ?? 0, 0, ',', ' ') }} FCFA</strong></td>
            </tr>
        </tfoot>
    </table>
    @else
    <p style="color: red;"><strong>Aucun article trouvé dans cette commande.</strong></p>
    @endif

    <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #666;">
        <p>Merci pour votre confiance !</p>
        <p>Cette facture a été générée automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>