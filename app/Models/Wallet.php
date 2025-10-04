<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'total_balance_usd',
        'currency',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_balance_usd' => 'decimal:2',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the crypto assets for the wallet.
     */
    public function cryptoAssets()
    {
        return $this->hasMany(CryptoAsset::class);
    }

    /**
     * Calculate total balance from crypto assets.
     */
    public function calculateTotalBalance()
    {
        $total = $this->cryptoAssets->sum(function ($asset) {
            return $asset->balance_usd;
        });
        
        $this->update(['total_balance_usd' => $total]);
        return $total;
    }
}
