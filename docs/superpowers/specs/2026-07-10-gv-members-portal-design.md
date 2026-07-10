# GV Members Portal and Consultation History — Design

**Date:** 2026-07-10

**Status:** Superseded by `2026-07-10-members-self-service-consultation-merged-design.md`

**Target:** `https://gvbasketball.com/members/`

## Goal

Replace the minimal `/booking/` page with a branded member site at `/members/` where any valid email can create or access an account through a one-time email code, parents can see all consultation requests submitted under their verified email, and confirmed LatePoint sessions remain visible in a read-only experience.

## Production findings

The design is based on direct inspection of the repository, the live page, WordPress over SSH, the production database, and LatePoint 5.6.6 source.

1. The live customer-login form is configured for email OTP, but LatePoint is using its classic authentication flow. `OsAuthController::request_otp()` rejects every unknown email with “We don't recognize this email.” The production database currently contains zero LatePoint customers and zero bookings, so no email can create an account through `/booking/` today.
2. The current request form validates fields and sends two emails but stores no request record. Consultation history cannot be reconstructed from WordPress or LatePoint.
3. The live `/booking/` response is publicly cacheable for seven days and was served as a LiteSpeed cache hit. A member page containing OTP nonces and account-specific data must never use public page caching.
4. `/members/` does not exist. Page 2983 is currently published as `/booking/`; the header, footer, and LatePoint redirect settings all point there.
5. The deployed `gv-request-form.php`, `gv-otp-email.php`, and `gv-brand.css` match their local `build/` sources byte-for-byte.

## Architecture decision

Use a hybrid GV member layer with LatePoint identity and sessions.

- A new GV members module owns OTP signup orchestration, consultation-request persistence, request claiming, the custom member interface, and email-based staff confirmation.
- LatePoint continues to own customer sessions and confirmed appointment records.
- LatePoint core/plugin files are never edited.
- WordPress user accounts are not created. Introducing WordPress users would duplicate identity, session, and authorization state without improving the member experience.
- Unscheduled consultation requests are not represented as LatePoint bookings. Fabricating dates or incomplete order records would pollute the confirmed-session system.

## Module boundaries

The new code is split by responsibility behind a top-level MU-plugin bootstrap:

- **Bootstrap/schema:** loads the module and ensures the private request table is at the expected schema version.
- **Request repository:** creates, claims, queries, confirms, and links requests. It is the only component that writes request rows.
- **Member authentication:** requests and verifies LatePoint OTPs, creates an email-only LatePoint customer after successful verification when needed, authorizes the LatePoint customer session, and applies rate limits.
- **Member portal:** renders logged-out authentication, the request timeline, confirmed sessions, profile, new-request entry, and help actions.
- **Staff confirmation:** validates the private email token, presents eligible LatePoint bookings, performs the explicit POST confirmation, and sends the confirmation email.
- **Existing request form integration:** validates the minimal form, calls the repository before sending mail, and renders account-aware defaults.

Each component exposes a small interface and does not reach directly into another component's storage.

## Identity and OTP flow

The logged-out `/members/` screen uses one unified flow with no Sign In/Create Account split:

1. The visitor submits any syntactically valid email.
2. The server applies a short resend cooldown plus rolling email/IP rate limits.
3. LatePoint generates and sends its existing six-digit, ten-minute, single-use OTP. The current branded `gv-otp-email.php` filter continues styling this email.
4. The visitor submits the code.
5. The server verifies it through LatePoint's OTP helper.
6. If a LatePoint customer exists for the normalized email, that customer is authorized. If not, an active email-only LatePoint customer is created using LatePoint's alternative email validation and then authorized.
7. Any unclaimed request rows with the same normalized email are attached to the customer idempotently.
8. The visitor is redirected to the request timeline.

Responses do not reveal whether the email already had an account. Invalid, used, or expired codes never create a customer. OTP generation and verification remain server-side.

## Request form — minimal fields

The form must collect only information needed to identify the player and arrange a consultation.

### Anonymous form

Visible required controls:

1. Parent/guardian name
2. Email
3. Player name and player age, displayed in one compact row
4. Training program
5. Preferred location
6. Preferred day(s), filtered by location

