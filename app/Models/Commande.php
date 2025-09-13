<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_commande',
        'user_id',
        'statut',
        'montant_total',
        'mode_paiement',
        'adresse_livraison',
        'telephone_livraison',
        'notes',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
    ];

    /**
     * Relation avec l'utilisateur (client)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les articles de la commande
     */
    public function articlesCommandes(): HasMany
    {
        return $this->hasMany(ArticleCommande::class);
    }

    /**
     * Génère un numéro de commande unique
     */
    public static function genererNumeroCommande(): string
    {
        $prefixe = 'CMD-';
        $timestamp = now()->format('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefixe . $timestamp . '-' . $random;
    }

    /**
     * Vérifie si la commande peut être annulée
     */
    public function peutEtreAnnulee(): bool
    {
        return in_array($this->statut, ['en_attente', 'confirmee']);
    }
   
   public function employe()
   {
       return $this->belongsTo(User::class, 'employe_id');
   }


    /**
     * Statuts possibles
     */
    public static function getStatuts(): array
    {
        return [
            'en_attente' => 'En attente',
            'confirmee' => 'Confirmée',
            'en_preparation' => 'En préparation',
            'prete' => 'Prête',
            'livree' => 'Livrée',
            'annulee' => 'Annulée',
        ];
    }

    /**
     * Obtient le libellé du statut
     */
    public function getLibelleStatutAttribute(): string
    {
        return self::getStatuts()[$this->statut] ?? $this->statut;
    }
}