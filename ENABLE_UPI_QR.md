# Enable UPI QR Code in Razorpay

## ✅ Changes Made to Your Website

I've updated your code to:
1. ✅ Changed business name from "SKYNOXX Free Fire" to "SKYNOXX"
2. ✅ Enabled UPI QR code payment option
3. ✅ Updated both player and creator wallet deposit pages

## 🔧 Enable UPI QR in Razorpay Dashboard

### Step 1: Log in to Razorpay Dashboard
- Go to: https://dashboard.razorpay.com/
- Make sure you're in **Test Mode** (blue toggle)

### Step 2: Enable UPI Payment Method
1. Go to: **Settings** → **Payment Methods**
   - Or direct link: https://dashboard.razorpay.com/app/payment-methods

2. Find **UPI** in the list

3. Make sure UPI is **Enabled** (toggle should be ON/blue)

4. Click on **UPI** to expand settings

5. Enable these UPI flows:
   - ✅ **UPI QR** (Scan QR code to pay)
   - ✅ **UPI Intent** (Opens UPI apps directly)
   - ✅ **UPI Collect** (Enter VPA/UPI ID)

6. Click **Save** or **Update**

### Step 3: Test QR Code Payment

1. Go to your wallet deposit page
2. Enter amount (₹100)
3. Click "Proceed to Payment"
4. You should now see:
   - **Pay using UPI** section at the top
   - **QR Code** option
   - **UPI Apps** (PhonePe, GPay, Paytm)
   - **Enter UPI ID** option

### Step 4: Test with Test UPI IDs

In test mode, use these:
- `success@razorpay` → Payment succeeds
- `failure@razorpay` → Payment fails

**Note:** QR code scanning won't work in test mode, but you'll see the QR code UI. Use test UPI IDs instead.

## 📱 What You'll See Now

Before:
- Name: "SKYNOXX Free Fire"
- Payment options appeared in random order
- No dedicated UPI section

After:
- Name: "SKYNOXX" ✅
- UPI section appears first ✅
- QR code option visible ✅
- Better organized payment methods ✅

## 🎯 Production (Live Mode)

When you switch to live mode:
1. Real UPI QR codes will work
2. Users can scan with any UPI app (GPay, PhonePe, Paytm, etc.)
3. Payment happens instantly
4. QR expires after 5 minutes

## 🔍 Troubleshooting

### UPI QR not showing?
- ✅ Check Razorpay Dashboard → Settings → Payment Methods
- ✅ Ensure UPI is enabled with all flows (QR, Intent, Collect)
- ✅ Clear browser cache and try again
- ✅ Test in incognito mode

### QR code but can't scan in test mode?
- This is normal! Test mode QR codes are not scannable
- Use test UPI IDs instead: `success@razorpay`
- Real scanning works in live mode only

### Still showing old name "SKYNOXX Free Fire"?
- ✅ Clear browser cache (Ctrl + Shift + Delete)
- ✅ Hard refresh (Ctrl + F5)
- ✅ Restart Apache in XAMPP

## ✅ Summary

Files Updated:
- ✅ `src/razorpay_config.php` - Business name changed to "SKYNOXX"
- ✅ `player/wallet_deposit.php` - UPI QR enabled + name updated
- ✅ `creator/wallet_deposit.php` - UPI QR enabled + name updated

What's New:
- UPI QR code payment option
- Better payment method organization
- Cleaner business name
- User contact prefill (phone/email)

**Ready to test!** 🚀
