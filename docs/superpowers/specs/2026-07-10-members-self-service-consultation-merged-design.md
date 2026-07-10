# GV Members + Self-Service Consultation — Merged Design

**Date:** 2026-07-10

**Status:** Approved in conversation; pending written-spec review

**Target:** `https://gvbasketball.com/members/`

**Supersedes:**

- `docs/superpowers/specs/2026-07-10-gv-members-portal-design.md`
- `docs/superpowers/specs/2026-07-10-latepoint-self-service-consultation-design.md`
- `docs/superpowers/plans/2026-07-10-latepoint-self-service-consultation.md`

## Goal

Create a branded member site at `/members/` and replace the email-only consultation request with a themed LatePoint wizard that immediately creates a valid pending booking. The same LatePoint record acts as the Submitted request and, after Coach Gino assigns the exact time and approves it, the Confirmed session.

## Conflict decisions

| Conflict | Winner |
|---|---|
| Selected date | One venue and one preferred day |
| Initial booking state | `pending`, shown to members as Submitted |
| Coach finalization | Secure link in the coach email; select exact time and approve |
| Public interface | Themed native LatePoint wizard |
| Member promotion | Optional checkbox; all verified emails remain technically able to sign in |
| Fields | Minimal fields plus optional phone/Instagram |
| Member emails | Immediate receipt only; Coach Gino personally sends the final confirmation |

## Production findings

The design is based on the local source, production WordPress and database state, and installed LatePoint 5.6.6 source over SSH.

1. The current `/booking/` dashboard login uses LatePoint's classic auth controller, which rejects unknown emails before sending OTP. A custom unified `/members/` OTP endpoint is required for any-email signup.
2. The current public consultation form only sends email and stores no request.
3. A normal LatePoint wizard creates a customer, order, order item, and order-linked booking. Using the native wizard avoids fabricating incomplete LatePoint rows.
4. LatePoint exposes `latepoint_booking_steps_contact_after` for additional contact-step controls, `latepoint_process_step` for step processing, and `latepoint_booking_created` after a booking is saved.
5. LatePoint provides `wp_latepoint_booking_meta` through `OsBookingMetaModel`/`OsMetaHelper`, allowing player and workflow data to stay attached to the booking.
6. The current portal response is publicly cacheable for seven days. Member/auth/finalization pages must explicitly bypass LiteSpeed and browser/edge caching.
7. Player Consultation is currently 45 minutes with venue work periods from 15:00 to 18:00. The merged flow keeps the real duration at 45 minutes and changes only the public interval/presentation needed to expose one day-request action.

## Architecture decision

Use a single LatePoint booking state machine.

- **LatePoint customer:** parent/guardian identity and OTP session.
- **LatePoint order + order item:** normal native booking ownership.
- **LatePoint booking:** the sole request/session record.
- **LatePoint booking meta:** player, training interest, optional contact/note, member-promotion preference, and secure finalization state.
- **GV members module:** unified OTP signup, custom portal UI, wizard extensions, coach finalization, email formatting, redirects, and cache protection.

There is no separate GV consultation-request table. This removes synchronization, claiming, duplicate status, and migration failure modes.

## Booking metadata

The integration stores these keys on each Player Consultation booking:

- `gv_player_name`
- `gv_player_age`
- `gv_training_interest`
- `gv_contact_alt`
- `gv_note`
- `gv_member_opt_in` (`yes` or `no`)
- `gv_day_request` (`yes`)
- `gv_finalize_token_hash`
- `gv_finalize_token_expires_at`
- `gv_finalize_token_used_at`
- `gv_finalized_at`

The raw finalization token is sent only to Coach Gino. Only its SHA-256 hash is stored.

## Public booking flow

### Entry

Every `Book a Consultation` CTA opens LatePoint's native booking wizard with Player Consultation preselected. `/book-a-consultation/` remains a landing fallback that embeds the same preselected wizard.

Private Training, Small Group, and Elite Performance remain unavailable as direct public services. They appear only as the visitor's training interest.

### Step 1 — Venue and day

The visitor chooses one venue and one available day.

Player Consultation remains a 45-minute service. Its public `timeblock_interval` becomes 180 minutes so the 15:00–18:00 work period produces one public request slot. The wizard does not present 15:00 as the final appointment time; the selectable action is labeled **Request this day**.

The selected date and venue are real LatePoint booking values. The nominal time remains internal while the booking is pending.

### Step 2 — Minimal details

Required fields:

1. Parent/guardian first and last name
2. Email
3. Player name
4. Player age, integer 3–99
5. Training interest: Private, Small Group, or Elite Performance

Optional fields:

1. Phone number or Instagram handle
2. One short note labeled **Anything we should know?**
3. Checkbox labeled **Send me access to the GV Members site**

