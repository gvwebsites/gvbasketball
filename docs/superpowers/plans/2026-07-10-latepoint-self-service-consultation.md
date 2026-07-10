# Self-Service Consultation Booking via LatePoint — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> **Spec:** `docs/superpowers/specs/2026-07-10-latepoint-self-service-consultation-design.md` (read it first).

**Goal:** Make the public "Book a Consultation" button create a real, brand-themed LatePoint consultation booking (day-only pick, details after) with an opt-in member account and an OTP + Turnstile gate — so bookings auto-appear in the member portal.

**Architecture:** Reuse LatePoint's own booking wizard (native "slot → details" flow), heavily CSS-themed to the GV brand and opened in the existing pop-up modal. Consultation is configured as one bookable slot per working day (coach sets the exact time later). Anti-spam = native email-OTP + Cloudflare Turnstile. A LatePoint notification hook sends the branded coach email with the existing "Add to Google Calendar" button. The custom email modal (`gv-request-form.php`) is retired as the public booking path; its email/gcal helper functions are preserved and reused.

**Tech Stack:** WordPress 6.8 + LatePoint 5.6.6 (FREE) on Hostinger (PHP 8.2), WP-CLI over SSH (`ssh gvweb`), Elementor Theme Builder, LiteSpeed + Cloudflare, mu-plugins, framework-free PHP CLI tests, Cloudflare Turnstile.

---

## Global Constraints

Copied from the spec and `wiki/` — **every task implicitly includes these**:

- **SSH:** `ssh gvweb`; WP root `/home/u907133977/domains/gvbasketball.com/public_html` — run all `wp` there. Filter noise: `2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"`.
- **Backups:** `wp db export` **fails on this host**. Back up table-scoped (`wp db query "SELECT ..." --skip-column-names > backup.tsv`) and per-page (`wp post meta get <id> _elementor_data`) into `~/backups/` **before** any DB/page change.
- **After any change:** `wp elementor flush-css && wp litespeed-purge all` (+ Cloudflare purge for changed static assets). Keep Rocket Loader OFF.
- **No unit-test framework.** The verification cycle is **edit `build/` → `scp` → apply (`wp eval-file` / `gv_*` helper) → flush caches → verify live (curl/browser)**. Helper-function logic is covered by the framework-free harness `build/mu-plugins/tests/test-gv-request-form.php` (run `php <file>`).
- **Payments OFF. Pricing never shown publicly. No bank details on the site.** Only the **Player Consultation** service is publicly bookable.
- **Member portal stays view-only** (customer reschedule/cancel is a paid LatePoint add-on, not enabled — see `wiki/booking-latepoint.md`).
- **Brand tokens** (`wiki/design-system.md`): navy `#123B78`, deep navy `#021F51`, orange `#F47B20`, gold `#C9A24B`; fonts Bebas Neue (display), Montserrat (UI), Inter (body). On-site contact = Instagram `https://ig.me/m/gvbasketballl` + `gvbasketballcoaching@gmail.com`. Email = identity (passwordless OTP; no passwords).
- **Key page IDs:** Book-a-Consultation **2982** (`/book-a-consultation/`), Member portal **2983** (`/booking/`), GV Header theme part **3002**, GV Footer **2991**. LatePoint agent = Coach Gino; venues Dasma (Mon/Wed/Thu), Urdaneta (Fri/Sun), Corinthian (Sun); work periods 15:00–18:00.
- **Deploy snippets** (DEPLOY-CSS, DEPLOY-PAGE, DEPLOY-FOOTER) and `gv_*` helpers: see `wiki/deployment-workflows.md`. Commit messages end with `Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>`. Never commit `.env`.

---

## Handoff Context (read before Task 0)

You are picking this up cold. Orient yourself:

- **Source of truth is `build/`**, deployed over SSH. Nothing is edited on the server directly except via `scp` + `wp eval-file`/`gv_*` helpers.
- **What already exists and works** (do NOT rebuild):
  - LatePoint configured in `build/scripts/setup-latepoint.php` (agent, 3 venues + day-pattern work periods, services incl. **Player Consultation** 45 min, payments off, PHP currency).
  - **Passwordless email-OTP auth ON** (`build/scripts/enable-member-auth.php`): `selected_customer_authentication_method=otp`, field=email, `page_url_customer_dashboard/_login=/booking/`, `notifications_email_processor=wp_mail`.
  - **Branded OTP email**: `build/mu-plugins/gv-otp-email.php` (intercepts OTP mail at `wp_mail`).
  - **Member portal**: page 2983 `/booking/` = `[latepoint_customer_dashboard]`, brand-wrapped, **view-only** (built 2026-07-10).
