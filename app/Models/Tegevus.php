<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tegevus extends Model
{
    protected $table = 'tegevused';
    
    protected $fillable = [
        'firma_id',
        'user_id',
        'kiri_id',
        'manus_id',
        'tyyp',
        'kirjeldus',
        'meta',
    ];
    
    protected $casts = [
        'meta' => 'array',
    ];
    
    /**
     * Tegevuse tüüpide nimed
     */
    const TYYP_NIMED = [
        'kiri_saadud' => 'Kiri saadud',
        'kiri_loetud' => 'Kiri loetud',
        'kiri_vastatud' => 'Vastatud',
        'kiri_teisaldatud' => 'Teisaldatud',
        'arve_tuvastatud' => 'Arve tuvastatud',
        'arve_sisestatud' => 'Arve sisestatud Aktivasse',
        'markus_lisatud' => 'Märkus lisatud',
        'staatus_muudetud' => 'Staatus muudetud',
        'manus_arve' => 'Märgitud arveks',
        'manus_muu' => 'Märgitud muuks',
    ];
    
    /**
     * Tegevuse firma
     */
    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }
    
    /**
     * Tegevuse kasutaja
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Tegevusega seotud kiri
     */
    public function kiri(): BelongsTo
    {
        return $this->belongsTo(Kiri::class);
    }
    
    /**
     * Tegevusega seotud manus
     */
    public function manus(): BelongsTo
    {
        return $this->belongsTo(Manus::class);
    }
    
    /**
     * Tegevuse tüübi inimloetav nimi
     */
    public function getTyypNimiAttribute(): string
    {
        return self::TYYP_NIMED[$this->tyyp] ?? $this->tyyp;
    }
    
    /**
     * Loo uus tegevus lihtsalt
     */
    public static function lisa(
        int $firmaId,
        string $tyyp,
        ?string $kirjeldus = null,
        ?int $kiriId = null,
        ?int $manusId = null,
        ?array $meta = null
    ): self {
        return self::create([
            'firma_id' => $firmaId,
            'user_id' => auth()->id(),
            'kiri_id' => $kiriId,
            'manus_id' => $manusId,
            'tyyp' => $tyyp,
            'kirjeldus' => $kirjeldus,
            'meta' => $meta,
        ]);
    }
}