The checkbox does not control whether LatePoint creates a customer; native booking always creates or reuses one. It controls whether the receipt promotes `/members/` and explains OTP access.

The contact step includes Cloudflare Turnstile. Server-side verification is mandatory before the booking reaches confirmation.

### Step 3 — Email verification

The visitor verifies the submitted email with LatePoint's six-digit, ten-minute OTP before the order is finalized. The existing branded OTP email remains in use.

An invalid, expired, or used code creates no booking. OTP requests are also rate-limited by normalized email and hashed IP in the GV layer.

### Step 4 — Pending booking and receipt

Successful verification creates a normal order-linked Player Consultation booking with status `pending`.

The parent receives one branded receipt containing:

- Player and training interest
- Requested venue and day
- Status: Request received
- Clear copy that Coach Gino will coordinate the exact time
- Optional `/members/` CTA only when `gv_member_opt_in=yes`

The receipt never shows the nominal internal time and never claims the appointment is confirmed.

## Coach operational email

Coach Gino receives a branded email for every pending consultation request. It includes the submitted details, optional phone/Instagram, requested venue/day, booking reference, and a secure **Finalize Consultation** button.

The email must clearly state these steps:

1. Review the requested venue, day, player, and training interest.
2. Contact the parent using email or the optional phone/Instagram detail.
3. Agree on the exact 45-minute consultation time.
4. Open **Finalize Consultation**.
5. Select an available exact time and click **Confirm Booking**.
6. Personally send the final schedule to the parent.

It explicitly warns that the website updates the member portal but does not send an automatic final confirmation email to the parent.

## Secure coach finalization

The emailed link contains the booking reference and a high-entropy token. Opening it performs no mutation, preventing email-security scanners from approving bookings.

The finalization screen:

1. Verifies the token hash, 30-day expiry, unused state, and that the booking is a pending Player Consultation.
2. Shows the requested day, venue, player, parent, and contact details.
3. Generates available 45-minute start times from 15:00 through 17:15 in 15-minute increments.
4. Excludes times that fail LatePoint's normal agent/location/service availability check.
5. Requires an explicit POST with nonce and token.

On confirmation, the server rechecks availability, sets the selected start time, recalculates end date/time and UTC fields, updates status to `approved`, records the token-used/finalized timestamps, and fires LatePoint's normal booking-updated hooks once.

It does not send the parent another email. The success screen prominently says:

> Booking updated. Please contact the parent now to confirm the final schedule.

Repeated, expired, mismatched, or prefetched requests cannot update the booking or send duplicate notifications.

## Members site

WordPress page 2983 becomes **Members** at `/members/`. `/booking/` and `/customer-cabinet/` permanently redirect there. Header, footer, and LatePoint customer URLs point to `/members/`.

### Authentication

The logged-out page has one unified email → OTP flow.

- Existing email: verify OTP and authorize the existing LatePoint customer.
- Unknown email: verify OTP, create one active email-only LatePoint customer, and authorize it.
- No password or separate Sign In/Create Account tabs.

The optional booking checkbox affects promotion only. Anyone who later enters and verifies the booking email at `/members/` can access its records.

### Information architecture

The approved Request Timeline direction contains:

- **Requests**
- **Confirmed Sessions**
- **Profile**
- **New Request**
- **Help / Email GV Basketball**

### Requests

Requests shows all Player Consultation bookings owned by the customer, newest first.

- LatePoint `pending` → **Submitted**
- LatePoint `approved` → **Confirmed**

Submitted items show requested day and venue but suppress the nominal time. Confirmed items show exact date, time, venue, coach, player, training interest, optional note, and booking reference.

Each item includes **Need to make a change? Email GV Basketball**, opening a pre-addressed email with the booking reference.

### Confirmed Sessions

This view shows approved upcoming and past LatePoint consultations/training bookings with date, time, service, venue, coach, status, and add-to-calendar actions.

It excludes orders, payments, New Appointment, cancellation, and rescheduling controls.

### Multiple players and new requests

Player identities are derived from distinct `gv_player_name`/`gv_player_age` booking metadata owned by the customer. No separate player table is added.

When a signed-in member starts another request:

- Parent name and verified email are prefilled and locked.
- Prior players are offered as reusable choices.
- Selecting New player exposes player name and age.
- The themed LatePoint wizard remains the booking engine.

### Profile

Profile includes parent/guardian first name, last name, phone, read-only verified email, and logout. Email ownership changes remain staff-assisted.

## Visual direction

The member UI is a premium training journal: white working canvas, deep-navy framing, restrained orange for progression/actions, a shallow real-photography title strip, and a single readable timeline column.

