# Razorpay Integration Setup Guide

## ✅ What's Been Completed

1. **Tournament Join Page** (`src/join_tournament.php`)
   - Comprehensive tournament details display (entry fee, prize pool, date, time, game mode, max players, map)
   - Modern gaming-themed UI matching your site design
   - Registration form with in-game name and Game UID
   - Razorpay payment integration (needs API keys)
   - CSRF protection for security

2. **Payment Processing** (`src/process_payment.php`)
   - Payment verification endpoint
   - Razorpay signature validation
   - Database updates for registrations and payments
   - JSON response handling

3. **Database Schema**
   - `registrations` table: Stores tournament registrations with in_game_name, game_uid, payment_status
   - `payments` table: Stores payment records with Razorpay details

## 🔧 Configuration Required

### Step 1: Get Razorpay API Keys

1. Go to [Razorpay Dashboard](https://dashboard.razorpay.com/)
2. Sign up / Log in to your account
3. Navigate to **Settings → API Keys**
4. Click **Generate Test Keys** (for testing) or **Generate Live Keys** (for production)
5. You'll get:
   - **Key ID** (e.g., `rzp_test_1234567890`)
   - **Key Secret** (e.g., `abcdefghijklmnopqrstuvwxyz`)

### Step 2: Update API Key in Code

Open `src/join_tournament.php` and find line **~556**:

```javascript
"key": "YOUR_RAZORPAY_KEY_ID", // Replace with your actual Razorpay Key ID
```

**Replace** `YOUR_RAZORPAY_KEY_ID` with your actual Razorpay Key ID:

```javascript
"key": "rzp_test_1234567890", // Your actual key
```

### Step 3: Test Mode vs Live Mode

- **Test Mode**: Use test API keys (starting with `rzp_test_`)
  - No real money is charged
  - Use test card numbers provided by Razorpay
  - Perfect for development and testing

- **Live Mode**: Use live API keys (starting with `rzp_live_`)
  - Real money transactions
  - Requires KYC verification on Razorpay
  - Only use after thorough testing

### Step 4: Test Payment Flow

**Test Card Numbers (Razorpay Test Mode):**
- **Success**: `4111 1111 1111 1111`
- **Failure**: `4012 0010 3714 1112`
- **CVV**: Any 3 digits (e.g., 123)
- **Expiry**: Any future date (e.g., 12/25)
- **Name**: Any name

**Testing Steps:**
1. Navigate to `http://localhost/Free fire 1/free-fire-tournament-platform/src/index.php`
2. Click **Join Tournament** on any tournament with entry fee > 0
3. Fill in your in-game name and Game UID
4. Click **Register Now**
5. After successful registration, click **Pay ₹X via Razorpay**
6. Razorpay popup should appear
7. Enter test card details
8. Complete payment
9. You should be redirected to dashboard with success message

## 📊 Database Updates on Payment

When payment is successful:

1. **registrations table** - `payment_status` updated to `'Paid'`
2. **payments table** - New record inserted with:
   - `payment_id` (from Razorpay)
   - `order_id` (if you implement orders)
   - `signature` (for verification)
   - `amount`
   - `status` = 'Success'

## 🎨 Features Implemented

### Tournament Join Page
- ✅ Large tournament title with gradient effect
- ✅ Status badge (Upcoming/Ongoing)
- ✅ Grid layout showing all tournament details
- ✅ Entry fee highlighted with red border
- ✅ Prize pool highlighted with gold border
- ✅ Date, time, game mode, max players, map display
- ✅ Registration form with validation
- ✅ Payment info section for paid tournaments
- ✅ Razorpay integration with popup
- ✅ Already registered check
- ✅ Mobile responsive design

### User Flow
1. **Player clicks "Join Tournament"** from index.php
2. **Sees comprehensive tournament details**
3. **Fills registration form** (in-game name, Game UID)
4. **Clicks "Register Now"**
5. **For free tournaments**: Redirects to dashboard immediately
6. **For paid tournaments**: Shows "Pay via Razorpay" button
7. **Clicks payment button**: Razorpay popup opens
8. **Completes payment**: Payment verified via `process_payment.php`
9. **Success**: Redirected to dashboard with confirmation

## 🔒 Security Features

- ✅ CSRF token protection on registration form
- ✅ Session validation (must be logged in as player)
- ✅ SQL prepared statements (prevent SQL injection)
- ✅ Razorpay signature verification (in process_payment.php)
- ✅ Already registered check (prevents duplicate registrations)
- ✅ Tournament status validation (only upcoming tournaments)

## 🚀 Next Steps

1. **Get Razorpay API keys** from dashboard
2. **Update** `src/join_tournament.php` line ~556 with your Key ID
3. **Test** payment flow with test mode keys
4. **Verify** database updates after successful payment
5. **Switch to live keys** when ready for production

## 📝 Important Notes

- Keep your **Key Secret** secure - NEVER commit it to public repositories
- Store Key Secret in environment variables or secure config files
- The Key ID (used in frontend) is public and safe to expose
- Test thoroughly in test mode before going live
- Razorpay charges transaction fees - check their pricing page

## 🆘 Troubleshooting

### Payment popup doesn't open
- Check browser console for JavaScript errors
- Verify Razorpay Checkout.js is loaded
- Ensure Key ID is correct

### Payment succeeds but database doesn't update
- Check `process_payment.php` for errors
- Verify database connection
- Check error logs: `c:\xampp\apache\logs\error.log`

### "Invalid request" error
- CSRF token mismatch - refresh the page
- Session expired - log in again

## 🎉 Success Indicators

When everything works:
- ✅ Tournament details display beautifully
- ✅ Registration form submits successfully
- ✅ Razorpay popup opens for payment
- ✅ Test payment completes
- ✅ `registrations.payment_status` = 'Paid'
- ✅ New record in `payments` table
- ✅ Redirect to dashboard with success message

---

**Need Help?** Check Razorpay documentation: https://razorpay.com/docs/