- **What you are changing:** the **public** booking path. Today `build/mu-plugins/gv-request-form.php` renders a global `wp_footer` modal + `[gv_request_form]` and only emails the coach; page 2982 302-redirects to `/training-programs/?gv_open_modal=1`. You will replace this with the LatePoint wizard.
- **Reusable helpers (keep these):** in `gv-request-form.php` — `gv_rf_gcal_url($args)`, `gv_rf_next_weekday_date($days, $today=null)`, `gv_rf_email_shell($heading,$intro,$inner)`, `gv_rf_verify_turnstile($token,$ip)`, `gv_rf_locations()`. They are unit-tested in `build/mu-plugins/tests/test-gv-request-form.php` (35 assertions).
- **Turnstile** keys are in `.env` as `GV_TURNSTILE_SITEKEY` / `GV_TURNSTILE_SECRET` and defined in `wp-config.php` as `GV_TURNSTILE_*` constants.
- **Timezone gotcha:** WP on this host is left at UTC; the business is Asia/Manila. `gv_rf_next_weekday_date()` already pins Manila — keep any date logic Manila-based.
- **Full runbook / access / options:** `wiki/deployment-workflows.md`, `wiki/access-and-hosting.md`, `wiki/booking-latepoint.md`, `wiki/pages.md`, `wiki/forms-and-emails.md`, `wiki/design-system.md`.

---

## File Structure

