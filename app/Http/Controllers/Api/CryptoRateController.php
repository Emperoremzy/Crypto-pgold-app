<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CryptoRate;
use App\Services\CryptoRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CryptoRateController extends Controller
{
    protected $cryptoRateService;

    public function __construct(CryptoRateService $cryptoRateService)
    {
        $this->cryptoRateService = $cryptoRateService;
    }

    /**
     * Get all crypto rates
     */
    public function getAllRates(Request $request)
    {
        try {
            // Update rates from external API
            $this->cryptoRateService->updateRates();

            $rates = CryptoRate::orderBy('symbol')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'crypto_rates' => $rates,
                    'total_assets' => $rates->count(),
                    'last_updated' => $rates->max('last_updated')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch crypto rates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rates for specific crypto symbols
     */
    public function getRatesForSymbols(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbols' => 'required|array|min:1',
            'symbols.*' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $symbols = array_map('strtoupper', $request->symbols);
            
            // Update rates from external API
            $this->cryptoRateService->updateRates();

            $rates = CryptoRate::forSymbols($symbols)->get();

            if ($rates->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No rates found for the specified symbols'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'crypto_rates' => $rates,
                    'requested_symbols' => $symbols,
                    'last_updated' => $rates->max('last_updated')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch crypto rates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single crypto rate
     */
    public function getRate(Request $request, $symbol)
    {
        try {
            $symbol = strtoupper($symbol);
            
            // Update rates from external API
            $this->cryptoRateService->updateRates();

            $rate = CryptoRate::where('symbol', $symbol)->first();

            if (!$rate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Crypto rate not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'crypto_rate' => $rate,
                    'last_updated' => $rate->last_updated
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch crypto rate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate conversion between two cryptocurrencies
     */
    public function calculateConversion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_symbol' => 'required|string|max:10',
            'to_symbol' => 'required|string|max:10',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fromSymbol = strtoupper($request->from_symbol);
            $toSymbol = strtoupper($request->to_symbol);
            $amount = $request->amount;

            // Update rates from external API
            $this->cryptoRateService->updateRates();

            $fromRate = CryptoRate::where('symbol', $fromSymbol)->first();
            $toRate = CryptoRate::where('symbol', $toSymbol)->first();

            if (!$fromRate || !$toRate) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or both crypto rates not found'
                ], 404);
            }

            // Convert through USD
            $usdValue = $amount * $fromRate->rate_usd;
            $convertedAmount = $usdValue / $toRate->rate_usd;

            return response()->json([
                'success' => true,
                'data' => [
                    'from_symbol' => $fromSymbol,
                    'to_symbol' => $toSymbol,
                    'from_amount' => $amount,
                    'to_amount' => round($convertedAmount, 8),
                    'from_rate_usd' => $fromRate->rate_usd,
                    'to_rate_usd' => $toRate->rate_usd,
                    'usd_value' => round($usdValue, 2),
                    'last_updated' => max($fromRate->last_updated, $toRate->last_updated)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate conversion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate USD value of crypto amount
     */
    public function calculateUsdValue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|string|max:10',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $symbol = strtoupper($request->symbol);
            $amount = $request->amount;

            // Update rates from external API
            $this->cryptoRateService->updateRates();

            $rate = CryptoRate::where('symbol', $symbol)->first();

            if (!$rate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Crypto rate not found'
                ], 404);
            }

            $usdValue = $amount * $rate->rate_usd;

            return response()->json([
                'success' => true,
                'data' => [
                    'symbol' => $symbol,
                    'amount' => $amount,
                    'rate_usd' => $rate->rate_usd,
                    'usd_value' => round($usdValue, 2),
                    'last_updated' => $rate->last_updated
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate USD value',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rate history for a crypto symbol
     */
    public function getRateHistory(Request $request, $symbol)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $symbol = strtoupper($symbol);
            $days = $request->days ?? 7;

            // In a real application, you'd have a separate rate_history table
            // For now, we'll return the current rate
            $rate = CryptoRate::where('symbol', $symbol)->first();

            if (!$rate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Crypto rate not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'symbol' => $symbol,
                    'current_rate' => $rate,
                    'history_days' => $days,
                    'note' => 'Rate history feature requires additional database structure'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch rate history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top gainers and losers
     */
    public function getTopMovers(Request $request)
    {
        try {
            // Update rates from external API
            $this->cryptoRateService->updateRates();

            $rates = CryptoRate::orderBy('symbol')->get();

            // In a real application, you'd calculate percentage changes
            // For now, we'll return the current rates
            $topGainers = $rates->take(5); // Top 5 by rate
            $topLosers = $rates->sortBy('rate_usd')->take(5); // Bottom 5 by rate

            return response()->json([
                'success' => true,
                'data' => [
                    'top_gainers' => $topGainers,
                    'top_losers' => $topLosers,
                    'last_updated' => $rates->max('last_updated'),
                    'note' => 'Percentage changes require historical data tracking'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch top movers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
