<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\CryptoAsset;
use App\Models\CryptoRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * Get user's total account balance
     */
    public function getTotalBalance(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found'
            ], 404);
        }

        // Update total balance from crypto assets
        $totalBalance = $wallet->calculateTotalBalance();

        return response()->json([
            'success' => true,
            'data' => [
                'total_balance_usd' => $totalBalance,
                'currency' => $wallet->currency,
                'wallet_id' => $wallet->id,
                'last_updated' => now()
            ]
        ]);
    }

    /**
     * Get all crypto assets with balances
     */
    public function getCryptoBalances(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found'
            ], 404);
        }

        // Update all asset balances with current rates
        $this->updateAssetBalances($user->cryptoAssets);

        $cryptoAssets = $user->cryptoAssets()->orderBy('symbol')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'crypto_assets' => $cryptoAssets,
                'total_assets' => $cryptoAssets->count(),
                'total_balance_usd' => $cryptoAssets->sum('balance_usd'),
                'last_updated' => now()
            ]
        ]);
    }

    /**
     * Get specific crypto asset balance
     */
    public function getCryptoAsset(Request $request, $symbol)
    {
        $user = $request->user();
        $cryptoAsset = $user->cryptoAssets()->where('symbol', strtoupper($symbol))->first();

        if (!$cryptoAsset) {
            return response()->json([
                'success' => false,
                'message' => 'Crypto asset not found'
            ], 404);
        }

        // Update asset balance with current rate
        $cryptoAsset->updateBalanceUsd();

        return response()->json([
            'success' => true,
            'data' => [
                'crypto_asset' => $cryptoAsset->fresh(),
                'last_updated' => now()
            ]
        ]);
    }

    /**
     * Deposit crypto to user's wallet
     */
    public function deposit(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'symbol' => 'required|string|max:10',
            'amount' => 'required|numeric|min:0.00000001',
            'transaction_hash' => 'required|string|unique:crypto_transactions,transaction_hash',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $symbol = strtoupper($request->symbol);
        $amount = $request->amount;

        try {
            DB::beginTransaction();

            $cryptoAsset = $user->cryptoAssets()->where('symbol', $symbol)->first();

            if (!$cryptoAsset) {
                // Create new crypto asset if it doesn't exist
                $wallet = $user->wallet;
                $cryptoAsset = CryptoAsset::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'symbol' => $symbol,
                    'name' => $this->getCryptoName($symbol),
                    'balance' => $amount,
                    'balance_usd' => 0.00,
                    'current_rate_usd' => 0.00,
                ]);
            } else {
                // Update existing asset
                $cryptoAsset->balance += $amount;
                $cryptoAsset->updateBalanceUsd();
            }

            // Update wallet total balance
            $wallet = $user->wallet;
            $wallet->calculateTotalBalance();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deposit successful',
                'data' => [
                    'crypto_asset' => $cryptoAsset->fresh(),
                    'deposited_amount' => $amount,
                    'new_balance' => $cryptoAsset->balance,
                    'transaction_hash' => $request->transaction_hash
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Deposit failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Withdraw crypto from user's wallet
     */
    public function withdraw(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'symbol' => 'required|string|max:10',
            'amount' => 'required|numeric|min:0.00000001',
            'address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $symbol = strtoupper($request->symbol);
        $amount = $request->amount;

        try {
            DB::beginTransaction();

            $cryptoAsset = $user->cryptoAssets()->where('symbol', $symbol)->first();

            if (!$cryptoAsset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Crypto asset not found'
                ], 404);
            }

            if ($cryptoAsset->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance'
                ], 422);
            }

            // Update asset balance
            $cryptoAsset->balance -= $amount;
            $cryptoAsset->updateBalanceUsd();

            // Update wallet total balance
            $wallet = $user->wallet;
            $wallet->calculateTotalBalance();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal successful',
                'data' => [
                    'crypto_asset' => $cryptoAsset->fresh(),
                    'withdrawn_amount' => $amount,
                    'new_balance' => $cryptoAsset->balance,
                    'withdrawal_address' => $request->address
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer crypto between users
     */
    public function transfer(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'symbol' => 'required|string|max:10',
            'amount' => 'required|numeric|min:0.00000001',
            'recipient_email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $symbol = strtoupper($request->symbol);
        $amount = $request->amount;

        try {
            DB::beginTransaction();

            $senderAsset = $user->cryptoAssets()->where('symbol', $symbol)->first();

            if (!$senderAsset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Crypto asset not found'
                ], 404);
            }

            if ($senderAsset->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance'
                ], 422);
            }

            $recipient = \App\Models\User::where('email', $request->recipient_email)->first();
            $recipientAsset = $recipient->cryptoAssets()->where('symbol', $symbol)->first();

            // Update sender balance
            $senderAsset->balance -= $amount;
            $senderAsset->updateBalanceUsd();

            // Update or create recipient balance
            if ($recipientAsset) {
                $recipientAsset->balance += $amount;
                $recipientAsset->updateBalanceUsd();
            } else {
                $recipientAsset = CryptoAsset::create([
                    'user_id' => $recipient->id,
                    'wallet_id' => $recipient->wallet->id,
                    'symbol' => $symbol,
                    'name' => $this->getCryptoName($symbol),
                    'balance' => $amount,
                    'balance_usd' => 0.00,
                    'current_rate_usd' => 0.00,
                ]);
            }

            // Update both wallets
            $user->wallet->calculateTotalBalance();
            $recipient->wallet->calculateTotalBalance();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer successful',
                'data' => [
                    'sender_asset' => $senderAsset->fresh(),
                    'recipient_asset' => $recipientAsset->fresh(),
                    'transferred_amount' => $amount,
                    'recipient_email' => $request->recipient_email
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Transfer failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update asset balances with current rates
     */
    private function updateAssetBalances($cryptoAssets)
    {
        foreach ($cryptoAssets as $asset) {
            $asset->updateBalanceUsd();
        }
    }

    /**
     * Get crypto name by symbol
     */
    private function getCryptoName($symbol)
    {
        $names = [
            'BTC' => 'Bitcoin',
            'ETH' => 'Ethereum',
            'USDT' => 'Tether',
            'BNB' => 'Binance Coin',
            'ADA' => 'Cardano',
            'DOT' => 'Polkadot',
            'LINK' => 'Chainlink',
        ];

        return $names[$symbol] ?? $symbol;
    }
}