The LatePoint wizard is heavily scoped under the GV wrapper and uses the same navy/orange typography, visible progress, real labels, 44px targets, 16px mobile inputs, accessible focus rings, and branded error/loading/success states.

## Cache, security, and privacy

- `/members/`, OTP endpoints, and coach finalization emit WordPress no-cache headers.
- Define `DONOTCACHEPAGE` and call `litespeed_control_set_nocache` on member/finalization requests.
- Keep Rocket Loader off.
- Sanitize and validate all custom fields server-side.
- Preserve nonce, Turnstile, and honeypot protections where applicable.
- Require OTP before creating the pending booking.
- Use generic auth responses to avoid email enumeration.
- Rate-limit OTP by email and IP.
- Hash finalization tokens; never store plaintext.
- Require explicit POST for booking mutation.
- Recheck booking ownership, service, status, token, and availability immediately before approval.
- Never place member PII in public URLs, browser storage, or another customer's rendered data.
- Never edit LatePoint plugin files.

## Email behavior

### Parent

Exactly one automatic email: the initial request receipt. If the member checkbox is selected, that receipt includes the `/members/` link. There is no automatic final-confirmation email.

### Coach Gino

One operational request email containing complete details, the numbered manual workflow, and the secure finalization button. The finalization success screen repeats the instruction to contact the parent personally.

Native LatePoint notifications that would expose the nominal time or imply immediate approval are suppressed or replaced for this consultation flow.

## Error handling

- **Invalid OTP/Turnstile:** create no order or booking.
- **Duplicate submission:** reuse LatePoint/customer safeguards and disable the submit control while processing.
- **Booking/order failure:** show a retryable error and send no success receipt.
- **Receipt failure after booking creation:** retain the pending booking, log delivery failure, and show an on-screen receipt state.
- **No available final time:** leave the booking pending and ask Coach Gino to coordinate another day.
- **Availability race:** recheck immediately before update; reject the stale selection.
- **Invalid/expired/used token:** reveal no private details and make no change.
- **Repeated confirmation:** return the existing approved state without re-firing email/workflow side effects.
- **Deleted booking:** exclude it from member sessions; finalization shows unavailable.
- **Unknown portal email:** OTP verification creates exactly one email-only customer.

## Testing and acceptance

### Automated

- Public interval produces one request action per operating day while consultation duration remains 45 minutes.
- Custom fields validate and persist to booking meta.
- Age outside 3–99 is rejected.
- Missing/invalid Turnstile or OTP creates no booking.
- Successful wizard creates one pending order-linked booking and customer.
- Receipt hides nominal time and conditionally includes `/members/`.
- Coach email contains all six operational steps.
- Finalization GET does not mutate.
- Finalization POST rejects wrong service/status/token/booking/time.
- Availability race leaves booking pending.
- Valid finalization updates exact time and changes status once.
- No final parent email is sent.
- Unknown-email member OTP creates one customer; repeat login creates none.
- Cross-customer booking access is rejected.
- Pending timeline hides time; approved timeline shows it.
- Multiple players remain isolated to the owning customer.
- Old URLs redirect and member/finalization responses are non-cacheable.

### Production acceptance

1. All site CTAs open the themed native consultation wizard.
2. Venue/day selection shows one **Request this day** action and no final-time promise.
3. Minimal details, optional phone/Instagram, Turnstile, and OTP complete successfully.
4. Exactly one pending order-linked booking exists with all booking metadata.
5. Parent receives one accurate receipt; checkbox controls the Members CTA.
6. Coach receives the numbered workflow and secure finalization link.
7. Coach selects an available exact time and approves the booking.
8. No second parent email is generated; the coach screen tells Coach Gino to contact the parent.
9. `/members/` shows Submitted before approval and Confirmed afterward.
10. Confirmed Sessions shows the exact time; no reschedule/payment UI appears.
11. Desktop/mobile keyboard, labels, focus, error announcements, and touch targets pass accessibility review.

## Deployment and rollback

Deployment uses targeted table/page/template backups, local `build/` sources, idempotent LatePoint configuration, MU-plugin/CSS deployment, page 2983 migration, cache flushes, and a controlled end-to-end test booking.

Rollback restores page 2983, header/footer links, LatePoint URL/settings/service interval, and the prior public modal/plugin versions. Existing pending/approved LatePoint records remain intact; rollback never deletes customer bookings.

## Explicit non-goals

- Separate GV request table
- Multiple preferred days per request
- Immediate approval using the nominal time
- Automatic final-confirmation email to the parent
- Customer self-edit, cancellation, or rescheduling
- Public booking of Private/Small Group/Elite services
- Online payments or bank details
- Historical mailbox backfill
- Persistent player-profile table
- WordPress user accounts
- WordPress request-management dashboard