| File | Responsibility |
|---|---|
| `build/scripts/setup-latepoint.php` | LatePoint config — add one-slot-per-day Consultation + booking status (extend, don't rewrite). |
| `build/scripts/configure-consult-booking.php` (new) | Idempotent script: LatePoint settings for the public wizard (service preset scope, custom fields registration if done via settings). |
| `build/templates/header.html`, `build/templates/footer.html` | Point "Book a Consultation" triggers at the LatePoint booking popup. |
| `build/scripts/build-functional.php` | Page 2982: host the LatePoint booking form / trigger; stop pointing at the retired modal. |
| `build/mu-plugins/gv-consult-notify.php` (new) | Houses the retained helpers + the LatePoint `booking-created` hook (branded coach email + gcal) + Turnstile server-side verification hook. |
| `build/mu-plugins/gv-request-form.php` | Stop rendering the public modal; keep or re-export helpers (moved to `gv-consult-notify.php`). |
| `build/mu-plugins/gv-assets/gv-brand.css` | Theme the LatePoint wizard (`.latepoint-*`) inside `.gv-bookform-wrap`. |
| `build/mu-plugins/tests/test-gv-consult-notify.php` (new) | Framework-free tests for moved helpers + any new pure logic. |
| `wiki/*.md` | Sync `booking-latepoint`, `forms-and-emails`, `pages`, `client-status`, `log`. |

---

## Task 0: Stage-0 discovery — verify LatePoint internals (GATES EVERYTHING)

No code changes. Produce a findings note that later tasks reference. **The exact hook names, view files, and CSS selectors discovered here fill the "from Task 0" references below.**

**Files:** none (read-only server inspection). Save findings to `docs/superpowers/plans/2026-07-10-latepoint-stage0-findings.md`.

- [ ] **Step 1: Confirm environment.** Run:
```bash
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && wp core version && wp plugin get latepoint --field=version && wp plugin list --status=active --field=name | grep -i latepoint && wp post list --post_type=page --field=ID --name=book-a-consultation' 2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: WP 6.8.x, LatePoint 5.6.6, only `latepoint` active, page 2982.

- [ ] **Step 2: R1 — does the BOOKING wizard enforce OTP?** Inspect the booking flow controllers/views:
```bash
ssh gvweb 'cd .../wp-content/plugins/latepoint && grep -rniE "otp|authentication_method|verify" lib/controllers/*booking* lib/controllers/*step* lib/views/booking/ 2>/dev/null | head -40'
```
Record: does completing a booking require the email OTP step when `authentication_method=otp`? If **yes**, OTP gate is native. If **no**, note that Task 5 must add an explicit verify step.

- [ ] **Step 3: R2 — booking-created + booking-reject hooks.** Find the action/filter fired when a booking is saved, and any filter that can **reject/validate** a booking server-side (for Turnstile):
```bash
ssh gvweb 'cd .../wp-content/plugins/latepoint && grep -rniE "do_action|apply_filters" lib/ | grep -iE "booking.*(created|saved|before|validate|added)" | head -40'
```
Record the exact hook name(s) and their arguments (e.g. the booking model). Also find how to inject markup into the wizard's details step (a step-content filter/action or a custom-fields mechanism).

- [ ] **Step 4: R3 — custom fields + day-only display.** Determine how LatePoint 5.6.6 supports **custom booking fields** (admin UI setting vs. filter) and how a single daily slot renders its time:
```bash
ssh gvweb 'cd .../wp-content/plugins/latepoint && grep -rniE "custom_fields|steps_.*fields|timeblock_interval|format_start" lib/ | head -30' 
```
Record: the custom-fields mechanism, and whether the timeslot label can be relabeled/hidden (CSS class in `lib/views/booking/` for the time step).

- [ ] **Step 5: Write findings + go/no-go.** Write `2026-07-10-latepoint-stage0-findings.md` with: OTP-native (Y/N), exact booking hook name(s), reject/validate hook (Y/N + name), custom-fields mechanism, day-only display approach. If R1 or R2 come back "no clean path," note the fallback (Task 5: OTP-only; or a pre-submit AJAX guard). **Commit** the findings file.

---

## Task 1: LatePoint config — one bookable slot per working day for Consultation

**Files:**
- Modify: `build/scripts/setup-latepoint.php` (the `gv_svc('Player Consultation', ...)` call + settings block)
- Deploy: `wp eval-file ~/setup-latepoint.php`

**Interfaces:**
- Produces: a Consultation service that exposes exactly one bookable slot per working day per venue; `default_booking_status` set.

- [ ] **Step 1: Back up LatePoint config tables.**
```bash
ssh gvweb 'cd .../public_html && mkdir -p ~/backups/consult-$(date +%F-%H%M) && for t in latepoint_services latepoint_service_meta latepoint_settings latepoint_work_periods; do wp db query "SELECT * FROM wp_'"$t"'" --skip-column-names > ~/backups/consult-$(date +%F-%H%M)/$t.tsv; done' 2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: 4 non-empty .tsv files.

- [ ] **Step 2: Edit the Consultation service to one slot/day.** In `build/scripts/setup-latepoint.php`, change the consultation service definition so its duration spans the full window and the timeblock interval matches, yielding a single slot per day:
```php
// was: $consult = gv_svc('Player Consultation', 45, 1, 1, '...');
$consult = gv_svc('Player Consultation', 180, 1, 1, 'Discuss goals, current level, and the best-fit program.');
// after $consult is created, force one slot/day:
$sc = new OsServiceModel($consult);
$sc->timeblock_interval = 180;   // one 15:00–18:00 block per day
$sc->save();
```
Add to the `$set` settings array in the same script:
```php
'default_booking_status' => 'approved',   // day-only self-service; coach sets exact time after
```
(If Task 0/client prefers a review step, use `'pending'` instead and document it.)

- [ ] **Step 3: Deploy + apply.**
```bash
scp build/scripts/setup-latepoint.php gvweb:~/setup-latepoint.php
ssh gvweb 'cd .../public_html && wp eval-file ~/setup-latepoint.php && wp elementor flush-css && wp litespeed-purge all && rm ~/setup-latepoint.php' 2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: the script's echo prints service/venue IDs, no PHP errors.

- [ ] **Step 4: Verify one slot/day.** Load the LatePoint booking form for Consultation (temporary `[latepoint_book_form]` on a scratch page or the wizard once Task 2 is done) OR query availability; confirm each working day shows exactly ONE selectable slot per venue and non-working days show none.

- [ ] **Step 5: Commit.**
```bash
git add build/scripts/setup-latepoint.php
git commit -m "feat(latepoint): one-slot-per-day Player Consultation for self-service booking

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: Swap the public "Book a Consultation" trigger to the LatePoint popup

**Files:**
- Modify: `build/templates/header.html` (the `.gv-nav__cta` "Book a Consultation" anchor), `build/templates/footer.html` (`.gv-footer__cta`)
- Modify: `build/scripts/build-functional.php` (page 2982 render)
- Modify: `build/mu-plugins/gv-request-form.php` (stop auto-opening the custom modal)
- Deploy: header/footer via `gv_set_theme_part_blocks` (see `wiki/deployment-workflows.md`), page 2982 via `build-functional.php`

**Interfaces:**
- Consumes: LatePoint booking-popup trigger (confirm exact shortcode/attribute in Task 0 — `[latepoint_book_button ...]` renders a button that opens the wizard popup; it accepts params to preselect `selected_service`).
- Produces: every "Book a Consultation" affordance opens the LatePoint wizard with Player Consultation preselected.

- [ ] **Step 1: Determine the LatePoint popup trigger.** From Task 0 findings, get the exact `[latepoint_book_button]` attributes to (a) open in a popup and (b) preselect the Consultation service (by its service id from Task 1). Draft the trigger markup, e.g.:
```
[latepoint_book_button caption="Book a Consultation" selected_service="<CONSULT_ID>" hide_service_selection="yes"]
```
Confirm attribute names against the installed version (they vary) — record the working form.

- [ ] **Step 2: Point the nav + footer CTAs at it.** Replace the `data-gv-open-modal` custom-modal CTA in `build/templates/header.html` and `build/templates/footer.html` with the LatePoint trigger (rendered server-side — the templates are HTML injected via Elementor, so use the LatePoint button's underlying anchor/attributes, or wrap the shortcode output). If the shortcode can't live in the raw header HTML, use LatePoint's JS trigger attribute on the existing anchor (from Task 0): e.g. `class="latepoint-book-button" data-service-id="<CONSULT_ID>"`.

- [ ] **Step 3: Stop the old modal from opening.** In `build/mu-plugins/gv-request-form.php`, disable the `wp_footer` modal injection and the `[gv_request_form]` public render (comment out the `add_action('wp_footer', 'gv_rf_global_modal', 50)` and the `template_redirect` that force-opens it). Do NOT delete the file yet (helpers still live here until Task 6).

- [ ] **Step 4: Repoint page 2982.** In `build/scripts/build-functional.php`, set page 2982 (`/book-a-consultation/`) to render the inline LatePoint booking form (`[latepoint_book_form]` with Consultation preselected) inside `.gv-bookform-wrap`, as a landing fallback for the popup.

- [ ] **Step 5: Deploy** header/footer theme parts + `build-functional.php` + the mu-plugin (see DEPLOY snippets in `wiki/deployment-workflows.md`); flush caches.

- [ ] **Step 6: Verify.** On the live site, click "Book a Consultation" in the nav, the footer, and a page CTA → the **LatePoint wizard popup** opens with Consultation preselected. The old custom modal no longer appears. `/book-a-consultation/` shows the inline wizard.

- [ ] **Step 7: Commit.**
```bash
git add build/templates/header.html build/templates/footer.html build/scripts/build-functional.php build/mu-plugins/gv-request-form.php
git commit -m "feat(booking): open LatePoint consultation wizard from Book a Consultation CTAs

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Theme the LatePoint wizard to the GV brand

**Files:**
- Modify: `build/mu-plugins/gv-assets/gv-brand.css` (add a `.gv-bookform-wrap .latepoint-*` block)
- Deploy: DEPLOY-CSS

**Interfaces:**
- Consumes: the `.latepoint-*` class names for the wizard steps/inputs/buttons/progress (record exact selectors from Task 0 / by inspecting the live wizard DOM).

- [ ] **Step 1: Capture the wizard's DOM.** With the wizard open (Task 2), inspect the markup (browser devtools or `curl` the booking page) and list the real selectors for: step container, step/progress indicator, inputs, labels, primary/secondary buttons, the time/day step, the summary. Record them.

- [ ] **Step 2: Write the theme block.** In `gv-brand.css`, under `.gv-bookform-wrap`, style those selectors to brand: navy/orange, Bebas headings, Montserrat/Inter, orange primary button, visible focus rings, 44px targets, 16px inputs. Apply the ui-ux-pro-max UX rules from the spec:
  - **Progress indicator** visible + branded ("Step X of Y").
  - **Real `<label>`s** styled (LatePoint uses labels; ensure they're visible, not placeholder-only).
  - **Submit/loading state** branded (style LatePoint's loading + success/error).
  - Inputs validate on blur (LatePoint default; ensure error styling is branded and near the field).
```css
/* .gv-bookform-wrap wraps LatePoint's [latepoint_book_form]/popup.
   Selectors below confirmed from the live wizard DOM in Step 1. */
.gv-bookform-wrap .latepoint-form { font-family: Inter, Arial, sans-serif; }
.gv-bookform-wrap .latepoint-step-heading { font-family:'Bebas Neue',sans-serif; color:var(--gv-navy); }
.gv-bookform-wrap .latepoint-btn-primary { background:var(--gv-orange); color:#fff; min-height:44px; }
.gv-bookform-wrap .latepoint-btn-primary:hover { background:var(--gv-orange-dark); }
.gv-bookform-wrap input:focus, .gv-bookform-wrap select:focus { outline:none; border-color:var(--gv-orange); box-shadow:0 0 0 3px rgba(244,123,32,.15); }
/* ...map remaining real selectors from Step 1 to brand tokens... */
```

- [ ] **Step 3: Deploy CSS** (DEPLOY-CSS) + LiteSpeed purge + Cloudflare purge of `gv-brand.css`.

- [ ] **Step 4: Verify.** Open the wizard at 375 / 768 / 1440px: brand colors/fonts applied, progress indicator visible, labels present, focus rings visible, primary button orange, no horizontal scroll, loading/success states branded.

- [ ] **Step 5: Commit** (`style(booking): theme LatePoint wizard to GV brand`).

---

## Task 4: Custom detail fields (player name, age, training interest, notes)

**Files:**
- Modify: `build/scripts/configure-consult-booking.php` (new; register custom fields per Task 0 mechanism) OR the LatePoint admin export if fields are DB rows.
- Deploy: `wp eval-file`

**Interfaces:**
- Produces: the wizard's details step collects `player_name` (req), `player_age` (req, 4–25), `training_interest` (select: Private/Small Group/Elite), `notes` (optional), stored on the booking (retrievable in the Task 6 hook).

- [ ] **Step 1: Add the fields** using the mechanism identified in Task 0 (admin custom-fields UI persisted as settings/meta, or a `latepoint_*` fields filter). Write it into the idempotent `configure-consult-booking.php`. Enforce age bounds with light JS if LatePoint's validation is thin (add via the wizard-step hook from Task 0).

- [ ] **Step 2: Deploy + apply**; flush caches.

- [ ] **Step 3: Verify.** In the wizard details step, the four fields render with labels; submitting an out-of-range age (e.g. 2 or 40) is rejected; a completed booking stores the values (check `wp_latepoint_booking_meta` or the booking's custom fields).

- [ ] **Step 4: Commit** (`feat(booking): consultation custom fields (player, age, interest, notes)`).

---

## Task 5: Anti-spam gate — OTP + Turnstile

**Files:**
- Modify: `build/mu-plugins/gv-consult-notify.php` (Turnstile inject + server-side verify hook — created in Task 6; if Task 5 runs first, create the file here)
- Modify: `build/mu-plugins/gv-assets/gv-brand.css` (Turnstile widget spacing)
- Deploy: mu-plugin + CSS

**Interfaces:**
- Consumes: `gv_rf_verify_turnstile($token,$ip)` (existing helper); the booking-reject/validate hook name from Task 0 (R2); `GV_TURNSTILE_SITEKEY/SECRET`.

- [ ] **Step 1: OTP.** From Task 0 R1: if OTP is native to the booking flow, confirm by completing a test booking and observing the required code step — **no code needed**. If NOT native, add the email-verify step (scope from Task 0 findings) before finalizing.

- [ ] **Step 2: Inject Turnstile into the wizard's details step** using the step-content hook from Task 0: render `<div class="cf-turnstile" data-sitekey="<GV_TURNSTILE_SITEKEY>">` + the Turnstile script.

- [ ] **Step 3: Verify Turnstile server-side on booking submit** via the booking-validate/reject hook from Task 0:
```php
add_filter('<latepoint_booking_validate_hook_from_task0>', function ($errors, $booking) {
    $token = isset($_POST['cf-turnstile-response']) ? sanitize_text_field(wp_unslash($_POST['cf-turnstile-response'])) : '';
    if (!gv_rf_verify_turnstile($token, $_SERVER['REMOTE_ADDR'] ?? '')) {
        $errors[] = __('Please complete the verification and try again.', 'latepoint');
    }
    return $errors;
}, 10, 2);
```
(Exact hook name + signature come from Task 0. If no clean reject hook exists, fall back to OTP-only and document it — do NOT ship a fake gate.)

- [ ] **Step 4: Deploy** mu-plugin + CSS; flush.

- [ ] **Step 5: Verify.** A booking with no/invalid Turnstile token is rejected server-side with a branded error; a booking with a valid token + verified OTP completes. Confirm bots/no-JS can't complete.

- [ ] **Step 6: Commit** (`feat(booking): Turnstile + OTP gate on consultation wizard`).

---

## Task 6: Branded coach email + Google Calendar button (LatePoint hook) + helper move

**Files:**
- Create: `build/mu-plugins/gv-consult-notify.php`
- Modify: `build/mu-plugins/gv-request-form.php` (remove moved helpers or have it require the new file)
- Create: `build/mu-plugins/tests/test-gv-consult-notify.php`
- Deploy: mu-plugins

**Interfaces:**
- Consumes: `gv_rf_gcal_url`, `gv_rf_next_weekday_date`, `gv_rf_email_shell` (move into `gv-consult-notify.php`); LatePoint `booking-created` hook + booking object accessors (venue, day, customer email, custom fields) from Task 0.
- Produces: on each new consultation booking, a branded coach email containing the "Add to Google Calendar" button (all-day on the booked day, customer as guest) + a branded customer confirmation.

- [ ] **Step 1: Move the helpers.** Cut `gv_rf_gcal_url`, `gv_rf_next_weekday_date`, `gv_rf_email_shell`, `gv_rf_verify_turnstile`, `gv_rf_locations` into `gv-consult-notify.php` (keep names). In `gv-request-form.php`, `require_once` the new file or remove the now-dead code. Keep the framework-free test green.

- [ ] **Step 2: Port the tests.** Copy the relevant assertions from `test-gv-request-form.php` into `test-gv-consult-notify.php` (include the new file; stub WP fns as the existing harness does). Run `php build/mu-plugins/tests/test-gv-consult-notify.php` → ALL PASS.

- [ ] **Step 3: Hook booking-created.** Using the hook from Task 0:
```php
add_action('<latepoint_booking_created_hook_from_task0>', function ($booking) {
    // pull fields off $booking (email, venue/location name, booked date, custom fields)
    $date  = $booking->start_date; // 'Y-m-d' of the booked day
    $email = $booking->customer->email;
    $gcal  = gv_rf_gcal_url([
        'title'    => 'GV Consultation — ' . /* player name custom field */,
        'date'     => $date,
        'guest'    => $email,
        'details'  => /* build <br>-joined summary from booking fields */,
        'location' => /* venue label */ . ', Metro Manila',
    ]);
    // build $inner (details table + the orange gcal button, reuse the markup from gv-request-form.php)
    $html = gv_rf_email_shell('New consultation booked', 'A consultation was booked on the website.', $inner);
    wp_mail('gvbasketballcoaching@gmail.com', 'New consultation — ' . /* player */, $html, ['Content-Type: text/html; charset=UTF-8']);
}, 10, 1);
```
Reuse the exact gcal-button markup from `gv-request-form.php` (orange anchor + "Opens a prefilled event…" note). Confirm `$booking` accessors against Task 0.

- [ ] **Step 4: Brand the customer confirmation.** Either LatePoint notification template or a `wp_mail` filter scoped to the booking-confirmation subject (mirror `gv-otp-email.php`'s pattern; keep it from touching OTP/other mail).

- [ ] **Step 5: Deploy** mu-plugins; flush.

- [ ] **Step 6: Verify.** Complete a test booking → coach inbox gets the branded email with a **working** "Add to Google Calendar" button (correct booked day, customer prefilled as guest); customer gets a branded confirmation. `php build/mu-plugins/tests/test-gv-consult-notify.php` passes.

- [ ] **Step 7: Commit** (`feat(booking): branded coach email + gcal button via LatePoint hook`).

---

## Task 7: Account opt-in checkbox + post-booking portal nudge

**Files:**
- Modify: `build/scripts/configure-consult-booking.php` (add the checkbox custom field) + `build/mu-plugins/gv-consult-notify.php` (post-booking CTA/email note)

**Interfaces:**
- Consumes: custom-field mechanism (Task 4); booking-created hook (Task 6).
- Produces: a "Create my member account" checkbox in the details step; when ticked, the confirmation surfaces a "view/manage at `/booking/`" CTA and a note that they log in with their emailed code.

- [ ] **Step 1: Add the checkbox** custom field ("Create my member account so I can view this online"). Document (in wiki, Task 8) that a LatePoint customer is created by email **regardless**; the checkbox only governs the portal nudge.

- [ ] **Step 2: Surface the nudge** when ticked: add the `/booking/` CTA to the on-screen confirmation (LatePoint confirmation-step content hook from Task 0) and/or a line in the customer confirmation email.

- [ ] **Step 3: Deploy; verify** ticked → portal CTA shown; unticked → no nudge; either way the booking appears in `/booking/` after the member logs in with the same email.

- [ ] **Step 4: Commit** (`feat(booking): optional member-account opt-in + portal nudge`).

---

## Task 8: Retire the old modal cleanly + wiki sync + end-to-end verify

**Files:**
- Modify: `build/mu-plugins/gv-request-form.php` (remove dead public-modal code; keep only what still serves, or delete if fully superseded)
- Modify: `wiki/booking-latepoint.md`, `wiki/forms-and-emails.md`, `wiki/pages.md`, `wiki/client-status.md`, `wiki/log.md`

- [ ] **Step 1: Remove the retired modal code** from `gv-request-form.php` (the `wp_footer` modal, `[gv_request_form]` shortcode, the 2982 redirect) now that helpers live in `gv-consult-notify.php` and page 2982 hosts the wizard. Confirm no page still references `[gv_request_form]` / `data-gv-open-modal`.

- [ ] **Step 2: Wiki sync.** Update `forms-and-emails.md` (public booking now = themed LatePoint wizard; modal retired; gcal via LatePoint hook), `booking-latepoint.md` (public self-booking, day-only slot, OTP+Turnstile gate, still view-only portal), `pages.md` (2982 now hosts the wizard), `client-status.md` (self-booking live), and append a `log.md` entry per the AGENTS.md schema.

- [ ] **Step 3: Full end-to-end verify** (the spec's verification list): button → themed popup → venue+day → details (custom fields + account checkbox + Turnstile) → OTP → booking confirms; coach branded email + gcal; booking visible in `/booking/` (view-only); accessibility at 375/768/1440; no pricing/payment surfaced; `php` helper tests pass.

- [ ] **Step 4: Commit** (`chore(booking): retire custom modal, sync wiki for self-service consultation`).

---

## Self-Review (completed by plan author)

- **Spec coverage:** every spec section maps to a task — config→T1, entry-swap→T2, theming(ui-ux)→T3, custom fields→T4, OTP+Turnstile→T5, coach email/gcal→T6, account opt-in→T7, portal impact + retire modal + wiki→T8; the spec's "Verify FIRST (R1–R3)"→T0.
- **Placeholder scan:** the only deferred specifics are LatePoint-internal names (hooks, selectors, shortcode attrs) that **cannot** be known without the installed plugin; they are explicitly resolved in Task 0 and referenced as "from Task 0," not left as vague TODOs. All GV-side code (config, gcal reuse, tests, CSS structure, deploy/verify commands) is concrete.
- **Type/name consistency:** helper names (`gv_rf_gcal_url`, `gv_rf_next_weekday_date`, `gv_rf_email_shell`, `gv_rf_verify_turnstile`) are used consistently and moved (not renamed) in T6.
- **Risk:** if Task 0 finds no clean OTP-in-booking or Turnstile-reject hook, Tasks 5 explicitly fall back (OTP-only / pre-submit guard) rather than shipping a fake gate.

## Execution Handoff

This plan is written for a **fresh agent** (per the user's handoff). Recommended: **superpowers:subagent-driven-development** — fresh subagent per task with two-stage review — and **start with Task 0**, which gates the rest. Do not begin Task 1+ until the Stage-0 findings file is written and committed.
