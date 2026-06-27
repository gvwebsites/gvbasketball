# Handoff — Member Profile Workflow (login + consult UX)

Goal of the next work block: **polish the member account experience** — how a client logs in,
books/manages a consultation, and views their profile/upcoming sessions. This doc is the starting
context so a fresh session (or another dev/agent) can pick it up cold.

## How it works today

The whole member/booking layer is **LatePoint** (free plugin, payments OFF). Two pages drive it:

| Page | URL | ID | Shortcode | Purpose |
|---|---|---|---|---|
| Book a Consultation | `/book-a-consultation/` | 2982 | `[latepoint_book_form]` | Multi-step booking wizard. Creates a LatePoint customer. |
| Member Booking (portal) | `/booking/` | 2983 | `[latepoint_customer_dashboard]` | Logged-out → login form; logged-in → dashboard (upcoming bookings, reschedule). |

Entry points to login: nav **person icon → `/booking/`**, footer **Member Login → `/booking/`** and
**Member Booking → `/booking/`**.

LatePoint config (see `build/scripts/setup-latepoint.php`):
- Agent: **Coach Gino**. Locations: **Makati, Ortigas**. Services: **Consultation, Private, Small
  Group, Elite**. Work periods: **Mon/Tue/Fri/Sun, 3–6 PM** per location. **Payments OFF.**
- Styling wrappers: `.gv-bookform-wrap` (booking) and `.gv-dash-wrap` (dashboard) in `gv-brand.css`
  — light-grey bands; LatePoint renders its own UI inside, only lightly themed so far.

## What likely needs polish (verify + decide)

1. **Account/login model.** Confirm how LatePoint customer accounts actually work here — password vs.
   magic-link/email login, and whether a customer who books a consult ever receives a way back in.
   This is the single biggest unknown; everything else depends on it.
2. **Post-booking → account.** After someone books a consult, is there a clear "you're in, here's your
   dashboard" moment? Today the booking confirmation and the portal feel like two separate things.
3. **Dashboard styling.** `[latepoint_customer_dashboard]` ships generic UI. Theme it to the GV brand
   (navy/orange, Bebas/Montserrat) like the rest of the site — buttons, tabs, cards.
4. **Login page UX.** The logged-out state of `/booking/` is just LatePoint's default login. Consider a
   branded intro/heading, "forgot password," and a clear "new here? Book a consultation" path.
5. **Profile fields.** Decide what a member profile should show/edit (contact info, athlete details,
   waiver status). Waiver form (page 3009) is currently separate from the account.
6. **Reschedule/cancel policy.** Confirm LatePoint cancellation window + that reschedule works from the
   dashboard.

## Deploy notes (same as rest of site)
- Edit `build/` → `scp` → apply via `gv_*` helpers / re-run the relevant `build/scripts/*.php` →
  `wp elementor flush-css && wp litespeed-purge all`. Full runbook in `CLAUDE.md`.
- LatePoint structural changes go through `build/scripts/setup-latepoint.php`. Dashboard/booking
  styling goes in `gv-brand.css` under the `.gv-dash-wrap` / `.gv-bookform-wrap` blocks.
- Always back up first (`wp db export` is flaky under load — use per-target backups, see CLAUDE.md §1).

## Open questions for the client
- Should members log in with a **password**, or is **email/magic-link** fine (lower friction)?
- After a consult is booked, what should the member be able to **do in their dashboard** (just see
  upcoming sessions? reschedule? message? see their plan)?
- Should the **waiver** be part of the member profile, or stay a standalone form?
