<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'stock',
        'category_id',
        'is_promotion',
        'promotion_start_date',
        'promotion_end_date',
        'promotion_price',
    ];

    protected $casts = [
        'is_promotion' => 'boolean',
        'promotion_start_date' => 'date',
        'promotion_end_date' => 'date',
        'price' => 'decimal:2',
        'promotion_price' => 'decimal:2',
    ];

    // Relation : Un produit appartient à une catégorie
    public function category()
    {
        return $this->belongsTo(Categorie::class, 'category_id');
    }

    // Validation des promotions lors de la sauvegarde
    public static function boot()
    {
        parent::boot();
    
        static::saving(function ($product) {
            // Si 'is_promotion' est true, les autres champs de promotion doivent être renseignés
            if ($product->is_promotion) {
                if (is_null($product->promotion_start_date) || 
                    is_null($product->promotion_end_date) || 
                    is_null($product->promotion_price)) {
                    throw new \Exception('Lorsque la promotion est activée, les champs de promotion doivent être renseignés.');
                }
            }
        });
    }
    
    // Accesseur pour déterminer si la promotion est actuellement active
    public function getIsCurrentlyOnPromotionAttribute()
    {
        if (!$this->is_promotion) {
            return false;
        }

        $now = Carbon::now();
        return $now->between($this->promotion_start_date, $this->promotion_end_date);
    }

    // Accesseur pour obtenir le prix effectif (avec ou sans promotion)
    public function getCurrentPriceAttribute()
    {
        // Si le produit est actuellement en promotion, retourner le prix promotionnel
        if ($this->is_currently_on_promotion) {
            return $this->promotion_price;
        }

        return $this->price; // Sinon, retourner le prix normal
    }

    // Accesseur pour afficher les informations de promotion
    public function getPromotionInfoAttribute()
    {
        if (!$this->is_promotion) {
            return null;
        }

        return [
            'is_active' => $this->is_currently_on_promotion,
            'start_date' => $this->promotion_start_date->format('Y-m-d'),
            'end_date' => $this->promotion_end_date->format('Y-m-d'),
            'original_price' => $this->getOriginal('price'),
            'promotion_price' => $this->promotion_price,
            'discount_percentage' => round((($this->getOriginal('price') - $this->promotion_price) / $this->getOriginal('price')) * 100, 2)
        ];
    }
}