# GV Basketball Wiki — LatePoint Booking & Auth

Documentation on the LatePoint booking engine configuration, venue schedules, and customer account login flow.

---

## 1. Booking Scheduler Configuration

The booking system operates in a **strictly informational/request capacity** (online payments are disabled, and no financial data is gathered on-site).

### Data Model & Setup
Created and managed via the setup helper `build/scripts/setup-latepoint.php`:

- **Agent:** Coach Gino Victorino (Agent ID: `1`, Email: `gvbasketballcoaching@gmail.com`)
- **Services (All at ₱0 / No Online Charge):**
  - **Player Consultation:** 45 minutes
  - **Private Training:** 60 minutes
  - **Small Group Training:** 90 minutes (Maximum capacity: 6 athletes)
  - **Elite Performance Training:** 90 minutes (Maximum capacity: 5-6 athletes)
- **Active Locations & Days:**
  - **Dasma, Makati:** Active Mon, Wed, Thu (Work hours: 15:00 - 18:00 / minutes 900–1080)
  - **Urdaneta Village:** Active Fri, Sun (Work hours: 15:00 - 18:00 / minutes 900–1080)
  - **Corinthian Gardens:** Active Sun (Work hours: 15:00 - 18:00 / minutes 900–1080)

### Settings Applied
- **Local Payments:** OFF (`enable_payments_local=off`)
- **Accent Color:** Orange (`#F47B20`)
- **Currency:** Philippine Peso (`currency_iso_code=PHP`, symbol `₱` placed before price)
- **Support Target:** Re-routed step footer support copy to Gino's Instagram (`https://ig.me/m/gvbasketballl`) and cleared the support phone field.
- **Workflow Steps:** Step selectors for location/service categories are disabled. Shortcodes default straight to forms.

---

## 2. Customer Portal & Member Login

The Member Portal at `/booking/` hosts the dashboard shortcode `[latepoint_customer_dashboard]`. 

### Passwordless OTP Authentication
Auth is entirely **email-based passwordless OTP**. There are no passwords. This forces verified-email signups.

| LatePoint Setting | Value | Rationale |
|---|---|---|
| `selected_customer_authentication_method` | `otp` | Selects OTP as the active auth method |
| `default_customer_authentication_method` | `otp` | Forces OTP load state by default |
| `selected_customer_authentication_field_type` | `email` | Selects email as target for OTP code delivery |
| `notifications_email_processor` | `wp_mail` | **CRITICAL GOTCHA:** Without this specific option set, LatePoint skips calling the standard WordPress mailer, disabling OTP deliveries and causing login queries to hang/fail silently. |
| `page_url_customer_dashboard` / `_login` | `/booking/` | Keeps users on the custom branded portal page instead of forwarding to `/customer-cabinet/` |

### Portal Scope — View-Only (free plugin) ⚠️
This install runs **free LatePoint 5.6.6 only** (no PRO add-on). That caps what the portal can do:

- **Members can VIEW** their consultation(s) and booking history — this is what the `/booking/` dashboard provides today.
- **Members CANNOT reschedule or cancel.** Customer reschedule is hard-gated behind
  `apply_filters('latepoint_is_feature_reschedule_available', false)` — a **paid** feature that is
  never enabled in the installed code, so it **cannot be turned on by a setting**
  (`allow_customer_booking_reschedule` et al. are inert). The `_booking_tile.php` Reschedule/Cancel
  buttons therefore never render. Portal copy on `/booking/` reflects this: *"view your consultation
  schedule… Need to change a day? Just message us."*
- **Self-registration is open:** any email → OTP code → account. New accounts see an empty dashboard
  until a booking exists under their email.
- **Bookings are order-linked** (`bookings.order_item_id → order_items → orders`), so a portal-visible,
  manageable booking must be created through LatePoint's normal flow — not a bare model insert.

### How a consultation reaches the portal
The public "Book a Consultation" modal (`gv-request-form.php`) only **emails** the coach; it does not
create a LatePoint booking. **Coach workflow:** when confirming the day, add the consultation in the
**LatePoint admin under the client's email**. The self-registered member (same email) is matched by
email and sees it in `/booking/`. If the paid reschedule add-on is ever purchased, self-reschedule
becomes available with no further code changes.

---

## 3. Branded OTP Email Integration

LatePoint's default OTP handler spits out plain-text emails. To match the premium brand aesthetics, the custom Must-Use plugin `gv-otp-email.php` intercepts outgoing messages:

- **Hook:** Filters `wp_mail` arguments at runtime.
- **Trigger:** Intercepts any mail whose subject contains the token "OTP".
- **Action:** Extracts the 6-digit numeric token, wraps it in a styled HTML document containing:
  - The GV crest logo (`2026/07/gv-logo-crest.png`, PNG for email-client compatibility)
  - Structured Navy and Orange brand highlights
  - Stylized monospace spacing for the code digits
  - Clear 10-minute expiry warning text
- **Output:** Changes the header content type to `text/html` and updates the payload.
- **Verify Command:** A test suite located at `build/mu-plugins/tests/test-gv-request-form.php` verifies the logic handles valid/invalid OTP sequences safely.
