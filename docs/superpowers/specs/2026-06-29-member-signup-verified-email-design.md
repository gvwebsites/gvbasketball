# Member Login: Signup with Verified Email — Design

**Date:** 2026-06-29
**Status:** Implemented 2026-06-29 (verified OTP send to test@favor.church)
**Site:** https://gvbasketball.com (WordPress + LatePoint 5.6.3 on Hostinger)

## Goal

The nav "Member Login" must offer a **signup** path, and every signup must have a
**verified email address**. Achieve this using LatePoint's built-in customer
authentication — no custom code.

## Background (current state, verified on server)

- Nav "Member Login" (`build/templates/header.html`) links to `/booking/`
  (page **2983**, GV-styled), which renders `[latepoint_customer_dashboard]`.
- Customer auth is already enabled on the email field
  (`selected_customer_authentication_field_type = email`), so Login/Register tabs
  already render — but `selected_customer_authentication_method` is unset, so it
  defaults to **`password`**: anyone can sign up with email + any password,
  **no email verification**.
- LatePoint supports **email OTP** (one-time 6-digit code) natively. OTP is
  generated server-side, hashed, emailed via the existing FluentSMTP/Gmail setup,
  and expires in 10 minutes. Completing signup/login requires receiving the code
  in that inbox → the email is verified by construction.
- A second, **bare/unstyled** dashboard page exists at `/customer-cabinet/`
  (page **2980**, default LatePoint block).
- LatePoint's redirect URLs `page_url_customer_dashboard` and
  `page_url_customer_login` both point at the bare `/customer-cabinet/` page, so a
  member who logs in is currently sent to the unstyled page rather than the
  branded `/booking/` page the nav uses.

## Decision

Chosen by client:
- **Auth method:** Email code only (passwordless OTP). Sign up & log in by entering
  an email and the 6-digit code sent to it. No passwords.
- **Include Part B:** point login redirects at the branded `/booking/` page.
- **Keep** the bare `/customer-cabinet/` page (2980) published as a harmless,
  unlinked fallback.

## Changes

All changes are **LatePoint settings only** (rows in `wp_latepoint_settings`).
No HTML, CSS, or template changes. The nav already links to `/booking/`.

### Part A — Passwordless email-OTP auth (core requirement)

| Setting | Target value | Effect |
|---|---|---|
| `selected_customer_authentication_method` | `otp` | Only one-time-code auth is offered |
| `default_customer_authentication_method` | `otp` | OTP shown by default |
| `selected_customer_authentication_field_type` | `email` | (already set) verify via email, not phone |
| `notifications_email_processor` | `wp_mail` | **Required prerequisite** (discovered during impl): LatePoint email notifications were disabled, which silently blocked OTP send. Routes OTP email through Default WP Mailer → FluentSMTP. |

Result on `/booking/`: logged-out members see "enter email → receive 6-digit code
→ verify & enter." New emails create an account on first successful verification;
returning members log in the same way. Every account has a verified email.

### Part B — Brand-consistent post-login landing

| Setting | Target value | Effect |
|---|---|---|
| `page_url_customer_dashboard` | `/booking/` | Logged-in members land on the GV-styled page |
| `page_url_customer_login` | `/booking/` | "Login required" redirects go to the GV-styled page |

The bare `/customer-cabinet/` page (2980) stays published, just no longer the
redirect target.

## Implementation approach

Per the project golden workflow (edit in `build/`, deploy over SSH):

1. **New script** `build/scripts/enable-member-auth.php` — idempotent; upserts the
   five settings above into `wp_latepoint_settings` (DELETE-then-INSERT per name,
   matching the existing pattern in `setup-latepoint.php`), and echoes the final
   values for verification.
2. **Back up first:** `wp db export ~/backups/pre-member-auth-$(date +%F-%H%M).sql`.
3. **Deploy:** `scp` the script to `~`, run `wp eval-file ~/enable-member-auth.php`,
   then `wp elementor flush-css && wp litespeed-purge all`, then `rm ~/...`.
4. Commit the script and update `PROJECT_LOG.md` (and a line in `PROGRESS_LOG.md`).

## Verification

1. **Settings applied:** script echo shows the five target values.
2. **Email delivery:** trigger a live OTP and confirm an email is sent. Either:
   - `wp eval 'var_dump(OsOTPHelper::generateAndSendOTP("info@gvbasketball.com","email","email"));'`
     and confirm a non-error return + the code arrives at info@; or
   - load `/booking/` logged out, enter an email, confirm the code email arrives.
3. **UI:** `/booking/` logged out shows the email + "send code" flow (no password
   field), then a code-entry box.
4. **Redirect:** after verifying a code, the member lands on `/booking/` (not
   `/customer-cabinet/`).

## Rollback

Re-run the script with `selected_customer_authentication_method` removed/`password`
to revert to password auth; restore the pre-change DB export if needed. Settings
are the only thing touched, so rollback is low-risk.

## Out of scope (noted, not in this change)

- OTP email body is plain text ("Your OTP code is: …"). Could be branded later via
  the `latepoint_notifications_send_otp_code` filter.
- Leftover LatePoint demo support text (`steps_support_text`: "Call (858)
  939-3746"). Separate cleanup.
