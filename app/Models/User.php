<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'phone_number',
        'date_of_birth',
        'gender',
        'country',
        'state',
        'city',
        'address',
        'referral_code',
        'password',
        'email_verified_at',
        'kyc_verified',
        'face_id_enabled',
        'fingerprint_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'password' => 'hashed',
        'kyc_verified' => 'boolean',
        'face_id_enabled' => 'boolean',
        'fingerprint_enabled' => 'boolean',
    ];

    /**
     * Get the wallet associated with the user.
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get the crypto assets for the user.
     */
    public function cryptoAssets()
    {
        return $this->hasMany(CryptoAsset::class);
    }
}
