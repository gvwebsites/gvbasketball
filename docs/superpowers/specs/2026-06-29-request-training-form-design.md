# Design — "Request Training" form (replaces LatePoint booking form)

**Date:** 2026-06-29
**Page:** `/book-a-consultation/` (post **2982**)
**Status:** Approved, ready for implementation plan

---

## Goal

Replace the LatePoint calendar booking form on the public booking page with a simple,
branded request form. No date/time picker. The visitor tells us who they are, what kind
of training they want, and when they'd like to meet — GV follows up manually.

LatePoint stays installed: the member portal + passwordless OTP login (page 2983) still
use it. **Only the public booking form on page 2982 is replaced.**

## Form fields

A `[gv_request_form]` shortcode renders, in the GV design system:

| Field | Type | Required | Notes |
|---|---|---|---|
| Your Name (parent/guardian) | text | yes | `parent_name` |
| Player Name | text | yes | `player_name` |
| Player Age | number | yes | range 4–25 |
| Email | email | yes | reply channel + auto-reply target |
| Phone or Instagram handle | text | no | `contact_alt` |
| Training Type | select | yes | options: `Private Training`, `Small Group`, `Elite Performance` |
| Preferred Days & Times | textarea | yes | free text, e.g. "Mon/Wed after 4pm" |

Submit button label: **"Send Request."** No "Player Consultation" option (dropped per request).

## Architecture

New must-use plugin **`build/mu-plugins/gv-request-form.php`**, modeled on the existing
`gv-otp-email.php` (table-based + inline-CSS branded email; tight scoping):

1. **Shortcode `[gv_request_form]`** — outputs:
   - The branded form (gv design-system markup), `action="<admin-post.php>"`, `method="post"`.
   - Hidden `action=gv_request_form` + `wp_nonce_field`.
   - A honeypot field (hidden text input bots fill, humans don't).
   - The Cloudflare Turnstile widget (`<div class="cf-turnstile" data-sitekey="…">` + the
     `challenges.cloudflare.com/turnstile/v0/api.js` script). Sitekey injected from the
     `GV_TURNSTILE_SITEKEY` constant so it is never hardcoded in page content.
   - A success/error banner driven by a `?gv_request=ok|err|spam` query param (Post-Redirect-Get).
2. **Handler** on `admin_post_nopriv_gv_request_form` + `admin_post_gv_request_form`:
   - Verify nonce; bail if honeypot is non-empty (silent success to fool bots).
   - **Verify Turnstile server-side** (`POST https://challenges.cloudflare.com/turnstile/v0/siteverify`
     with `GV_TURNSTILE_SECRET` + the response token + remote IP). On failure → redirect `?gv_request=spam`.
   - Sanitize + validate all fields (required checks, `is_email`, age is int 4–25).
   - Send two branded HTML emails via `wp_mail`.
   - Redirect (PRG) back to the booking page with the result param.

### Submission model
Standard HTML form POST to `admin-post.php` with Post-Redirect-Get (no AJAX/SPA). Simpler
and more reliable; the banner state comes back via the query param.

## Emails (both branded, reuse the OTP email visual style)

Shared helper builds the GV-branded shell (logo header, orange top rule, navy headings,
IG footer), as in `gv-otp-email.php`.

1. **Admin notification → `info@gvbasketball.com`**
   - Subject: `New training request — {player_name}`
   - `Reply-To: {email}` (so GV replies straight to the parent)
   - Body: styled table of every submitted field.
2. **Auto-reply → submitter's email**
   - Subject: `We got your request — GV Basketball`
   - Warm confirmation, a short "what happens next," and the IG DM link
     (`https://ig.me/m/gvbasketballl`).

Both use `Content-Type: text/html`. FluentSMTP + Gmail OAuth already route `info@`.

## Spam protection — Cloudflare Turnstile

- Provision a **managed** Turnstile widget for `gvbasketball.com` via the Cloudflare API
  using `CLOUDFLARE_ACCOUNT_ID` + `CLOUDFLARE_API_TOKEN` from `.env`
  (`POST /accounts/{id}/challenges/widgets`). Capture `sitekey` + `secret`.
  (Verified 2026-06-29: token has Turnstile scope; 0 widgets currently exist.)
- Store keys as **wp-config constants** `GV_TURNSTILE_SITEKEY` / `GV_TURNSTILE_SECRET`
  (same approach as the Google OAuth keys), set with `wp config set --quiet`. Mirror into
  `.env` locally (gitignored). Never print secret values.
- Defense in depth: nonce + honeypot + server-side Turnstile verification.

## Reframe — keep slug, change labels

Keep the URL `/book-a-consultation/` (no broken inbound links / redirects). Change visible
copy to **"Request Training" / "Get Started"**:
- Page 2982 hero + the old "Pick a Date & Time" subhead and consultation-flavored copy.
- Header CTA (`build/templates/header.html`), footer (`footer.html`).
- "Book a Consultation" anchor text on marketing pages (home/about/programs/etc.).
- Grep every occurrence of "Book a Consultation" / "consultation" CTA text so none is missed.

## Files

- **New:** `build/mu-plugins/gv-request-form.php`
- **New:** `build/scripts/setup-turnstile.php` (provision widget + set wp-config constants)
- **Edit:** `build/scripts/build-functional.php` — page 2982 block `[latepoint_book_form]`
  → `[gv_request_form]`, reword consultation copy.
- **Edit:** `build/templates/header.html`, `build/templates/footer.html`, and marketing
  pages carrying "Book a Consultation" CTA labels.
- Deploy: `scp` mu-plugin + run scripts over SSH, then `wp elementor flush-css && wp litespeed-purge all`.

## Verification

1. Deploy mu-plugin + rebuild page 2982 + set Turnstile constants.
2. Load `/book-a-consultation/`: form renders in brand style, Turnstile widget shows.
3. Submit a valid test request → confirm the admin email lands at `info@` and the
   auto-reply lands at the test address; both render branded.
4. POST without a Turnstile token (e.g. curl) → confirm rejection (`?gv_request=spam`,
   no email sent).
5. Confirm member portal/OTP login (2983) still works (LatePoint untouched).
6. Update `PROJECT_LOG.md` + `PROGRESS_LOG.md`; commit (keep `.env` out of git).

## Decisions locked

- Build approach: **custom** branded form + email (not WPForms).
- Reply channel: **Email required**, phone/Instagram optional.
- Spam: **Cloudflare Turnstile** (provisioned via API).
- **Auto-reply to submitter** in addition to the `info@` notification.
- Name: **separate** parent name + player name.
- Slug **unchanged**; labels reframed to "Request Training."
- Submission: **standard POST + Post-Redirect-Get** (no AJAX).
