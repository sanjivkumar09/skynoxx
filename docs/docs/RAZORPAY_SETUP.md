# Razorpay Payment Gateway Integration Guide

## 🚀 Complete Setup Instructions

### Step 1: Create Razorpay Account

1. Go to [https://razorpay.com/](https://razorpay.com/)
2. Click "Sign Up" and create an account
3. Complete KYC verification (required for live payments)
4. Access your dashboard at [https://dashboard.razorpay.com/](https://dashboard.razorpay.com/)

### Step 2: Get API Keys

1. Login to Razorpay Dashboard
2. Go to **Settings** → **API Keys**
3. Click **Generate Test Keys** (for testing)
4. Copy your:
   - **Key ID** (starts with `rzp_test_`)
   - **Key Secret** (keep this secure!)

### Step 3: Configure Your Application

Open the file: `src/razorpay_config.php`

Replace the placeholder values:

```php
// Replace these with your actual Razorpay keys
define('RAZORPAY_KEY_ID', 'rzp_test_YOUR_KEY_ID');      // Your Test Key ID
define('RAZORPAY_KEY_SECRET', 'YOUR_KEY_SECRET');        // Your Key Secret

// Set mode to 'test' for testing, 'live' for production
define('RAZORPAY_MODE', 'test');
```

**Example:**
```php
define('RAZORPAY_KEY_ID', 'rzp_test_AbCdEfGhIjKlMnOp');
define('RAZORPAY_KEY_SECRET', 'qRsTuVwXyZ1234567890');
define('RAZORPAY_MODE', 'test');
```

### Step 4: Test the Integration

1. **Enable Test Mode** in `razorpay_config.php`:
   ```php
   define('RAZORPAY_MODE', 'test');
   ```

2. **Access Payment Page**:
   ```
   http://localhost/Free%20fire%201/free-fire-tournament-platform/player/razorpay_payment.php?reg_id=1
   ```

3. **Use Test Card Details**:
   - **Card Number**: `4111 1111 1111 1111`
   - **CVV**: Any 3 digits (e.g., `123`)
   - **Expiry**: Any future date (e.g., `12/25`)
   - **Name**: Any name

4. **Test UPI**:
   - UPI ID: `success@razorpay`
   - This will simulate a successful payment

5. **Test Net Banking**:
   - Select any bank
   - Choose "Success" in test mode

### Step 5: Verify Installation

✅ **Check these files exist:**
- `src/razorpay_config.php` (Configuration)
- `player/razorpay_payment.php` (Payment page)
- `player/razorpay_verify.php` (Verification handler)

✅ **Test workflow:**
1. Player clicks "Pay with Razorpay" → Opens payment page
2. Clicks "Pay Now" → Razorpay checkout opens
3. Completes payment → Auto-redirected to dashboard
4. Payment status updates to "Completed" ✓

## 📋 Features Included

### ✅ Automatic Payment Verification
- Razorpay signature validation
- Payment status verification via API
- Secure transaction recording

### ✅ Multiple Payment Methods
- 💳 Credit/Debit Cards (Visa, Mastercard, RuPay, Amex)
- 📱 UPI (Google Pay, PhonePe, Paytm, BHIM)
- 🏦 Net Banking (50+ banks)
- 💰 Wallets (Paytm, PhonePe, Mobikwik, etc.)
- 💵 EMI Options (for eligible cards)

### ✅ Security Features
- PCI-DSS compliant
- 256-bit SSL encryption
- SHA-256 signature verification
- Fraud detection by Razorpay
- 3D Secure authentication

### ✅ User Experience
- Instant payment confirmation
- Auto-redirect after success
- Mobile-responsive checkout
- Multiple language support
- One-click payments (for returning users)

## 🎮 Player Workflow

1. **Join Tournament** → Registration created with "Pending" status
2. **Click "Pay with Razorpay"** → Redirected to secure payment page
3. **Choose Payment Method** → Card/UPI/Net Banking/Wallet
4. **Complete Payment** → Razorpay processes securely
5. **Auto-Verification** → System verifies and updates status
6. **Confirmation** → Redirected to dashboard with success message
7. **Tournament Access** → Player is confirmed for tournament

## 💼 Admin Features

- View all Razorpay payments in `admin/payment_management.php`
- Transaction IDs automatically recorded
- Payment method tracked (card/upi/netbanking)
- Real-time payment status
- Full audit trail

## 🔧 Configuration Options

### Custom Business Details

In `src/razorpay_config.php`:

```php
// Customize your business information
define('RAZORPAY_BUSINESS_NAME', 'Your Tournament Name');
define('RAZORPAY_BUSINESS_LOGO', '/path/to/your/logo.png');
define('RAZORPAY_THEME_COLOR', '#ff4655');  // Your brand color
```

### Webhook Configuration (Advanced)

1. Go to Razorpay Dashboard → **Settings** → **Webhooks**
2. Add webhook URL:
   ```
   https://yourdomain.com/player/razorpay_webhook.php
   ```
3. Select events: `payment.captured`, `payment.failed`
4. Copy **Webhook Secret** to config:
   ```php
   define('RAZORPAY_WEBHOOK_SECRET', 'your_webhook_secret');
   ```

## 🚦 Going Live (Production)

### Prerequisites
1. Complete KYC verification on Razorpay
2. Get your business account activated
3. Test thoroughly in test mode

### Steps to Go Live

1. **Generate Live API Keys**:
   - Dashboard → Settings → API Keys
   - Click "Generate Live Keys"
   - Copy Key ID (starts with `rzp_live_`)

2. **Update Configuration**:
   ```php
   define('RAZORPAY_KEY_ID', 'rzp_live_YOUR_LIVE_KEY');
   define('RAZORPAY_KEY_SECRET', 'your_live_secret');
   define('RAZORPAY_MODE', 'live');
   ```

3. **Enable Live Mode**:
   - Dashboard → Settings → Configuration
   - Toggle "Activate Account"

4. **Test with Real Money**:
   - Make a small test transaction (₹1)
   - Verify payment flow works
   - Refund the test transaction

## 📊 Testing Checklist

- [ ] Razorpay account created
- [ ] Test API keys configured
- [ ] Payment page loads correctly
- [ ] Razorpay checkout opens
- [ ] Test card payment succeeds
- [ ] Test UPI payment succeeds
- [ ] Payment status updates to "Completed"
- [ ] Transaction ID recorded in database
- [ ] Player dashboard shows payment success
- [ ] Admin panel shows payment details
- [ ] Payment failure handled gracefully
- [ ] Manual payment option still available

## 🐛 Troubleshooting

### "Authentication failed" error
**Solution**: Check API keys in `razorpay_config.php` are correct

### Razorpay checkout doesn't open
**Solution**: 
- Check browser console for errors
- Verify Razorpay script is loaded: `https://checkout.razorpay.com/v1/checkout.js`
- Check if Key ID is correct (starts with `rzp_test_` or `rzp_live_`)

### Payment succeeds but status doesn't update
**Solution**:
- Check `razorpay_verify.php` is accessible
- Verify database has required columns (run `setup_payment_system.php`)
- Check PHP error logs for issues

### "Signature verification failed"
**Solution**:
- Ensure Key Secret matches the Key ID
- Check for extra spaces in config file
- Verify signature generation logic

### Payment amount mismatch
**Solution**:
- Razorpay uses paise (1 rupee = 100 paise)
- Check amount conversion: `amount * 100`

## 💰 Razorpay Pricing

### Transaction Fees (India)
- **Domestic Cards**: 2% per transaction
- **UPI**: Free (₹0)
- **Net Banking**: ₹3-7 per transaction
- **Wallets**: 2% per transaction
- **International Cards**: 3% + GST

### No Setup Fees
- No signup fees
- No annual maintenance
- No hidden charges
- Pay only for successful transactions

## 🔐 Security Best Practices

1. **Never commit API keys to Git**:
   ```bash
   # Add to .gitignore
   echo "src/razorpay_config.php" >> .gitignore
   ```

2. **Use environment variables** (recommended):
   ```php
   define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID'));
   define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET'));
   ```

3. **Enable HTTPS** for production:
   - Get SSL certificate (Let's Encrypt free)
   - Force HTTPS redirect
   - Update Razorpay callback URLs

4. **Validate webhook signatures**:
   ```php
   $signature = hash_hmac('sha256', $webhook_body, RAZORPAY_WEBHOOK_SECRET);
   ```

## 📞 Support & Resources

### Razorpay Documentation
- [Official Docs](https://razorpay.com/docs/)
- [Payment Gateway API](https://razorpay.com/docs/payments/)
- [Checkout Integration](https://razorpay.com/docs/payments/payment-gateway/web-integration/)

### Support Channels
- **Email**: support@razorpay.com
- **Dashboard**: Raise ticket in Razorpay dashboard
- **Phone**: Check dashboard for support number

### Testing Resources
- [Test Cards](https://razorpay.com/docs/payments/payments/test-card-details/)
- [Test UPI](https://razorpay.com/docs/payments/payments/test-upi-details/)
- [Postman Collection](https://razorpay.com/docs/api/)

## 🎯 Quick Reference

### Payment Flow
```
Player → Pay with Razorpay → razorpay_payment.php 
    → Create Order (API) 
    → Open Razorpay Checkout 
    → User Pays 
    → razorpay_verify.php (Verification) 
    → Update Database 
    → Redirect to Dashboard (Success)
```

### Key Files
| File | Purpose |
|------|---------|
| `razorpay_config.php` | API keys and configuration |
| `razorpay_payment.php` | Payment initiation page |
| `razorpay_verify.php` | Payment verification handler |
| `player_dashboard.php` | Shows payment status |
| `payment_management.php` | Admin payment tracking |

### Test Credentials
| Method | Credential | Result |
|--------|-----------|--------|
| Card | 4111 1111 1111 1111 | Success |
| UPI | success@razorpay | Success |
| UPI | failure@razorpay | Failure |

## 🎊 Success!

Your Razorpay integration is now complete! Players can:
- ✅ Pay instantly with cards/UPI/netbanking
- ✅ Get auto-verified payments
- ✅ Receive instant confirmation
- ✅ Access tournaments immediately

**Next Steps**:
1. Configure your API keys
2. Test with test credentials
3. Verify payment flow works
4. Go live when ready!

For questions or issues, check the troubleshooting section or contact Razorpay support.
