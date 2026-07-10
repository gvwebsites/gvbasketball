# GV Basketball Wiki — Forms & Email Routing

Documentation of standard WPForms, transactional mail servers, and the custom global consultation modal.

---

## 1. Transactional Mail Routing (FluentSMTP)

All site-generated mail (latepoint OTPs, waiver notifications, consultation responses) is routed via FluentSMTP connected to Google OAuth.

- **Sender Address:** `info@gvbasketball.com` (configured as a "Send mail as" alias inside Gmail account `gvictorino.websites@gmail.com`).
- **GCP API Scope:** Connected to GCP API Project `gvbasketball` via Gmail API.
- **Secrets Management:** The Google OAuth Client ID and Secret are defined in `wp-config.php` as PHP constants:
  - `FLUENTMAIL_GMAIL_CLIENT_ID`
  - `FLUENTMAIL_GMAIL_CLIENT_SECRET`
- **Recipient Address:** All admin alerts and form notifications deliver directly to **`gvbasketballcoaching@gmail.com`**.

> [!WARNING]
> The OAuth client secret was historically printed to terminal output during configuration. If credentials need cycling, reset them in Google Cloud Console, update `.env`, and apply with `wp config set --quiet` to prevent logging secrets.

---

## 2. WPForms Lite Directory

WPForms Lite is utilized for simple form capture. Since it is the Lite version, a plain text field is used for phone entries (as the native Phone field requires a Pro license).

- **Contact Form (ID: 3003):** Located on the `/contact/` page. Routes entries to `gvbasketballcoaching@gmail.com`.
- **Waiver Form (ID: 3007):** Located on the `/waiver/` page. Reassures parents/athletes and compiles signoffs. Deliveries go to `gvbasketballcoaching@gmail.com`.
- **Newsletter Form (ID: 3005):** Associated with the footer newsletter segment. Currently deactivated/hidden, but configuration is preserved.

---

## 3. Consultation Form & Modal (`gv-request-form.php`)

To replace direct public booking on LatePoint, a highly structured, custom "Book a Consultation" form is delivered via the must-use plugin `gv-request-form.php`.

### Behavior & Injection
- **Global Injection:** The modal wrapper HTML and initialization scripts are injected globally via the `wp_footer` hook.
- **Triggers:** Any element on the website with the attribute `data-gv-open-modal` automatically intercepts clicks and fades in the consultation modal.
- **Query Parameter Auto-Open:** If a user lands on a page with `?gv_open_modal=1` in the URL (e.g. redirected from the legacy `/book-a-consultation/` page), the modal script fires and opens the modal immediately.

### Form Fields & Validation
1. **Parent Name** (required)
2. **Player Name & Age** (required)
3. **Email Address** (required)
4. **Phone / Instagram Handle** (required)
5. **Training Type** (Select: Private, Small Group, Elite Performance, Consultation)
6. **Location Dropdown:** Dashma Makati, Urdaneta Village, Corinthian Gardens, or Any.
7. **Day Selection Chips:** Seven checkbox buttons (Mon–Sun).
   - **Dynamic Filter:** A client-side JavaScript IIFE checks the selected location and hides invalid days. For example, selecting "Dasma, Makati" hides all days except Monday, Wednesday, and Thursday.
8. **Preferred Time / Notes** (Optional)

### Anti-Abuse Controls
- **WordPress Nonce:** Security token validation.
- **Honeypot:** Hidden field to catch basic bot submissions.
- **Cloudflare Turnstile:** Invisible challenge validation. Keys are loaded from `.env`.

### Notifications & Auto-Reply
On success, `gv-request-form.php` constructs two mail payloads sent via `wp_mail()`:
- **Admin Alert:** Sent to `gvbasketballcoaching@gmail.com` with parent/player stats, chosen location, and days. It includes an **"Add to Google Calendar" action link** styled as a button:
  - Automatically calculates the soonest strictly-future date matching any of the selected weekdays using `gv_rf_next_weekday_date()`.
  - Generates a prefilled Google Calendar template URL using `gv_rf_gcal_url()`, placing the parent's email as a guest (`add`), setting the location, and formatting all submission details into the description. Description line breaks use `<br>` (Google Calendar renders the description as HTML — raw `\n` collapses to nothing).
- **Branding:** both emails use the branded shell with the **GV crest logo** (`2026/07/gv-logo-crest.png`, a PNG since email clients don't render SVG). The old `2025/07/GV_Logo_Main.png` is retired.
- **Parent Auto-Reply:** Sent to the user's email confirming receipt, highlighting the chosen venue details, and stating the coaching team will follow up via IG/Email.

### Verification CLI Test
Run the framework-free assertion suite on the local terminal:
```bash
php build/mu-plugins/tests/test-gv-request-form.php
```
This tests the location-day data model, validation filters, nonce exceptions, markup builders, and the new Google Calendar date/URL generation functions.

