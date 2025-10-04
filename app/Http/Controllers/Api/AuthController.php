<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\EmailVerificationRequest;
use App\Http\Requests\BiometricSetupRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Models\CryptoAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'phone_number' => $request->phone_number,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city,
                'address' => $request->address,
                'referral_code' => $request->referral_code,
                'password' => Hash::make($request->password),
                'terms_accepted' => $request->terms_accepted,
            ]);

            // Create wallet for user
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'total_balance_usd' => 0.00,
                'currency' => 'USD',
            ]);

            // Create default crypto assets
            $defaultAssets = ['BTC', 'ETH', 'USDT'];
            foreach ($defaultAssets as $symbol) {
                CryptoAsset::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'symbol' => $symbol,
                    'name' => $this->getCryptoName($symbol),
                    'balance' => 0.00000000,
                    'balance_usd' => 0.00,
                    'current_rate_usd' => 0.00,
                ]);
            }

            // Generate email verification token (in real app, you'd send email)
            $user->email_verification_token = \Str::random(64);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully. Please verify your email.',
                'data' => [
                    'user' => $user->makeHidden(['password', 'remember_token']),
                    'verification_token' => $user->email_verification_token, // Remove in production
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send email verification OTP
     */
    public function sendEmailVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Generate 6-digit OTP
        $otp = sprintf('%06d', mt_rand(100000, 999999));
        $user->email_verification_otp = $otp;
        $user->email_verification_otp_expires = now()->addMinutes(10);
        $user->save();

        // In production, send email here
        // Mail::to($user->email)->send(new EmailVerificationMail($otp));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email address',
            'data' => [
                'otp' => $otp, // Remove in production
                'expires_in_minutes' => 10
            ]
        ]);
    }

    /**
     * Verify email OTP
     */
    public function verifyEmail(EmailVerificationRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->email_verification_otp !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP'
            ], 422);
        }

        if ($user->email_verification_otp_expires < now()) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired'
            ], 422);
        }

        $user->email_verified_at = now();
        $user->email_verification_otp = null;
        $user->email_verification_otp_expires = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully'
        ]);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // if (!$user->email_verified_at) { //enabled in production
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Please verify your email before logging in'
        //     ], 422);
        // }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Setup biometric authentication
     */
    public function setupBiometric(BiometricSetupRequest $request)
    {
        $user = $request->user();

        if ($request->type === 'face_id') {
            $user->face_id_enabled = true;
        } elseif ($request->type === 'fingerprint') {
            $user->fingerprint_enabled = true;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->type) . ' enabled successfully'
        ]);
    }

    /**
     * Biometric login
     */
    public function biometricLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'type' => 'required|in:face_id,fingerprint',
            'biometric_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email before logging in'
            ], 422);
        }

        // In production, verify biometric token here
        $biometricEnabled = $request->type === 'face_id' ? $user->face_id_enabled : $user->fingerprint_enabled;
        
        if (!$biometricEnabled) {
            return response()->json([
                'success' => false,
                'message' => ucfirst($request->type) . ' not enabled for this account'
            ], 422);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Biometric login successful',
            'data' => [
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()->makeHidden(['password', 'remember_token'])
            ]
        ]);
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
