# Razorpay Test Mode Integration Guide

## 🚀 Quick Start - Testing Razorpay on Your Website

### Step 1: Get Your Test API Keys

1. **Log in to Razorpay Dashboard**
   - Go to: https://dashboard.razorpay.com/
   - Sign in with your credentials

2. **Switch to Test Mode**
   - Look for the toggle in the top-left corner
   - Make sure it says "Test Mode" (blue toggle)

3. **Get API Keys**
   - Go to: **Settings** → **API Keys** (or direct: https://dashboard.razorpay.com/app/website-app-settings/api-keys)
   - Under **Test Mode** section, you'll see:
     - **Key ID**: Starts with `rzp_test_`
     - **Key Secret**: Click "Show" to reveal (starts with letters/numbers)
   - Click **Generate Test Key** if you don't have one yet
   - Copy both Key ID and Key Secret

4. **Get Webhook Secret** (optional but recommended)
   - Go to: **Settings** → **Webhooks**
   - Click "Create New Webhook" or "Add New Webhook"
   - URL: `https://yourdomain.com/webhooks/razorpay.php` (you'll set this up later)
   - Secret: Copy the secret shown (starts with `whsec_`)

---

### Step 2: Configure Your Website

1. **Edit razorpay_config.php**
   - Open: `src/razorpay_config.php`
   - Replace the placeholders:

```php
// Replace these with your actual test keys
define('RAZORPAY_KEY_ID', 'rzp_test_AbCdEfGhIjKlMn');    // Your actual test Key ID
define('RAZORPAY_KEY_SECRET', 'XyZ123456789AbCdEfGh');   // Your actual test Key Secret
define('RAZORPAY_WEBHOOK_SECRET', 'whsec_YourSecretHere'); // Your webhook secret
```

2. **Make sure test mode is enabled:**

```php
define('RAZORPAY_MODE', 'test'); // Keep this as 'test'
```

3. **Save the file**

---

### Step 3: Test Payment Flow

#### Option A: Test with Test Cards (No Real Money)

Razorpay provides special test card numbers that work in test mode:

| Card Number | CVV | Expiry | Result |
|------------|-----|--------|--------|
| 4111 1111 1111 1111 | 123 | Any future date | ✅ Success |
| 4012 8888 8888 1881 | 123 | Any future date | ✅ Success |
| 5555 5555 5555 4444 | 123 | Any future date | ✅ Success |
| 4111 1111 1111 1112 | 123 | Any future date | ❌ Failure |

**Test UPI IDs:**
- `success@razorpay` → Payment succeeds
- `failure@razorpay` → Payment fails

**Test Netbanking:**
- Select any bank
- Use any credentials (no real login required in test mode)

---

### Step 4: Complete Test Workflow

**Follow these steps on your website:**

1. **Start XAMPP**
   - Start Apache and MySQL

2. **Open Your Website**
   - Go to: `http://localhost/Free%20fire%201/free-fire-tournament-platform/src/index.php`

3. **Log in to Your Account**
   - Use your player account

4. **Go to Wallet**
   - Click on "My Wallet" or go to: `player/wallet_dashboard.php`

5. **Click "Add Money"**
   - Should redirect to: `player/wallet_deposit.php`

6. **Enter Test Amount**
   - Try: ₹100 (or any amount ≥ ₹10)
   - Click "Proceed to Payment"

7. **Razorpay Checkout Opens**
   - You should see the Razorpay payment popup
   - It will show: "Test Mode" badge
   - Choose payment method (Card/UPI/Netbanking)

8. **Complete Test Payment**
   
   **For Card Payment:**
   - Card Number: `4111 1111 1111 1111`
   - CVV: `123`
   - Expiry: `12/25` (or any future date)
   - Name: `Test User`
   - Click "Pay"

   **For UPI Payment:**
   - Enter UPI ID: `success@razorpay`
   - Click "Pay"

9. **Verify Success**
   - You should be redirected back to your wallet
   - Check if amount is added to your wallet balance
   - Check transaction history

---

### Step 5: Verify in Razorpay Dashboard

1. **Go to Razorpay Dashboard**
   - https://dashboard.razorpay.com/

2. **Check Test Payments**
   - Make sure you're in **Test Mode** (blue toggle)
   - Go to: **Transactions** → **Payments**
   - You should see your test payment listed
   - Status should be "Captured" or "Success"

3. **Check Payment Details**
   - Click on the payment
   - Verify amount, customer details, and order ID match

---

## 🔧 Troubleshooting

### Issue 1: "Invalid API Key"
- ✅ Double-check you copied the correct Key ID and Secret
- ✅ Make sure you're using **Test** keys (start with `rzp_test_`)
- ✅ Check for extra spaces when pasting
- ✅ Restart Apache after editing config file

### Issue 2: Payment Popup Doesn't Open
- ✅ Check browser console for JavaScript errors (F12)
- ✅ Make sure Razorpay script is loaded: `https://checkout.razorpay.com/v1/checkout.js`
- ✅ Clear browser cache
- ✅ Try in incognito mode

### Issue 3: Payment Succeeds but Wallet Not Updated
- ✅ Check `wallet_transactions` table in database
- ✅ Check PHP error logs in XAMPP
- ✅ Verify `razorpay_verify.php` is receiving the callback
- ✅ Check database connection

### Issue 4: "Callback URL Required"
- ✅ Make sure your website is accessible via HTTP/HTTPS
- ✅ For localhost testing, use ngrok or LocalTunnel to get public URL
- ✅ Update callback URLs in your code

---

## 📊 What Happens in Test Mode

✅ **No Real Money is Charged**
- All transactions are simulated
- No actual bank transfers happen
- Test cards are not real cards

✅ **Test Data Only**
- Payments appear only in Test Mode dashboard
- Separate from live payments
- Can be deleted/reset anytime

✅ **All Features Work**
- Payment capture
- Refunds
- Webhooks
- Payment methods (Card, UPI, Netbanking, Wallets)

❌ **What Doesn't Work**
- Real bank settlements (no money is actually transferred)
- SMS notifications (in free test mode)
- Email receipts (may not send in test mode)

---

## 🎯 Testing Checklist

Use this checklist to test all scenarios:

### Basic Payment Flow
- [ ] Successful card payment
- [ ] Successful UPI payment
- [ ] Failed payment (using failure test card)
- [ ] Wallet balance updates correctly
- [ ] Transaction appears in history
- [ ] Transaction status is correct

### Edge Cases
- [ ] Minimum amount validation (₹10)
- [ ] Payment timeout/cancel
- [ ] Multiple rapid payments
- [ ] Payment with same order ID (should fail)

### Database Verification
- [ ] Check `wallet_transactions` table
- [ ] Verify `reference_id` matches Razorpay payment ID
- [ ] Check `status` field is correct
- [ ] Verify `amount` matches

### Refund Testing (if implemented)
- [ ] Initiate refund from Razorpay dashboard
- [ ] Verify wallet balance decreases
- [ ] Check transaction record updated

---

## 📱 Testing on Mobile/Public URL

If you want to test from your phone or share with others:

### Option 1: Ngrok (Recommended)
```powershell
# Install ngrok
# Download from: https://ngrok.com/download

# Run ngrok to expose localhost
ngrok http 80

# You'll get a public URL like: https://abc123.ngrok.io
# Use this URL to access your site from anywhere
```

### Option 2: Cloudflare Tunnel (Already Set Up)
```powershell
cd C:\cloudflared
.\start-tunnel.ps1

# Use your configured domain
```

### Update Webhook URL
Once you have a public URL, update webhook in Razorpay:
1. Go to: Dashboard → Settings → Webhooks
2. Edit your webhook
3. URL: `https://yourdomain.com/player/razorpay_webhook.php`
4. Events: Select "payment.captured", "payment.failed"
5. Save

---

## 🔐 Security Best Practices

### Do:
✅ Keep API keys secure
✅ Never commit keys to GitHub/version control
✅ Use environment variables in production
✅ Validate payment signatures server-side
✅ Log all transactions

### Don't:
❌ Share your Key Secret publicly
❌ Use test keys in production
❌ Skip signature verification
❌ Trust client-side payment success
❌ Store complete card details

---

## 📞 Need Help?

### Razorpay Support
- Dashboard: https://dashboard.razorpay.com/
- Docs: https://razorpay.com/docs/
- Support: support@razorpay.com
- Test Cards: https://razorpay.com/docs/payments/payments/test-card-details/

### Your Platform Support
- Email: gameshear09@gmail.com
- Phone: 9981474023

---

## ✅ Ready for Production?

Before switching to Live Mode:
1. ✅ Complete KYC verification
2. ✅ Test all payment flows thoroughly
3. ✅ Set up webhooks on production domain
4. ✅ Replace test keys with live keys
5. ✅ Test with small real amount first
6. ✅ Monitor first few transactions closely

---

**Happy Testing! 🎮💰**
