# Payment Gateway Compliance Pack (Universal)

This compliance pack is designed to satisfy common onboarding requirements of major payment gateways and aggregators (Razorpay, PhonePe, Paytm, Cashfree, Stripe, PayPal, etc.) for a digital services business (online esports/tournament platform).

Use this page as your master checklist. Ensure all linked policy pages are publicly accessible from your production domain without login.

Last updated: 2025-11-02

---

## 1) Business Profile (What reviewers need to see)

- Legal/Trade name: SKYNOXX (update if different)
- Nature: Online esports/tournament management (digital services). No gambling/lottery/betting.
- Model: Users deposit money to wallet, pay entry fees for skill-based tournaments, prizes credited to winners' wallets, wallet withdrawals to UPI/bank
- Fulfilment: Purely digital service; no physical shipment. Any merchandise promotions follow the Shipping Policy
- Customer support email: gameshear09@gmail.com
- Registered/operational address: Hanuman Mandir, Khairpur 496001, Usrot BO, Chhattisgarh 496001, India
- Platform URLs (replace with your production domain):
  - Terms: https://your-domain.com/src/terms.php
  - Privacy: https://your-domain.com/src/privacy.php
  - Refund/Cancellation: https://your-domain.com/src/refund.php
  - Shipping/Delivery: https://your-domain.com/src/shipping.php
  - Compliance Declaration: https://your-domain.com/src/compliance.php
  - Contact: https://your-domain.com/src/contact.php

Notes for reviewers (embed in Compliance page):
- Skill-based tournaments only; strictly no betting/lottery/chance games
- Age restriction enforced (13+) and KYC/AML on suspicious activity
- No storage of card details on our servers; PCI-compliant processing by payment partners

---

## 2) Mandatory Policies (must-have pages)

Payment gateways expect these pages, clearly written and reachable:

1. Terms of Service
   - Prohibited uses, age restriction, KYC/AML, chargeback & disputes, jurisdiction
   - Clear business model: digital tournament services, wallet system, prize distribution

2. Privacy Policy
   - Personal data collected, purpose, security, data retention, cookies, third-parties (payment processors)
   - Statement that you do not store card data, only tokens from the gateway

3. Cancellation & Refund Policy
   - When refunds apply (e.g., tournament cancellation before start)
   - Refund SLA (e.g., processed within 2 business days)
   - Non-refundable components (service fees)

4. Shipping & Delivery Policy
   - Digital delivery statement for tournament services (instant/near-immediate)
   - If sending physical gifts/merchandise, include timelines, carriers, locations served

5. Contact/Grievance
   - Contact email, address, and (ideally) phone for support
   - Escalation/Grievance contact

All of the above pages already exist in this repo under /src and are styled. Keep them public.

---

## 3) Universal Compliance Declarations (include on Compliance page)

- We operate skill-based esports tournaments; no gambling/betting/lottery or games of chance
- We do not sell age-restricted products/services; users must be 13+ (or local law’s minimum age)
- We do not process or store cardholder data on our servers; PCI-compliant gateways handle payments
- We perform risk checks and may request KYC before withdrawals
- Anti-money-laundering controls: limits, velocity checks, suspicious activity flags, refunds to original source where required
- Prohibited activities: cheating, fraud, exploiting payments, illegal content; accounts can be suspended and funds held pending investigation
- Disputes & chargebacks: we cooperate with payment partners; provide logs, registration records, match results; we respect chargeback schemes
- Fulfilment: upon successful registration, tournament slot allocation and service access is provided digitally; winner prizes are credited to in-app wallet

---

## 4) What to submit in gateway onboarding forms

- Business model: “Online esports/tournament platform (digital services), skill-based competitions. Users pay entry fees; winners receive prize credits in wallet, redeemable via UPI/bank.”
- Category: Digital services / Online gaming (skill-based) / Event tickets (choose closest available) — never select Gambling/Betting
- Policy URLs: Provide the production domain links listed above
- Live screenshots: Home page, Terms/Privacy/Refund pages, a sample tournament listing, and a sample registration/checkout flow (with test mode)
- Address Proof / KYC docs: As per gateway requirements (Govt ID, PAN, GST if applicable)

---

## 5) Technical and UX Do’s (reviewers check these)

- Policy links in footer across the site (Terms, Privacy, Refunds, Shipping, Compliance)
- Contact details visible (email/address and optional phone)
- Checkout shows: price/fee, currency, refund note link, and support contact link
- No broken links; pages render without login and without 404s
- Domain is HTTPS and the exact same domain you submit in the onboarding

---

## 6) Chargebacks, Disputes, AML (copy for Terms/Compliance)

- Chargebacks: For unauthorized or disputed transactions, users should contact support promptly. We will cooperate with the payment partner and supply logs. If a chargeback is adjudicated in the user’s favor, funds are reversed to the original source.
- Dispute resolution: In-app ticket first; if unresolved, escalate to support email; courts of India have jurisdiction.
- AML: We may place holds, reverse transactions, and request KYC for suspicious or high-risk activity. We do not permit use of the platform for money laundering or illegal activities.

---

## 7) Maintenance Checklist

- [ ] Replace `https://your-domain.com` with your live domain in `/src/terms.php`, `/src/privacy.php`, `/src/refund.php`, `/src/shipping.php`, `/src/compliance.php`
- [ ] Ensure footer shows links to Terms, Privacy, Refunds, Shipping, and Compliance
- [ ] Double-check contact email and address are correct and consistent across pages
- [ ] Keep dates updated (auto or manual)
- [ ] Re-run onboarding with fresh screenshots after going live

---

## 8) Appendix: Why applications get rejected

Common reasons gateways reject:
- Missing public Terms/Privacy/Refund pages or not linked in the website
- Business model described ambiguously (sounds like betting/lottery)
- No contact details or fake information
- Domain mismatch (submitting a different domain than the one used in the site)
- Attempting to store card data directly (never do this)

This pack addresses those issues across providers.
