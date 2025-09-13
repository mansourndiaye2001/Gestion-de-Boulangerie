<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /**
     * Nom de la table
     */
    protected $table = 'notifications';

    /**
     * La clé primaire est un UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    
    const CREATED_AT = 'cree_le';
    const UPDATED_AT = 'modifie_le';

   
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id', 
        'donnees',
        'lu_le',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'donnees' => 'array',
        'lu_le' => 'datetime',
        'cree_le' => 'datetime',
        'modifie_le' => 'datetime',
    ];

    /**
     * Relation polymorphique vers l'entité notifiable
     */
    public function notifiable()
    {
        return $this->morphTo('notifiable', 'notifiable_type', 'notifiable_id');
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeNonLues($query)
    {
        return $query->whereNull('lu_le');
    }

    /**
     * Scope pour les notifications lues
     */
    public function scopeLues($query)
    {
        return $query->whereNotNull('lu_le');
    }

    /**
     * Marquer comme lue
     */
    public function marquerCommeLue()
    {
        $this->update(['lu_le' => now()]);
    }

    /**
     * Vérifier si la notification est lue
     */
    public function estLue()
    {
        return !is_null($this->lu_le);
    }

    /**
     * Obtenir le titre de la notification
     */
    public function getTitreAttribute()
    {
        return $this->donnees['titre'] ?? 'Notification';
    }

    /**
     * Obtenir le message de la notification
     */
    public function getMessageAttribute()
    {
        return $this->donnees['message'] ?? '';
    }
}