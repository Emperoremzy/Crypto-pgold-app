<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\CryptoRateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/send-verification', [AuthController::class, 'sendEmailVerification']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/biometric-login', [AuthController::class, 'biometricLogin']);
});

// Crypto rates (public)
Route::prefix('crypto-rates')->group(function () {
    Route::get('/', [CryptoRateController::class, 'getAllRates']);
    Route::get('/symbols', [CryptoRateController::class, 'getRatesForSymbols']);
    Route::get('/{symbol}', [CryptoRateController::class, 'getRate']);
    Route::get('/{symbol}/history', [CryptoRateController::class, 'getRateHistory']);
    Route::get('/market/top-movers', [CryptoRateController::class, 'getTopMovers']);
    Route::post('/convert', [CryptoRateController::class, 'calculateConversion']);
    Route::post('/calculate-usd', [CryptoRateController::class, 'calculateUsdValue']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/setup-biometric', [AuthController::class, 'setupBiometric']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Wallet routes
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'getTotalBalance']);
        Route::get('/crypto-assets', [WalletController::class, 'getCryptoBalances']);
        Route::get('/crypto-assets/{symbol}', [WalletController::class, 'getCryptoAsset']);
        Route::post('/deposit', [WalletController::class, 'deposit']);
        Route::post('/withdraw', [WalletController::class, 'withdraw']);
        Route::post('/transfer', [WalletController::class, 'transfer']);
    });
});

// Legacy route for backward compatibility
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
