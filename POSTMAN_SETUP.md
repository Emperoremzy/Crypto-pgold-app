# Postman Documentation for Pgold Crypto App API

This guide will help you import and use the Postman collection for testing the Pgold Crypto App API.

## Quick Setup

### 1. Import Collection and Environment

1. **Open Postman**
2. **Import Collection:**
   - Click "Import" button
   - Select `postman_collection.json` file
   - Click "Import"

3. **Import Environment:**
   - Click "Import" button
   - Select `postman_environment.json` file
   - Click "Import"

4. **Select Environment:**
   - In the top-right corner, select "Pgold Crypto App - Local Environment"

### 2. Start Your Laravel Server

Make sure your Laravel development server is running:

```bash
cd crypto_app
php artisan serve
```

The server should be running at `http://localhost:8000`

## API Testing Workflow

### Step 1: Authentication

1. **Register a New User**
   - Navigate to `Authentication > Register User`
   - Click "Send"
   - Note the verification token in the response

2. **Send Email Verification**
   - Navigate to `Authentication > Send Email Verification`
   - Click "Send"
   - Note the OTP in the response (for testing purposes)

3. **Verify Email**
   - Navigate to `Authentication > Verify Email`
   - Enter the OTP from the previous step
   - Click "Send"

4. **Login**
   - Navigate to `Authentication > Login`
   - Click "Send"
   - **Important:** The token will be automatically saved to the environment variable

### Step 2: Wallet Operations

After successful login, you can test wallet operations:

1. **Get Total Balance**
   - Navigate to `Wallet Management > Get Total Balance`
   - Click "Send"

2. **Get Crypto Assets**
   - Navigate to `Wallet Management > Get Crypto Assets`
   - Click "Send"

3. **Deposit Crypto**
   - Navigate to `Wallet Management > Deposit Crypto`
   - Modify the amount and transaction hash
   - Click "Send"

4. **Withdraw Crypto**
   - Navigate to `Wallet Management > Withdraw Crypto`
   - Modify the amount and address
   - Click "Send"

5. **Transfer Crypto**
   - Navigate to `Wallet Management > Transfer Crypto`
   - Modify the recipient email
   - Click "Send"

### Step 3: Crypto Rates

Test crypto rate endpoints (no authentication required):

1. **Get All Crypto Rates**
   - Navigate to `Crypto Rates > Get All Crypto Rates`
   - Click "Send"

2. **Get Specific Rates**
   - Navigate to `Crypto Rates > Get Rates for Specific Symbols`
   - Click "Send"

3. **Calculate Conversion**
   - Navigate to `Crypto Rates > Calculate Conversion`
   - Modify the symbols and amount
   - Click "Send"

4. **Calculate USD Value**
   - Navigate to `Crypto Rates > Calculate USD Value`
   - Modify the symbol and amount
   - Click "Send"

## Environment Variables

The following variables are available in the environment:

| Variable | Description | Example Value |
|----------|-------------|---------------|
| `base_url` | API base URL | `http://localhost:8000/api` |
| `auth_token` | Bearer token (auto-populated) | `1|abc123...` |
| `user_email` | Test user email | `john@example.com` |
| `user_password` | Test user password | `password123` |
| `test_btc_address` | Test Bitcoin address | `1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa` |
| `test_eth_address` | Test Ethereum address | `0x742d35Cc6634C0532925a3b8D0c8B7C8B2C8B2C8` |

## Pre-request Scripts

The collection includes pre-request scripts that automatically:

1. **Save authentication token** after successful login
2. **Set Bearer token** for protected routes

## Response Examples

### Successful Registration
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

### Successful Login
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

### Wallet Balance
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

### Crypto Rates
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
      }
    ],
    "total_assets": 1,
    "last_updated": "2024-01-15T10:00:00.000000Z"
  }
}
```

## Error Responses

### Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

### Authentication Error
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### Not Found Error
```json
{
  "success": false,
  "message": "Crypto asset not found"
}
```

## Testing Tips

### 1. Token Management
- The login request automatically saves the token to environment variables
- All protected routes will use this token automatically
- To refresh the token, simply login again

### 2. Data Persistence
- User data persists between requests
- Crypto assets are created automatically for new users
- Rates are updated from external API

### 3. Error Testing
- Try invalid credentials to test error handling
- Test with expired tokens
- Test with missing required fields

### 4. Rate Limiting
- The API includes rate limiting
- If you hit limits, wait a few minutes before retrying

## Troubleshooting

### Common Issues

1. **"Connection refused"**
   - Ensure Laravel server is running: `php artisan serve`
   - Check the base_url in environment variables

2. **"Unauthenticated" error**
   - Make sure you've logged in successfully
   - Check that the auth_token is set in environment variables
   - Try logging in again

3. **"Validation failed" error**
   - Check the request body format
   - Ensure all required fields are provided
   - Verify data types (string, number, boolean)

4. **"Crypto asset not found"**
   - Make sure you're using valid crypto symbols (BTC, ETH, USDT)
   - Check that the user has the crypto asset

### Debug Steps

1. **Check Environment Variables**
   - Go to Postman settings
   - Verify environment variables are set correctly

2. **Check Request Headers**
   - Ensure Content-Type is set to application/json
   - Verify Authorization header for protected routes

3. **Check Laravel Logs**
   - Check `storage/logs/laravel.log` for server-side errors
   - Look for database connection issues

4. **Check Database**
   - Ensure migrations have been run: `php artisan migrate`
   - Verify database connection in `.env` file

## Advanced Usage

### Running Tests in Sequence

1. **Create a Collection Runner**
   - Select the collection
   - Click "Run" button
   - Configure the order of requests
   - Set up data dependencies

2. **Use Newman (CLI)**
   ```bash
   npm install -g newman
   newman run postman_collection.json -e postman_environment.json
   ```

### Custom Scripts

You can add custom scripts to requests for:
- Data validation
- Dynamic data generation
- Response processing
- Test assertions

### Environment Switching

Create multiple environments for:
- Development: `http://localhost:8000/api`
- Staging: `https://staging-api.pgold.com/api`
- Production: `https://api.pgold.com/api`

## Support

For issues with the API or Postman collection:
1. Check the Laravel logs
2. Verify environment configuration
3. Test individual endpoints
4. Check database connectivity

## API Endpoints Summary

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/auth/register` | Register new user | No |
| POST | `/auth/login` | User login | No |
| POST | `/auth/verify-email` | Verify email with OTP | No |
| POST | `/auth/logout` | Logout user | Yes |
| GET | `/auth/me` | Get current user | Yes |
| GET | `/wallet/balance` | Get total balance | Yes |
| GET | `/wallet/crypto-assets` | Get crypto assets | Yes |
| POST | `/wallet/deposit` | Deposit crypto | Yes |
| POST | `/wallet/withdraw` | Withdraw crypto | Yes |
| POST | `/wallet/transfer` | Transfer crypto | Yes |
| GET | `/crypto-rates` | Get all rates | No |
| GET | `/crypto-rates/{symbol}` | Get specific rate | No |
| POST | `/crypto-rates/convert` | Calculate conversion | No |
| POST | `/crypto-rates/calculate-usd` | Calculate USD value | No |
