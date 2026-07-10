# Self-Service Consultation Booking via LatePoint — Design

**Date:** 2026-07-10
**Status:** Superseded by `2026-07-10-members-self-service-consultation-merged-design.md`
**Site:** https://gvbasketball.com — WordPress + LatePoint 5.6.6 (FREE) on Hostinger

---

## Context

Today the public **"Book a Consultation"** button opens a custom email modal
(`gv-request-form.php`) that only *emails* the coach — it does **not** create a LatePoint
booking. The member portal (`/booking/`) is LatePoint, but it only shows bookings the coach
adds by hand.

**New direction (this spec):** the "Book a Consultation" button should create a **real
LatePoint consultation booking** through a brand-themed LatePoint wizard ("pick when/where
first, details after"), with an optional member-account opt-in. Because the booking is a real
LatePoint record keyed by email, it **auto-appears in the member portal** — the manual
"coach adds it" step disappears, and "email = identity" is already how LatePoint OTP works.

> **This reverses a prior decision.** The 2026-07-10 member-login work chose *coach-creates-at-
> confirmation* and kept the email modal. This spec supersedes that for the **public booking
> path**. The member portal stays **view-only** (customer reschedule is a paid LatePoint add-on,
> off on this install — see `wiki/booking-latepoint.md`).

## Decisions locked with the client

| # | Decision | Choice |
|---|---|---|
| 1 | **What the visitor books** | **Day only** — pick venue + day; booking created for that day; **coach assigns the exact time later**. (LatePoint always stores a start_time, so configure one slot/day and treat/label its time as nominal — see Risk R3.) |
| 2 | **Form UI** | **Themed LatePoint native wizard** — restyle LatePoint's own booking form to GV brand, opened in the existing pop-up modal. Its native order is already "slot → details." No bespoke API-driven UI. |
| 3 | **"Create account after"** | **Opt-in checkbox** in the details step: "Create my member account." (Note: LatePoint creates a customer by email on every booking regardless; the checkbox governs whether we *surface/nudge* the portal + send a "you can log in" note — see §Account.) |
| 4 | **Anti-spam / no-show gate** | **Both** — email **OTP verification** before the booking finalizes (native to LatePoint when auth = otp) **plus** Cloudflare **Turnstile** on the form (belt-and-suspenders). |

Defaults chosen by the implementer (not separately asked; change if the client objects):
- Keep the current detail fields (player name, player age, training interest, notes) as LatePoint
  **custom booking fields** in the details step.
- **Preserve the branded coach email + "Add to Google Calendar" button** via a LatePoint
  notification hook, reusing `gv_rf_gcal_url()` / `gv_rf_email_shell()`.
- Only the **Player Consultation** service is publicly bookable; Private/Small Group/Elite stay
  non-public (they are handled off-site). Payments stay OFF; pricing never shown.

## Current state (for the fresh agent)

- **LatePoint 5.6.6 free**, payments OFF. Config in `build/scripts/setup-latepoint.php`
  (agent Coach Gino; venues **Dasma/Urdaneta/Corinthian** with day patterns Mon/Wed/Thu,
  Fri/Sun, Sun; work periods 15:00–18:00; services incl. **Player Consultation** 45 min).
- **Passwordless email-OTP auth already enabled** (`build/scripts/enable-member-auth.php`):
  `selected_customer_authentication_method=otp`, field=email, `notifications_email_processor=wp_mail`.
- **Branded OTP email:** `build/mu-plugins/gv-otp-email.php` (intercepts OTP mail at `wp_mail`).
- **Member portal:** page **2983** `/booking/` = `[latepoint_customer_dashboard]`, brand-wrapped
  (`.gv-dash-wrap`); **view-only** (reschedule/cancel are gated paid features, off).
- **Custom booking modal (to retire for public booking):** `build/mu-plugins/gv-request-form.php`
  — global `wp_footer` modal + `[gv_request_form]`, Turnstile + branded emails + the
  `gv_rf_gcal_url()` / `gv_rf_next_weekday_date()` / `gv_rf_email_shell()` helpers. Page **2982**
  `/book-a-consultation/` currently 302-redirects to `/training-programs/?gv_open_modal=1`.
- **Deploy / access / helpers:** see `wiki/deployment-workflows.md`, `wiki/access-and-hosting.md`
  (`ssh gvweb`; WP root `/home/u907133977/domains/gvbasketball.com/public_html`; `wp db export`
  fails → table-scoped backups; filter SSH post-quantum noise). Brand tokens: `wiki/design-system.md`.

## Target user flow

1. Visitor clicks any **"Book a Consultation"** button → LatePoint booking **popup** opens with the
   **Player Consultation** service preselected.
2. **Step 1 — Where & when:** pick **venue** → pick a **day** (one bookable slot per available day;
   time is nominal / relabeled). Progress indicator shows steps.
3. **Step 2 — Details:** name, email, phone/IG (optional) + custom fields (player name, player age,
   training interest, notes). Inline (on-blur) validation; real `<label>`s. **"Create my member
   account"** checkbox. **Turnstile** widget.
4. **Verify:** enter the **6-digit email code** (LatePoint OTP) → booking finalizes. Loading →
   success state.
5. **Confirmation:** branded on-screen confirmation + emails. If the account checkbox was ticked,
   show a "View/manage your booking → `/booking/`" CTA and note they can log in with their email code.
6. The booking now appears in that member's **`/booking/`** portal (view-only). Coach receives a
   **branded email with the "Add to Google Calendar" button** and later sets the exact time.

## Design & implementation

### A. LatePoint configuration (`build/scripts/setup-latepoint.php`, re-runnable)
- Configure **Player Consultation** to expose **one bookable slot per working day** per venue: set
  duration to the full window (180 min) and `timeblock_interval` 180 so exactly one slot/day
  appears. Keep capacity 1, price 0, visibility public.
- Keep venues + day-pattern work periods as-is.
- Set `default_booking_status` (approved vs pending) — recommend **approved** for day-only self
  service (coach sets time after). Confirm with client if they'd prefer pending.
- Ensure only Consultation is offered in the public wizard (preselect service; hide others from the
  service step, or mark the training services non-public).

### B. Public entry — swap modal → LatePoint popup
- Replace the global consultation modal trigger so **`[data-gv-open-modal]` / "Book a Consultation"**
  buttons open **LatePoint's booking popup** with the Consultation service preset (LatePoint
  `[latepoint_book_button]` / its JS trigger; params to preset service + limit steps).
