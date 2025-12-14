<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manus extends Model
{
    protected $table = 'manused';
    
    protected $fillable = [
        'kiri_id',
        'firma_id',
        'gmail_attachment_id',
        'failinimi',
        'mime_type',
        'suurus',
        'salvestatud_path',
        'on_arve',
        'ocr_andmed',
        'arve_staatus',
    ];
    
    protected $casts = [
        'on_arve' => 'boolean',
        'ocr_andmed' => 'array',
    ];
    
    /**
     * Manuse kiri
     */
    public function kiri(): BelongsTo
    {
        return $this->belongsTo(Kiri::class);
    }
    
    /**
     * Manuse firma
     */
    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }
    
    /**
     * Manusega seotud tegevused
     */
    public function tegevused(): HasMany
    {
        return $this->hasMany(Tegevus::class);
    }
    
    /**
     * Ainult PDF failid
     */
    public function scopePdf($query)
    {
        return $query->where(function($q) {
            $q->where('mime_type', 'application/pdf')
              ->orWhere('failinimi', 'like', '%.pdf');
        });
    }
    
    /**
     * Ainult arved
     */
    public function scopeArved($query)
    {
        return $query->where('on_arve', true);
    }
    
    /**
     * Töötlemata arved
     */
    public function scopeTootlemata($query)
    {
        return $query->where('on_arve', true)
            ->whereIn('arve_staatus', ['tuvastamata', 'tuvastatud']);
    }
    
    /**
     * Kas on PDF?
     */
    public function getOnPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf'
            || str_ends_with(strtolower($this->failinimi ?? ''), '.pdf');
    }
    
    /**
     * Failisuurus inimloetaval kujul
     */
    public function getSuurusLoetavAttribute(): string
    {
        if (!$this->suurus) return '';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->suurus;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 1) . ' ' . $units[$unit];
    }
    
    /**
     * OCR-ist tuvastatud arve summa
     */
    public function getArveSummaAttribute(): ?float
    {
        return $this->ocr_andmed['summa'] ?? null;
    }
    
    /**
     * OCR-ist tuvastatud tarnija
     */
    public function getArveTarnijaAttribute(): ?string
    {
        return $this->ocr_andmed['tarnija'] ?? null;
    }
}
