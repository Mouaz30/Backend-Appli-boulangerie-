<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'total',
        'statut',
        'adresse_livraison',
        'methode_paiement',
        'date_livraison',
        'numero_suivi'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'date_livraison' => 'datetime'
    ];

    const STATUTS = [
        'en_attente' => 'En attente',
        'en_preparation' => 'En préparation',
        'prete' => 'Prête',
        'en_livraison' => 'En livraison',
        'livree' => 'Livrée',
        'annulee' => 'Annulée'
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class);
    }

    public function elements()
    {
        return $this->hasMany(ElementCommande::class);
    }

    public function facture()
    {
        return $this->hasOne(Facture::class);
    }

    public function getStatutLibelleAttribute()
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function scopePourUtilisateur($query, $userId)
    {
        return $query->where('utilisateur_id', $userId);
    }

    public function scopeParStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}