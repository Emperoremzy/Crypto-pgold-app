# Pgold Crypto App API

A comprehensive Laravel API for a cryptocurrency wallet and trading platform, inspired by modern mobile crypto applications like Pgold. This API provides complete functionality for user authentication, wallet management, crypto asset tracking, and real-time cryptocurrency rate calculations.

## Features

### 🔐 Authentication & User Management
- **User Registration**: Complete signup flow with personal information collection
- **Email Verification**: OTP-based email verification system
- **Login/Logout**: Secure authentication with Laravel Sanctum
- **Biometric Authentication**: Support for Face ID and Fingerprint login
- **KYC Integration**: Identity verification framework

### 💰 Wallet & Balance Management
- **Multi-Currency Support**: Support for multiple cryptocurrencies (BTC, ETH, USDT, etc.)
- **Real-time Balance Tracking**: Live balance updates with USD conversion
- **Deposit/Withdrawal**: Crypto deposit and withdrawal functionality
- **Peer-to-Peer Transfers**: Transfer crypto between users
- **Transaction History**: Complete transaction tracking

### 📊 Crypto Rate Calculator
- **Real-time Rates**: Live cryptocurrency exchange rates from CoinGecko API
- **Currency Conversion**: Convert between different cryptocurrencies
- **USD Value Calculation**: Calculate USD value of crypto holdings
- **Rate History**: Historical rate data (framework ready)
- **Market Data**: Top gainers and losers tracking

## Technology Stack

- **Backend**: Laravel 10.x
- **Authentication**: Laravel Sanctum
- **Database**: MySQL/PostgreSQL
- **External API**: CoinGecko API for crypto rates
- **Validation**: Custom Request classes
- **Documentation**: Comprehensive API documentation

## Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL/PostgreSQL
- Laravel 10.x

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd crypto_app
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**
   Update your `.env` file with database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=crypto_app
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Configure external services**
   Add CoinGecko API key to `.env`:
   ```env
   COINGECKO_API_KEY=your_coingecko_api_key
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed initial data (optional)**
   ```bash
   php artisan db:seed
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

## API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication
All protected routes require a Bearer token in the Authorization header:
```
Authorization: Bearer {your_token}
```

## API Endpoints

### Authentication Endpoints

#### 1. User Registration
**POST** `/auth/register`

Register a new user with complete personal information.

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "phone_number": "+1234567890",
    "date_of_birth": "1990-01-01",
    "gender": "male",
    "country": "United States",
    "state": "California",
    "city": "Los Angeles",
    "address": "123 Main St",
    "referral_code": "REF123",
    "password": "password123",
    "password_confirmation": "password123",
    "terms_accepted": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "User registered successfully. Please verify your email.",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe",
            "email_verified_at": null,
            "created_at": "2024-01-15T10:00:00.000000Z"
        },
        "verification_token": "abc123..."
    }
}
```

#### 2. Send Email Verification
**POST** `/auth/send-verification`

Send OTP to user's email for verification.

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "OTP sent to your email address",
    "data": {
        "otp": "123456",
        "expires_in_minutes": 10
    }
}
```

#### 3. Verify Email
**POST** `/auth/verify-email`

Verify email using OTP.

**Request Body:**
```json
{
    "email": "john@example.com",
    "otp": "123456"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Email verified successfully"
}
```

#### 4. User Login
**POST** `/auth/login`

Authenticate user with email and password.

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "email_verified_at": "2024-01-15T10:05:00.000000Z"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

#### 5. Biometric Login
**POST** `/auth/biometric-login`

Login using Face ID or Fingerprint.

**Request Body:**
```json
{
    "email": "john@example.com",
    "type": "face_id",
    "biometric_token": "biometric_token_here"
}
```

#### 6. Setup Biometric Authentication
**POST** `/auth/setup-biometric` *(Protected)*

Enable biometric authentication for user.

**Request Body:**
```json
{
    "type": "face_id",
    "biometric_token": "biometric_token_here"
}
```

#### 7. Logout
**POST** `/auth/logout` *(Protected)*

Logout user and invalidate token.

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

#### 8. Get Current User
**GET** `/auth/me` *(Protected)*

