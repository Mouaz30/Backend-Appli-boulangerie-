<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description', 
        'prix',
        'categorie_id',
        'stock',
        'image',
        'allergenes',
        'est_actif'
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'stock' => 'integer',
        'est_actif' => 'boolean',
        'allergenes' => 'array'
    ];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'produit_promotion')
                    ->withTimestamps();
    }

    public function elementsCommande()
    {
        return $this->hasMany(ElementCommande::class);
    }

    public function scopeActif($query)
    {
        return $query->where('est_actif', true);
    }

    public function scopeAvecPromotionsActives($query)
    {
        return $query->whereHas('promotions', function($q) {
            $q->where('est_active', true)
              ->where('date_debut', '<=', now())
              ->where('date_fin', '>=', now());
        });
    }

    public function getEstEnStockAttribute()
    {
        return $this->stock > 0;
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
}