One optional control remains: **Anything we should know?** This replaces separate contact and preferred-time fields with a single short note.

The Phone/Instagram field is removed. Email is the canonical contact channel. Player age accepts integers from 3 through 99, matching the site's stated ages-3-through-professional audience instead of the current 4–25 cap.

### Signed-in form

The verified email and completed parent identity are supplied by the account and are not editable request fields. If an email-only account has no parent name yet, the form shows one compact first-name/last-name row and saves it to the LatePoint customer profile with the request. The remaining visible controls are:

1. Existing player or New player
2. Player name and age only when New player is selected
3. Training program
4. Preferred location
5. Preferred day(s)
6. Optional note

Existing-player choices are derived from distinct players in that member's request history. A separate player-profile table is intentionally deferred.

## Request persistence and ownership

A private table, `{prefix}gv_consultation_requests`, stores:

- Internal numeric id
- Non-sequential public reference
- Normalized email
- Nullable LatePoint customer id
- Parent name
- Player name and age
- Training program
- Location key
- Preferred days as encoded structured data
- Optional note
- Status: `submitted` or `confirmed`
- Created and confirmed timestamps
- Nullable linked LatePoint booking id
- Hash of the single-use staff confirmation token
- Confirmation-token expiry and used timestamps

There are no public post URLs. Repository queries always scope member reads by the authorized LatePoint customer id; normalized email is used only to claim previously anonymous rows after OTP verification.

The public handler stores the validated request before sending either email. If storage fails, it sends no success email and asks the visitor to retry. If email delivery fails after storage, the request remains saved and the failure is logged without duplicating the request.

## Member information architecture

The approved visual direction is **Request Timeline**.

### Visual thesis

A premium training journal: white working canvas, deep navy framing, one restrained orange progress/action color, a shallow real-photography title strip, and a single chronological column that remains clear on mobile.

### Logged-in navigation

- Requests
- Confirmed Sessions
- Profile
- New Request
- Help / Email GV Basketball

### Request timeline

Requests are sorted newest first. Each request shows its reference, submission date, player, age, program, location, preferred days, optional note, and current status.

The status model has exactly two states:

- **Submitted:** one timeline event records receipt.
- **Confirmed:** a second timeline event shows the linked LatePoint date, time, venue, service, and coach.

There is no In Review, Closed, Declined, staff-note, editing, or withdrawal state in this release.

Each request includes a **Need to make a change? Email GV Basketball** action. It opens a pre-addressed message to `gvbasketballcoaching@gmail.com` with the request reference in the subject. Members cannot edit or withdraw a stored request directly.

### Confirmed Sessions

This view reads the authorized customer's LatePoint bookings and presents a custom GV interface for:

- Upcoming sessions
- Past sessions
- Date, time, service, venue, coach, and status
- Add-to-calendar links

It does not expose LatePoint Orders, payments, New Appointment, cancellation, or rescheduling UI.

### Profile

Profile contains parent/guardian first name, last name, phone, verified email, and logout. The verified email is read-only. Account-email changes remain staff-assisted.

## Coach workflow through email

The existing coach notification remains the operational inbox and keeps its Add to Google Calendar action. It gains **Mark Confirmed**.

The emailed link contains a high-entropy opaque token; only its hash is stored. The token expires after 30 days and becomes used after the first successful confirmation. Opening the link never changes state because email-security scanners may prefetch links.

The link opens a branded confirmation screen that:

1. Shows the request summary.
2. Finds upcoming LatePoint bookings belonging to a customer with the same normalized email.
3. Preselects the booking when exactly one candidate exists.
4. Lets the coach select the correct booking when several exist.
5. Explains that a LatePoint booking must be created first when none exists.
6. Requires an explicit POST confirmation protected by the token and a nonce.

On confirmation, the repository verifies that the selected booking belongs to the same email, links it, changes the request to `confirmed`, marks the token used, and sends the member a branded confirmation email containing the appointment details and `/members/` link. A repeated valid submission returns the already-confirmed state without changing data or sending another email; an expired or unrelated token reveals no request details.

No WordPress request-management screen is added.

## URL and cache migration

