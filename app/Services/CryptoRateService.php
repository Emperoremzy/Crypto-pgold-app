<?php

namespace App\Services;

use App\Models\CryptoRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CryptoRateService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.coingecko.api_key', '');
        $this->baseUrl = 'https://api.coingecko.com/api/v3';
    }

    /**
     * Update crypto rates from external API
     */
    public function updateRates()
    {
        try {
            $symbols = ['bitcoin', 'ethereum', 'tether', 'binancecoin', 'cardano', 'polkadot', 'chainlink'];
            $response = Http::timeout(30)->get($this->baseUrl . '/simple/price', [
                'ids' => implode(',', $symbols),
                'vs_currencies' => 'usd',
                'api_key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->saveRates($data);
                Log::info('Crypto rates updated successfully');
            } else {
                Log::error('Failed to fetch crypto rates from API', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to fetch rates from external API');
            }
        } catch (\Exception $e) {
            Log::error('Error updating crypto rates', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Save rates to database
     */
    private function saveRates($data)
    {
        $symbolMap = [
            'bitcoin' => 'BTC',
            'ethereum' => 'ETH',
            'tether' => 'USDT',
            'binancecoin' => 'BNB',
            'cardano' => 'ADA',
            'polkadot' => 'DOT',
            'chainlink' => 'LINK',
        ];

        $nameMap = [
            'bitcoin' => 'Bitcoin',
            'ethereum' => 'Ethereum',
            'tether' => 'Tether',
            'binancecoin' => 'Binance Coin',
            'cardano' => 'Cardano',
            'polkadot' => 'Polkadot',
            'chainlink' => 'Chainlink',
        ];

        foreach ($data as $coinId => $rates) {
            if (isset($symbolMap[$coinId]) && isset($rates['usd'])) {
                $symbol = $symbolMap[$coinId];
                $rate = $rates['usd'];

                CryptoRate::updateOrCreate(
                    ['symbol' => $symbol],
                    [
                        'name' => $nameMap[$coinId],
                        'rate_usd' => $rate,
                        'last_updated' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Get rate for specific symbol
     */
    public function getRate($symbol)
    {
        $symbol = strtoupper($symbol);
        $rate = CryptoRate::where('symbol', $symbol)->first();

        if (!$rate || $rate->last_updated->diffInMinutes(now()) > 5) {
            $this->updateRates();
            $rate = CryptoRate::where('symbol', $symbol)->first();
        }

        return $rate;
    }

    /**
     * Get multiple rates
     */
    public function getRates(array $symbols)
    {
        $symbols = array_map('strtoupper', $symbols);
        $rates = CryptoRate::whereIn('symbol', $symbols)->get();

        // Check if any rate is older than 5 minutes
        $needsUpdate = $rates->some(function ($rate) {
            return $rate->last_updated->diffInMinutes(now()) > 5;
        });

        if ($needsUpdate) {
            $this->updateRates();
            $rates = CryptoRate::whereIn('symbol', $symbols)->get();
        }

        return $rates;
    }

    /**
     * Convert amount between two cryptocurrencies
     */
    public function convert($fromSymbol, $toSymbol, $amount)
    {
        $fromRate = $this->getRate($fromSymbol);
        $toRate = $this->getRate($toSymbol);

        if (!$fromRate || !$toRate) {
            throw new \Exception('Unable to get rates for conversion');
        }

        // Convert through USD
        $usdValue = $amount * $fromRate->rate_usd;
        $convertedAmount = $usdValue / $toRate->rate_usd;

        return [
            'from_symbol' => $fromSymbol,
            'to_symbol' => $toSymbol,
            'from_amount' => $amount,
            'to_amount' => $convertedAmount,
            'usd_value' => $usdValue,
            'from_rate' => $fromRate->rate_usd,
            'to_rate' => $toRate->rate_usd,
        ];
    }

    /**
     * Calculate USD value of crypto amount
     */
    public function calculateUsdValue($symbol, $amount)
    {
        $rate = $this->getRate($symbol);

        if (!$rate) {
            throw new \Exception('Unable to get rate for USD calculation');
        }

        return $amount * $rate->rate_usd;
    }

    /**
     * Get all available symbols
     */
    public function getAvailableSymbols()
    {
        return CryptoRate::pluck('symbol')->toArray();
    }

    /**
     * Check if symbol is supported
     */
    public function isSupported($symbol)
    {
        $symbol = strtoupper($symbol);
        return CryptoRate::where('symbol', $symbol)->exists();
    }

    /**
     * Get market data (simplified)
     */
    public function getMarketData()
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/global', [
                'api_key' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching market data', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
