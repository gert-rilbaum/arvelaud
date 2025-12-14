<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Firma extends Model
{
    protected $table = 'firmad';
    
    protected $fillable = [
        'nimi',
        'registrikood',
        'gmail_labels',
        'email',
        'telefon',
        'aadress',
        'merit_api_id',
        'merit_api_key',
        'aktiivne',
    ];

    protected $casts = [
        'aktiivne' => 'boolean',
        'gmail_labels' => 'array',
    ];

    /**
     * Kas firmal on see label?
     */
    public function hasLabel(string $label): bool
    {
        return in_array($label, $this->gmail_labels ?? []);
    }

    /**
     * Labelite arv
     */
    public function getLabelsCountAttribute(): int
    {
        return count($this->gmail_labels ?? []);
    }
    
    /**
     * Firma kirjad
     */
    public function kirjad(): HasMany
    {
        return $this->hasMany(Kiri::class);
    }
    
    /**
     * Firma manused (läbi kirjade)
     */
    public function manused(): HasMany
    {
        return $this->hasMany(Manus::class);
    }
    
    /**
     * Firma tegevuste logi
     */
    public function tegevused(): HasMany
    {
        return $this->hasMany(Tegevus::class);
    }
    
    /**
     * Ainult aktiivsed firmad
     */
    public function scopeAktiivne($query)
    {
        return $query->where('aktiivne', true);
    }
    
    /**
     * Uute kirjade arv
     */
    public function getUusiKirjuAttribute(): int
    {
        return $this->kirjad()->where('staatus', 'uus')->count();
    }
    
    /**
     * Töötlemata arvete arv
     */
    public function getTootlemataArveidAttribute(): int
    {
        return $this->manused()
            ->where('on_arve', true)
            ->whereIn('arve_staatus', ['tuvastamata', 'tuvastatud'])
            ->count();
    }
}
