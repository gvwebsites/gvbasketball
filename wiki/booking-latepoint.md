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

## 2. Members Portal & OTP Login (`/members/`)

The GV Members Training Journal lives at **`/members/` (page 2983)** via the `[gv_members_portal]`
shortcode from the `gv-members` mu-plugin. (The old `/booking/` and `/customer-cabinet/` paths 301
to `/members/`.)

### Passwordless OTP Authentication
Auth is entirely **email-based passwordless OTP** handled by custom AJAX endpoints
(`gv_otp_request` / `gv_otp_verify` in `gv-members/auth.php`) built on LatePoint's `OsOTPHelper`.
There are no passwords and no WP user accounts — any verified email becomes a LatePoint customer
record on demand. Rate limits: 5 sends/email/hour, 10 sends/IP/hour. `WP_Error` returns from the
OTP helpers are treated as failures.

| LatePoint Setting | Value | Rationale |
|---|---|---|
| `notifications_email_processor` | `wp_mail` | **CRITICAL GOTCHA:** Without this option, LatePoint skips the WordPress mailer entirely — OTP deliveries silently fail. |

### Portal Scope — View-Only (free plugin) ⚠️
This install runs **free LatePoint 5.6.6 only** (no PRO add-on):

- **Members can VIEW** their consultation requests (Submitted) and confirmed sessions (Confirmed,
  with exact times and ICS calendar links) in the Training Journal.
- **Members CANNOT reschedule or cancel** — customer reschedule is hard-gated behind
  `apply_filters('latepoint_is_feature_reschedule_available', false)`, a paid feature. Portal copy
  says to message the team instead.
- **Bookings are order-linked** (`bookings.order_item_id → order_items → orders`), so a
  portal-visible booking must be created through LatePoint's normal flow — not a bare model insert.

---

## 3. Consultation Wizard (modal-only, venue-scoped)

The public booking flow is **modal-only** — there is no booking page. Consultation CTAs sit in the
header/footer sitewide; `/book-a-consultation/` (page 2982, drafted) 301s to `/training-programs/`.

### How it works (`gv-members.php` + `gv-members/booking.php`)
1. `wp_footer` prints **one hidden `[latepoint_book_button]` trigger per active venue** with
   `selected_location="N"` plus an extra trigger with `selected_location="any"`
   (`LATEPOINT_ANY_LOCATION`), all with `hide_side_panel="yes" hide_summary="yes"` (no side
   panel, no Summary panel).
2. Clicking a consultation CTA opens the **GV venue chooser dialog** (one button per venue plus
   "I don't have a venue yet" → the `any` trigger). The chooser stays open with a loading state
   until the LatePoint lightbox form is actually in the DOM, then closes.
3. The wizard shows **one "BOOK A CONSULTATION" action per available day** (nominal 15:00 start;
   the exact time is still coordinated later by the coach);
   custom fields (player name/age, training interest, phone/Instagram, note) are injected via the
   `latepoint_booking_steps_contact_after` hook — signature is `($customer, $booking)`.
4. Submission creates a real **pending** LatePoint booking (order-linked), emails the parent a
   branded receipt and Coach Gino a request email with a **tokenized finalize link**.
5. Coach finalizes the exact 45-minute time on the finalize screen
   (`/members/?gv_finalize_consultation=…`) → booking becomes **approved** with real start/end.
   **No automatic final email is sent to the parent** — the coach contacts them personally
   (stated in the coach email).

### PRO-only constraints & schedule gotchas ⚠️
- **`booking__locations` step is LatePoint PRO-only** — core strips it from the step order, so
  venue choice must happen *before* the wizard opens (hence the chooser + `selected_location`
  presets, which work fine in core).
- **The default work schedule must be zeroed** (`configure-members-consultation.php` does this via
  `OsWorkPeriodModel`): LatePoint falls back to the default schedule whenever a venue has no
  location-specific work-period row, which would otherwise open unintended days on every venue.
- `steps_settings` may retain a stale `booking__locations` key — the config script cleans it.

---

## 4. Branded OTP Email Integration

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
