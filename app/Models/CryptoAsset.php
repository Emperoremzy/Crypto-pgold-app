<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoAsset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'symbol',
        'name',
        'balance',
        'balance_usd',
        'current_rate_usd',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:8',
        'balance_usd' => 'decimal:2',
        'current_rate_usd' => 'decimal:2',
    ];

    /**
     * Get the user that owns the crypto asset.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet that owns the crypto asset.
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Update balance in USD based on current rate.
     */
    public function updateBalanceUsd()
    {
        $cryptoRate = CryptoRate::where('symbol', $this->symbol)->first();
        if ($cryptoRate) {
            $this->balance_usd = $this->balance * $cryptoRate->rate_usd;
            $this->current_rate_usd = $cryptoRate->rate_usd;
            $this->save();
        }
    }
}
