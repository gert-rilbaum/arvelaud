<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kiri extends Model
{
    protected $table = 'kirjad';
    
    protected $fillable = [
        'firma_id',
        'gmail_message_id',
        'gmail_thread_id',
        'saatja_email',
        'saatja_nimi',
        'saaja_email',
        'teema',
        'sisu_text',
        'sisu_html',
        'suund',
        'staatus',
        'on_manuseid',
        'gmail_kuupaev',
    ];
    
    protected $casts = [
        'on_manuseid' => 'boolean',
        'gmail_kuupaev' => 'datetime',
    ];
    
    /**
     * Kirja firma
     */
    public function firma(): BelongsTo
    {
        return $this->belongsTo(Firma::class);
    }
    
    /**
     * Kirja manused
     */
    public function manused(): HasMany
    {
        return $this->hasMany(Manus::class);
    }
    
    /**
     * Kirjaga seotud tegevused
     */
    public function tegevused(): HasMany
    {
        return $this->hasMany(Tegevus::class);
    }
    
    /**
     * Sama vestluse kirjad (thread)
     */
    public function vestlus()
    {
        return $this->where('gmail_thread_id', $this->gmail_thread_id)
            ->where('firma_id', $this->firma_id)
            ->orderBy('gmail_kuupaev', 'asc');
    }
    
    /**
     * Ainult uued kirjad
     */
    public function scopeUued($query)
    {
        return $query->where('staatus', 'uus');
    }
    
    /**
     * Ainult sissetulevad
     */
    public function scopeSissetulevad($query)
    {
        return $query->where('suund', 'sisse');
    }
    
    /**
     * Kirjad koos manustega
     */
    public function scopeManustega($query)
    {
        return $query->where('on_manuseid', true);
    }
    
    /**
     * Kas on PDF manuseid?
     */
    public function getPdfManuseidAttribute(): bool
    {
        return $this->manused()->where('mime_type', 'application/pdf')->exists();
    }
    
    /**
     * Lühike sisu eelvaateks
     */
    public function getSisuEelvaadeAttribute(): string
    {
        $text = strip_tags($this->sisu_text ?? $this->sisu_html ?? '');
        return mb_substr($text, 0, 150) . (mb_strlen($text) > 150 ? '...' : '');
    }
}