- Keep WordPress page id 2983 but rename it to **Members** with slug `/members/`.
- Replace the generic LatePoint dashboard block with the GV members shortcode.
- Permanently redirect `/booking/` and `/customer-cabinet/` to `/members/`.
- Update the header account link, footer member link, LatePoint dashboard/login URL settings, page documentation, and build scripts.
- Mark `/members/` as non-cacheable with `DONOTCACHEPAGE`, WordPress no-cache headers, and the appropriate LiteSpeed exclusion. Logged-in member responses must never be stored by page or edge caches.

## Historical data

Tracking begins with the production launch. Existing mailbox-only requests are not imported. The portal empty state clearly explains that requests submitted after the member-site launch will appear automatically.

## Error handling

- **OTP email failure:** keep the visitor logged out and show a retryable message.
- **Invalid/expired/used OTP:** do not create or authorize a customer.
- **Rate limit:** show a cooldown message without revealing account existence.
- **Duplicate signup race:** re-query by normalized email immediately before creation; authorize the existing row if another request created it first.
- **Request storage failure:** send no notifications and show a safe retry state.
- **Notification failure after storage:** retain one stored request, log the delivery failure, and show that the request was saved.
- **Invalid, prefetched, or reused staff link:** make no state change and show an expired/already-confirmed state.
- **No matching booking:** keep the request Submitted and tell the coach to create the LatePoint booking first.
- **Linked booking later deleted:** keep the request Confirmed, show that session details are unavailable, and display the email-for-changes action.
- **Claiming repeated:** leave already-owned requests unchanged and create no duplicates.

## Security and privacy

- Sanitize and validate every form value server-side.
- Preserve the existing WordPress nonce, honeypot, and Cloudflare Turnstile controls on consultation submission.
- Apply OTP cooldowns and rolling limits by normalized email and hashed IP key.
- Use generic auth responses to prevent account enumeration.
- Store staff tokens hashed, never in plaintext.
- Require POST for all state changes.
- Verify request ownership and booking ownership on every member/staff operation.
- Never place member PII in URLs, browser storage, or client-side data for other accounts.
- Keep LatePoint core files untouched.

## Testing and acceptance criteria

### Automated checks

- Unknown email can request OTP, verify, create exactly one LatePoint customer, and remain logged in.
- Existing customer can verify OTP and sign in without duplication.
- Invalid, expired, used, and rate-limited OTP paths fail safely.
- Anonymous minimal request is stored before email delivery.
- Signed-in request ignores tampered identity/email fields.
- Post-signup claim attaches all and only same-email unclaimed requests.
- Multiple players appear as distinct reusable choices.
- Cross-account request and booking access is rejected.
- Staff GET does not mutate; valid POST confirmation links the correct booking once.
- Confirmation sends the member email and invalid/reused tokens do nothing.
- Deleted booking fallback renders safely.
- `/booking/` and `/customer-cabinet/` redirect to `/members/`.
- `/members/` emits no-cache behavior.

### UI and production acceptance

- Logged-out email and OTP states work on desktop and mobile.
- Request timeline supports empty, Submitted, and Confirmed states.
- Sessions supports upcoming and past records without orders/payment controls.
- Profile keeps verified email read-only.
- Keyboard navigation, focus states, labels, error announcements, and touch targets are accessible.
- A controlled production test email completes signup, sees a new request, links a LatePoint booking through the coach email, receives confirmation, and sees the confirmed details in both Requests and Sessions.

## Deployment and rollback

Deployment follows the project golden workflow: targeted backups, deploy local `build/` sources, install/upgrade the request schema, rename page 2983, update settings and templates, flush Elementor/LiteSpeed caches, and run the production acceptance flow.

Rollback restores page 2983's prior slug/content and LatePoint URLs, restores header/footer links, disables the members bootstrap, and leaves the private request table intact so no submitted data is lost.

## Explicit non-goals

- Request editing, withdrawal, decline, or closed states
- Staff notes or member messaging threads
- Historical Gmail backfill
- WordPress user accounts
- Paid LatePoint rescheduling/cancellation
- Payments or orders
- Separate persistent player profiles
- A WordPress request-management dashboard
