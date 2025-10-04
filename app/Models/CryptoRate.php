<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoRate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'symbol',
        'name',
        'rate_usd',
        'last_updated',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate_usd' => 'decimal:2',
        'last_updated' => 'datetime',
    ];

    /**
     * Get the crypto assets for this rate.
     */
    public function cryptoAssets()
    {
        return $this->hasMany(CryptoAsset::class, 'symbol', 'symbol');
    }

    /**
     * Scope to get rates for specific symbols.
     */
    public function scopeForSymbols($query, array $symbols)
    {
        return $query->whereIn('symbol', $symbols);
    }

    /**
     * Scope to get recent rates.
     */
    public function scopeRecent($query, $minutes = 5)
    {
        return $query->where('last_updated', '>=', now()->subMinutes($minutes));
    }
}