Get authenticated user information.

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe",
            "phone_number": "+1234567890",
            "kyc_verified": false,
            "face_id_enabled": true,
            "fingerprint_enabled": false
        }
    }
}
```

### Wallet Endpoints

#### 1. Get Total Balance
**GET** `/wallet/balance` *(Protected)*

Get user's total wallet balance in USD.

**Response:**
```json
{
    "success": true,
    "data": {
        "total_balance_usd": 1234.56,
        "currency": "USD",
        "wallet_id": 1,
        "last_updated": "2024-01-15T10:00:00.000000Z"
    }
}
```

#### 2. Get Crypto Assets
**GET** `/wallet/crypto-assets` *(Protected)*

Get all crypto assets with balances.

**Response:**
```json
{
    "success": true,
    "data": {
        "crypto_assets": [
            {
                "id": 1,
                "symbol": "BTC",
                "name": "Bitcoin",
                "balance": "0.00000000",
                "balance_usd": 0.00,
                "current_rate_usd": 45000.00
            },
            {
                "id": 2,
                "symbol": "ETH",
                "name": "Ethereum",
                "balance": "0.00000000",
                "balance_usd": 0.00,
                "current_rate_usd": 3000.00
            }
        ],
        "total_assets": 2,
        "total_balance_usd": 0.00,
        "last_updated": "2024-01-15T10:00:00.000000Z"
    }
}
```

#### 3. Get Specific Crypto Asset
**GET** `/wallet/crypto-assets/{symbol}` *(Protected)*

Get specific crypto asset balance.

**Response:**
```json
{
    "success": true,
    "data": {
        "crypto_asset": {
            "id": 1,
            "symbol": "BTC",
            "name": "Bitcoin",
            "balance": "0.00100000",
            "balance_usd": 45.00,
            "current_rate_usd": 45000.00
        },
        "last_updated": "2024-01-15T10:00:00.000000Z"
    }
}
```

#### 4. Deposit Crypto
**POST** `/wallet/deposit` *(Protected)*

Deposit crypto to user's wallet.

**Request Body:**
```json
{
    "symbol": "BTC",
    "amount": 0.001,
    "transaction_hash": "abc123def456..."
}
```

**Response:**
```json
{
    "success": true,
    "message": "Deposit successful",
    "data": {
        "crypto_asset": {
            "id": 1,
            "symbol": "BTC",
            "balance": "0.00100000",
            "balance_usd": 45.00
        },
        "deposited_amount": 0.001,
        "new_balance": "0.00100000",
        "transaction_hash": "abc123def456..."
    }
}
```

#### 5. Withdraw Crypto
**POST** `/wallet/withdraw` *(Protected)*

Withdraw crypto from user's wallet.

**Request Body:**
```json
{
    "symbol": "BTC",
    "amount": 0.0005,
    "address": "1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa"
}
```

#### 6. Transfer Crypto
**POST** `/wallet/transfer` *(Protected)*

Transfer crypto to another user.

**Request Body:**
```json
{
    "symbol": "BTC",
    "amount": 0.0001,
    "recipient_email": "recipient@example.com"
}
```

### Crypto Rate Endpoints

#### 1. Get All Crypto Rates
**GET** `/crypto-rates`

Get all available cryptocurrency rates.

**Response:**
```json
{
    "success": true,
    "data": {
        "crypto_rates": [
            {
                "id": 1,
                "symbol": "BTC",
                "name": "Bitcoin",
                "rate_usd": 45000.00,
                "last_updated": "2024-01-15T10:00:00.000000Z"
            },
            {
                "id": 2,
                "symbol": "ETH",
                "name": "Ethereum",
                "rate_usd": 3000.00,
                "last_updated": "2024-01-15T10:00:00.000000Z"
            }
        ],
        "total_assets": 2,
        "last_updated": "2024-01-15T10:00:00.000000Z"
    }
}
```

#### 2. Get Rates for Specific Symbols
**GET** `/crypto-rates/symbols?symbols[]=BTC&symbols[]=ETH`

Get rates for specific cryptocurrency symbols.

**Response:**
```json
{
    "success": true,
    "data": {
        "crypto_rates": [
            {
                "symbol": "BTC",
                "name": "Bitcoin",
                "rate_usd": 45000.00
            },
            {
                "symbol": "ETH",
                "name": "Ethereum",
                "rate_usd": 3000.00
            }
        ],
        "requested_symbols": ["BTC", "ETH"],
        "last_updated": "2024-01-15T10:00:00.000000Z"
    }
}
```

#### 3. Get Single Crypto Rate
**GET** `/crypto-rates/{symbol}`

Get rate for a specific cryptocurrency.

**Response:**
```json
{
    "success": true,
    "data": {
        "crypto_rate": {
            "id": 1,
            "symbol": "BTC",
            "name": "Bitcoin",
            "rate_usd": 45000.00,
            "last_updated": "2024-01-15T10:00:00.000000Z"
        },
        "last_updated": "2024-01-15T10:00:00.000000Z"
    }
}
```

#### 4. Calculate Conversion
**POST** `/crypto-rates/convert`

Calculate conversion between two cryptocurrencies.

**Request Body:**
```json
{
    "from_symbol": "BTC",
    "to_symbol": "ETH",
    "amount": 0.1
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "from_symbol": "BTC",
        "to_symbol": "ETH",
        "from_amount": 0.1,
        "to_amount": 1.5,
        "from_rate_usd": 45000.00,
        "to_rate_usd": 3000.00,
        "usd_value": 4500.00,
        "last_updated": "2024-01-15T10:00:00.000000Z"
    }
}
```

#### 5. Calculate USD Value
**POST** `/crypto-rates/calculate-usd`

Calculate USD value of crypto amount.

**Request Body:**
```json
{
    "symbol": "BTC",
    "amount": 0.1
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "symbol": "BTC",
        "amount": 0.1,
        "rate_usd": 45000.00,
        "usd_value": 4500.00,
        "last_updated": "2024-01-15T10:00:00.000000Z"
    }
}
```

#### 6. Get Top Movers
**GET** `/crypto-rates/market/top-movers`

Get top gainers and losers.

**Response:**
```json
{
    "success": true,
    "data": {
        "top_gainers": [
            {
                "symbol": "BTC",
                "name": "Bitcoin",
                "rate_usd": 45000.00
            }
        ],
        "top_losers": [
            {
                "symbol": "ETH",
                "name": "Ethereum",
                "rate_usd": 3000.00
            }
        ],
        "last_updated": "2024-01-15T10:00:00.000000Z"
    }
}
```

## Database Schema

### Users Table
- `id` - Primary key
- `name` - User's full name
- `email` - Email address (unique)
- `username` - Username (unique, nullable)
- `phone_number` - Phone number
- `date_of_birth` - Date of birth
- `gender` - Gender (male/female/other)
- `country` - Country
- `state` - State
- `city` - City
- `address` - Address
- `referral_code` - Referral code (nullable)
- `password` - Hashed password
- `terms_accepted` - Terms acceptance flag
- `kyc_verified` - KYC verification status
- `face_id_enabled` - Face ID enabled flag
- `fingerprint_enabled` - Fingerprint enabled flag
- `email_verified_at` - Email verification timestamp
- `email_verification_token` - Email verification token
- `email_verification_otp` - Email verification OTP
- `email_verification_otp_expires` - OTP expiration timestamp

### Wallets Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `total_balance_usd` - Total balance in USD
- `currency` - Currency (default: USD)

### Crypto Assets Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `wallet_id` - Foreign key to wallets table
- `symbol` - Crypto symbol (BTC, ETH, etc.)
- `name` - Crypto name
- `balance` - Crypto balance
- `balance_usd` - Balance in USD
- `current_rate_usd` - Current rate in USD

### Crypto Rates Table
- `id` - Primary key
- `symbol` - Crypto symbol (unique)
- `name` - Crypto name
- `rate_usd` - Rate in USD
- `last_updated` - Last update timestamp

### Crypto Transactions Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `symbol` - Crypto symbol
- `type` - Transaction type (deposit/withdrawal/transfer)
- `amount` - Transaction amount
- `usd_value` - USD value
- `transaction_hash` - Blockchain transaction hash
- `from_address` - From address
- `to_address` - To address
- `recipient_email` - Recipient email (for transfers)
- `status` - Transaction status
- `notes` - Additional notes

## Error Handling

The API returns consistent error responses in the following format:

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

### Common HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Security Features

1. **Laravel Sanctum**: Token-based authentication
2. **Request Validation**: Comprehensive input validation
3. **Password Hashing**: Secure password storage
4. **Email Verification**: OTP-based email verification
5. **Biometric Authentication**: Face ID and Fingerprint support
6. **Rate Limiting**: API rate limiting (configurable)
7. **CORS**: Cross-origin resource sharing configuration

## External Integrations

### CoinGecko API
- **Purpose**: Real-time cryptocurrency rates
- **Configuration**: Add `COINGECKO_API_KEY` to `.env`
- **Rate Limits**: Respects CoinGecko's rate limits
- **Fallback**: Graceful handling of API failures

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
./vendor/bin/pint
```

### Database Seeding
```bash
php artisan db:seed
```

### Cache Management
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Production Deployment

### Environment Variables
Ensure the following variables are set in production:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_*` - Database configuration
- `COINGECKO_API_KEY` - CoinGecko API key
- `SANCTUM_STATEFUL_DOMAINS` - Allowed domains for Sanctum

### Security Considerations
1. Use HTTPS in production
2. Set secure session configuration
3. Configure proper CORS policies
4. Implement rate limiting
5. Use environment-specific API keys
6. Regular security updates

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support and questions, please open an issue in the repository or contact the development team.

## Changelog

### Version 1.0.0
- Initial release
- Complete authentication system
- Wallet management
- Crypto rate calculator
- Real-time rate updates
- Biometric authentication support
- Comprehensive API documentation