- **Retire** the `gv-request-form.php` public modal (remove the `wp_footer` modal + the
  `[gv_request_form]` render). **Keep the helper functions** (`gv_rf_gcal_url`,
  `gv_rf_next_weekday_date`, `gv_rf_email_shell`) — move them into a small shared mu-plugin (e.g.
  `gv-consult-notify.php`) reused by the LatePoint notification hook (§F). Point page **2982** at
  the LatePoint booking form (or keep the button-opens-popup pattern site-wide).

### C. Themed wizard UI (ui-ux-pro-max) — `gv-brand.css`
Heavily style LatePoint's wizard markup (`.latepoint-*`) to GV brand — navy `#123B78` / orange
`#F47B20`, Bebas Neue headings, Montserrat/Inter body — inside the `.gv-bookform-wrap` popup.
Bake in these UX rules (from the ui-ux-pro-max UX set):
- **Progress indicator** ("Step 2 of 3") — LatePoint shows steps; ensure it's visible + branded.
- **Inline validation on blur**, not submit-only.
- **Submit feedback:** loading → success/error state (LatePoint provides; brand it).
- **Real `<label>`s** (no placeholder-only), 44×44px touch targets, visible focus rings, 16px+ inputs.
- Match the existing consultation-modal look as closely as LatePoint's markup allows.

### D. Custom fields (details step)
Add LatePoint **custom booking fields**: player name (req), player age (req, 4–25), training
interest (select: Private / Small Group / Elite), notes (optional). Age/select validation may need
light JS if LatePoint's field validation is thin.

### E. Gate — OTP + Turnstile
- **OTP:** with `authentication_method=otp` already on, the wizard's customer step should require
  email → code → verify before completing. **VERIFY this fires inside the booking flow** (Risk R1).
- **Turnstile:** inject the Turnstile widget into the wizard's details step and **verify server-side**
  on booking submit via a LatePoint booking-validation hook (reuse `GV_TURNSTILE_*` from `.env` and
  the verify logic in `gv-request-form.php`). **Confirm a hook exists** to block the booking on
  failure (Risk R2). If OTP proves sufficient and Turnstile can't cleanly hook the wizard, fall back
  to OTP-only and tell the client.

