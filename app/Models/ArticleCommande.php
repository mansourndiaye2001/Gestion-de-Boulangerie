<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleCommande extends Model
{
    use HasFactory;

    protected $table = 'articles_commandes';

    protected $fillable = [
        'commande_id',
        'product_id',
        'nom_produit',
        'prix_produit',
        'quantite',
        'prix_total',
    ];

    protected $casts = [
        'prix_produit' => 'decimal:2',
        'prix_total' => 'decimal:2',
    ];

    /**
     * Relation avec la commande
     */
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    /**
     * Relation avec le produit
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calcule automatiquement le prix total
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($articleCommande) {
            $articleCommande->prix_total = $articleCommande->quantite * $articleCommande->prix_produit;
        });

        static::updating(function ($articleCommande) {
            $articleCommande->prix_total = $articleCommande->quantite * $articleCommande->prix_produit;
        });
    }
}