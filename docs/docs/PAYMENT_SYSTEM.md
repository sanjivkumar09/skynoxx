# Payment System Documentation

## Overview
Complete payment management system for handling tournament entry fees and prize distribution.

## Features
- ✅ Player payment submission with screenshot upload
- ✅ Admin payment verification and tracking
- ✅ Prize distribution management
- ✅ Transaction history tracking
- ✅ Multiple payment methods (UPI, Card, Net Banking, Wallet)
- ✅ Real-time payment status updates
- ✅ Financial statistics dashboard

## Setup Instructions

### 1. Database Setup
Run the setup script to add payment columns:
```
http://localhost/Free%20fire%201/free-fire-tournament-platform/src/setup_payment_system.php
```

Or manually run the SQL file:
```sql
SOURCE src/sql/payment_schema.sql;
```

### 2. File Structure
```
admin/
  └── payment_management.php       # Admin payment dashboard
player/
  └── submit_payment.php            # Player payment submission
  └── player_dashboard.php          # Updated with payment status
src/
  └── setup_payment_system.php      # One-time setup script
  └── sql/
      └── payment_schema.sql        # Database schema
```

### 3. Required Database Columns

**registrations table:**
- `payment_status` VARCHAR(50) DEFAULT 'Pending'
- `payment_method` VARCHAR(50) NULL
- `transaction_id` VARCHAR(100) NULL
- `payment_screenshot` VARCHAR(255) NULL
- `payment_date` TIMESTAMP NULL
- `prize_won` DECIMAL(10,2) DEFAULT 0.00
- `prize_status` VARCHAR(50) DEFAULT 'Not Won'

**players_profile table:**
- `upi_id` VARCHAR(100) NULL (for prize distribution)

## User Workflows

### Player Payment Workflow
1. Player registers for tournament
2. Joins tournament (creates registration record with status 'Pending')
3. Clicks "Pay Now" button in dashboard
4. Redirected to `submit_payment.php?reg_id=X`
5. Makes payment via UPI/Card/Net Banking
6. Uploads payment screenshot
7. Enters transaction ID
8. Submits form
9. Payment status changes to 'Pending' (awaiting verification)
10. Admin verifies and updates to 'Completed'

### Admin Payment Verification Workflow
1. Admin logs into `admin/payment_management.php`
2. Views all registrations with payment details
3. Sees pending payments highlighted
4. Clicks "Update Payment" for each registration
5. Verifies payment screenshot (opens in modal)
6. Updates payment status (Pending/Completed/Failed)
7. Adds/verifies transaction ID
8. Saves changes
9. Player sees updated status in dashboard

### Prize Distribution Workflow
1. Tournament completes
2. Creator/Admin determines winners
3. Admin opens payment management
4. Clicks "Distribute Prize" for winner's registration
5. Enters prize amount
6. Verifies player's UPI ID (from profile)
7. Transfers money externally (via UPI/Bank)
8. Marks prize as "Distributed" in system
9. Player sees prize amount in dashboard

## Payment Status Options
- **Pending**: Payment submitted, awaiting admin verification
- **Completed**: Payment verified and confirmed
- **Failed**: Payment not received or verification failed
- **Not Paid**: Player has not submitted payment yet

## Prize Status Options
- **Not Won**: Player did not win any prize
- **Pending**: Prize allocated but not yet distributed
- **Distributed**: Prize successfully transferred to player

## Integration with Payment Gateways

### Razorpay Integration (Recommended for India)
```php
// In submit_payment.php, add Razorpay checkout
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    "key": "YOUR_RAZORPAY_KEY",
    "amount": <?= $registration['entry_fee'] * 100 ?>, // in paise
    "currency": "INR",
    "name": "Free Fire Tournament",
    "description": "Tournament Entry Fee",
    "handler": function (response){
        // Auto-fill transaction ID
        document.querySelector('input[name="transaction_id"]').value = response.razorpay_payment_id;
        // Auto-update payment status to Completed
    }
};
var rzp1 = new Razorpay(options);
</script>
```

### PayU Integration
```php
// Add PayU hash generation
$productInfo = "Tournament Entry Fee";
$hash = hash('sha512', $key.'|'.$txnid.'|'.$amount.'|'.$productInfo.'|'.$firstname.'|'.$email.'|||||||||||'.$salt);
```

### UPI Direct Integration
- Display UPI QR code
- Use UPI deep links: `upi://pay?pa=merchant@upi&pn=MerchantName&am=500&cu=INR`
- Auto-verify using UPI transaction ID

## Security Features
- ✅ Session-based authentication
- ✅ Role-based access control (admin/player)
- ✅ SQL injection prevention (prepared statements)
- ✅ File upload validation (image types only)
- ⚠️ Add CSRF token protection (recommended)
- ⚠️ Add rate limiting for payment submissions
- ⚠️ Add IP logging for audit trail

## Enhancement Ideas

### Phase 1: Basic Improvements
1. Add email notifications for payment confirmations
2. Add SMS notifications for prize distributions
3. Create payment receipt PDF generation
4. Add refund management for cancelled tournaments
5. Create player payment history page

### Phase 2: Advanced Features
1. Integrate automated payment gateways (Razorpay/PayU)
2. Add wallet system for players
3. Implement auto-refund on tournament cancellation
4. Add payment reconciliation reports
5. Create financial analytics dashboard
6. Add export functionality (CSV/PDF)

### Phase 3: Enterprise Features
1. Multi-currency support
2. Tax calculation and invoicing
3. Split payments for team tournaments
4. Escrow system for prize pool
5. Automated prize distribution via API
6. Fraud detection system
7. Compliance and audit logs

## API Endpoints (Future)
```
POST /api/payments/create        - Create payment order
POST /api/payments/verify        - Verify payment
GET  /api/payments/:id           - Get payment details
POST /api/prizes/distribute      - Distribute prize
GET  /api/transactions/player    - Player transaction history
GET  /api/transactions/admin     - Admin transaction reports
```

## Testing Checklist
- [ ] Run setup_payment_system.php successfully
- [ ] Verify all database columns exist
- [ ] Player can submit payment with screenshot
- [ ] Admin can view all payments in dashboard
- [ ] Admin can update payment status
- [ ] Admin can distribute prizes
- [ ] Payment status displays correctly in player dashboard
- [ ] "Pay Now" button shows only for pending payments
- [ ] Prize amounts display correctly
- [ ] File uploads work properly
- [ ] Form validations work
- [ ] Mobile responsive design works

## Troubleshooting

### "Column doesn't exist" error
Run `setup_payment_system.php` or manually add columns from `payment_schema.sql`

### Screenshot not uploading
- Check `src/uploads/payments/` directory exists
- Verify folder permissions (777 on Linux)
- Check PHP upload_max_filesize setting

### Payment status not updating
- Verify admin is logged in with correct role
- Check SQL syntax in update queries
- Check browser console for JavaScript errors

### "Registration not found" error
- Verify registration_id is passed in URL
- Check if registration belongs to logged-in player
- Verify JOIN conditions in SQL query

## File Permissions
```bash
chmod 755 admin/payment_management.php
chmod 755 player/submit_payment.php
chmod 777 src/uploads/payments/
```

## Support
For issues or questions:
1. Check browser console for errors
2. Check PHP error logs
3. Verify database columns exist
4. Test with sample data
5. Review payment workflow diagrams

## Version History
- v1.0.0 - Initial payment system with manual verification
- v1.1.0 - Added screenshot upload and transaction tracking
- v1.2.0 - Added prize distribution management
- v2.0.0 - (Planned) Payment gateway integration