### F. Branded coach email + Google Calendar button
Hook LatePoint's "booking created" notification (a `latepoint_*` action/filter, or the `wp_mail`
layer) to send the coach a **branded email** (reuse `gv_rf_email_shell`) including the **"Add to
Google Calendar"** button (`gv_rf_gcal_url`, all-day on the booked day, parent as guest — logic
already built & tested). Brand the **customer confirmation** email too (LatePoint templates or
`wp_mail` filter).

### G. Account opt-in semantics
LatePoint creates a customer (by email) on every booking. The checkbox therefore controls **UX, not
data**: if ticked → post-booking "your account is ready — view/manage at `/booking/`" CTA + a nudge
that they log in with their emailed code; if unticked → no portal push. Document this so the client
isn't surprised that an account technically always exists. Email remains the sole identity (no
passwords).

## Member portal impact
No portal rebuild. Self-booked consultations (real LatePoint bookings) now **auto-appear** in
`/booking/` for the matching email. Still **view-only** — to change a day the member messages the
team (reschedule is the paid add-on). The 2026-07-10 view-only copy stays accurate.

## Files to touch
- `build/scripts/setup-latepoint.php` — one-slot-per-day Consultation + booking status.
- `build/templates/header.html`, `build/templates/footer.html`, `build/scripts/build-functional.php`
  — point "Book a Consultation" triggers at the LatePoint popup; retire the custom modal render.
- `build/mu-plugins/gv-request-form.php` → split out helpers into `gv-consult-notify.php` (LatePoint
  notification hook + Turnstile verify + gcal email); stop rendering the public modal.
- `build/mu-plugins/gv-assets/gv-brand.css` — theme the LatePoint wizard (`.latepoint-*`).
- `build/mu-plugins/tests/` — extend the framework-free tests for the retained helpers.
- Wiki: `booking-latepoint.md`, `forms-and-emails.md`, `pages.md`, `client-status.md`, `log.md`.

## Verify FIRST (Stage 0 — before building)
Do these on the live server (read-only) and confirm, exactly as prior LatePoint work did:
- **R1 — OTP-in-booking:** does LatePoint's **booking wizard** (not just the dashboard login)
  enforce the email-OTP verify step when `authentication_method=otp`? Inspect
  `wp-content/plugins/latepoint/lib/controllers/*` + views for the booking flow. If not, scope the
  OTP-verify step explicitly.
- **R2 — Turnstile hook:** is there a LatePoint filter/action to **reject a booking** server-side
  (for Turnstile verification) and to inject markup into the wizard's final step? If none is clean,
  fall back to OTP-only.
- **R3 — Day-only display:** LatePoint always stores a `start_time`. Decide how the single daily slot
  is labeled so it reads as "reserve this day" rather than a hard clock time (CSS/label or a nominal
  time the coach overrides). Confirm the portal shows it acceptably.
- Also reconfirm: only free `latepoint` active (no paid add-on), page IDs 2982/2983, deploy access.

## Verification (end-to-end, after build)
1. Config: re-run `setup-latepoint.php`; Consultation shows **one slot per working day** per venue.
2. Public flow: a "Book a Consultation" button opens the **themed** LatePoint popup (Consultation
   preset); pick venue + day → details (custom fields + account checkbox + Turnstile) → email OTP →
   booking confirms. Bad/absent Turnstile or OTP blocks it.
3. Data: a real LatePoint booking + customer (by email) now exist; coach gets the **branded email with
   a working Google Calendar button**; parent gets a branded confirmation.
4. Portal: self-register/login at `/booking/` with the same email → the consultation is listed
   (view-only, no reschedule button).
5. Account checkbox ticked vs unticked changes only the post-booking portal nudge.
6. Accessibility/UX: progress indicator, on-blur validation, labels, focus rings, 44px targets, no
   horizontal scroll at 375px. No pricing/payment surfaced anywhere.

## Out of scope
- Customer self-reschedule/cancel (paid LatePoint add-on).
- Public self-booking of Private/Small Group/Elite training (stay off-site).
- Online payments / bank details on the site.
