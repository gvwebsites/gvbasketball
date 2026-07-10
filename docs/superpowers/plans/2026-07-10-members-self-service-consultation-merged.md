# GV Members + Self-Service Consultation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

**Goal:** Launch a branded /members/ site with any-email OTP signup, a complete request/session timeline, and a themed LatePoint consultation wizard that creates a pending booking for Coach Gino to finalize at an exact 45-minute time.

**Architecture:** Keep one normal order-linked LatePoint booking as the sole request/session record. A focused MU-plugin extends the native wizard, stores GV fields in booking meta, owns unified member OTP, renders a read-only portal, sends the initial workflow emails, and exposes a tokenized coach finalization screen. Targeted idempotent scripts change only Player Consultation and pages 2982/2983; the destructive fresh-install LatePoint setup script is never run on production.

**Tech Stack:** WordPress 6.8.5, PHP 8.2, LatePoint 5.6.6 FREE, WP-CLI over ssh gvweb, Elementor helper functions, FluentSMTP, Cloudflare Turnstile, LiteSpeed, framework-free PHP tests, and headless Chrome/Playwright for report evidence.

---

**Approved design:** docs/superpowers/specs/2026-07-10-members-self-service-consultation-merged-design.md

## Non-negotiable implementation rules

- Work in an isolated codex/ worktree using superpowers:using-git-worktrees.
- Source of truth is build/. Production receives only locally reviewed files.
- Never edit LatePoint plugin files.
- Never execute build/scripts/setup-latepoint.php on production; it truncates LatePoint configuration tables.
- Keep Player Consultation duration at 45 minutes. Set only timeblock_interval to 180 minutes.
- New requests start as LatePoint pending; only the secure coach POST changes them to approved.
- A parent receives exactly one automatic consultation email: the initial receipt. Coach Gino personally sends the final schedule.
- Back up the affected page, theme parts, service/settings, and LatePoint data before mutation. wp db export is unreliable on this host.
- All date/time operations use Asia/Manila even though WordPress currently runs in UTC.
- /members/, OTP actions, and finalization responses are private and non-cacheable.
- Commit after every task using only that task's files. Preserve unrelated user changes.

## File structure

| File | Responsibility |
|---|---|
| build/mu-plugins/gv-members.php | Bootstrap, module loading, assets, cache protection, old-URL redirects |
| build/mu-plugins/gv-members/core.php | Pure validation, token, time, status, and formatting helpers |
| build/mu-plugins/gv-members/booking.php | Wizard fields, Turnstile, cart payload, booking metadata, notification suppression |
| build/mu-plugins/gv-members/emails.php | Shared shell, parent receipt, Coach Gino workflow email |
| build/mu-plugins/gv-members/auth.php | Any-email OTP request/verify/logout endpoints and customer creation |
| build/mu-plugins/gv-members/portal.php | Login application, requests, sessions, profile, player reuse |
| build/mu-plugins/gv-members/finalize.php | Secure GET screen and nonce-protected exact-time approval POST |
| build/mu-plugins/gv-members/assets/gv-members.css | Scoped portal, wizard, and finalizer presentation |
| build/mu-plugins/gv-members/assets/gv-members.js | OTP UI, member tabs, CTA bridge, player reuse, accessibility states |
| build/mu-plugins/tests/test-gv-members-core.php | Pure helper and validation tests |
| build/mu-plugins/tests/test-gv-members-emails.php | Email content and conditional-CTA tests |
| build/mu-plugins/tests/test-gv-members-contracts.php | Hook, route, security, notification, and source-contract tests |
| build/scripts/configure-members-consultation.php | Idempotent non-destructive service/settings changes |
| build/scripts/configure-members-page.php | Targeted page-2983 title/slug/content migration |
| build/scripts/configure-consultation-page.php | Targeted page-2982 native-wizard fallback |
| build/scripts/render-member-report-emails.php | Render exact email builders with safe sample data |
| build/templates/header.html and footer.html | Member URL and consultation CTA source markup |
| build/scripts/build-functional.php | Keep future full rebuilds consistent |
| build/mu-plugins/gv-request-form.php | Retire old modal/redirect; retain temporary helper wrappers |
| docs/CLIENT-REPORT-JULY.html | Replace obsolete claims and show final evidence |
| docs/screenshots/*.png | Final public, member, finalizer, and email evidence |
| wiki/*.md | Architecture, operations, pages, email, deployment, status, and log |

### Task 0: Isolated worktree and production baseline

**Files:**
- Read: approved design and all wiki files named above
- Create during execution: resources/backups/members-consultation-baseline.md

- [ ] **Step 1: Create the implementation worktree.** Invoke superpowers:using-git-worktrees, create branch codex/members-consultation, and verify git status --short is empty.

- [ ] **Step 2: Record the immutable baseline.** Run:

~~~bash
ssh gvweb "cd /home/u907133977/domains/gvbasketball.com/public_html && wp core version && wp plugin get latepoint --field=version && wp post get 2983 --fields=ID,post_title,post_name,post_status --format=json && wp db query 'SELECT id,name,duration,timeblock_interval,override_default_booking_status FROM wp_latepoint_services WHERE name=0x506c6179657220436f6e73756c746174696f6e; SELECT COUNT(*) AS customers FROM wp_latepoint_customers; SELECT COUNT(*) AS bookings FROM wp_latepoint_bookings;'"
~~~

Expected: WordPress 6.8.5, LatePoint 5.6.6, page 2983 on booking, consultation duration 45, and current customer/booking counts.

- [ ] **Step 3: Save scoped production backups.** Create one timestamped directory and export page 2983, header 3002, footer 2991, latepoint_services, latepoint_service_meta, latepoint_settings, latepoint_customers, latepoint_bookings, latepoint_booking_meta, latepoint_orders, and latepoint_order_items. Use table-scoped wp db query output, not wp db export. Record the remote directory and baseline counts in resources/backups/members-consultation-baseline.md.

- [ ] **Step 4: Reconfirm installed hooks.** Run:

~~~bash
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html/wp-content/plugins/latepoint && grep -Rni "latepoint_booking_steps_contact_after\|latepoint_process_step\|latepoint_booking_created\|latepoint_booking_updated" lib/controllers lib/helpers lib/models lib/views | head -40'
~~~

Expected: contact hook in _contact_form.php, process hook in steps_controller.php, creation hook in order_intent_model.php, and update hook in booking_model.php.

### Task 1: Pure domain helpers with TDD

**Files:**
- Create: build/mu-plugins/gv-members/core.php
- Create: build/mu-plugins/tests/test-gv-members-core.php

- [ ] **Step 1: Write the failing helper tests.** Stub only the WordPress functions needed by core.php and assert:

~~~php
$valid = gv_members_validate_payload([
    'player_name' => 'Alex Victorino',
    'player_age' => '12',
    'training_interest' => 'small_group',
    'contact_alt' => '@parent',
    'note' => 'First consultation',
    'member_opt_in' => 'yes',
]);
gv_assert_same([], $valid['errors'], 'valid request');
gv_assert_same(12, $valid['data']['player_age'], 'age normalized');
gv_assert_same('Small Group', gv_members_interest_label('small_group'), 'interest label');
gv_assert_same('Submitted', gv_members_status_label('pending'), 'pending label');
gv_assert_same('Confirmed', gv_members_status_label('approved'), 'approved label');
gv_assert_same([900,915,930,945,960,975,990,1005,1020,1035], gv_members_candidate_start_times(), 'available starts');
gv_assert_false(gv_members_secure_equals(gv_members_token_hash('alpha'), gv_members_token_hash('beta')), 'wrong token');
gv_assert_true(gv_members_secure_equals(gv_members_token_hash('alpha'), gv_members_token_hash('alpha')), 'same token');
gv_assert_contains('GV%20Basketball%20booking%20ABC123', gv_members_change_mailto('ABC123'), 'change email');
~~~

Also assert ages 2 and 100 fail, unknown interest fails, name over 100 characters fails, contact over 120 fails, note over 500 fails, and a finalization token expires after 30 days.

- [ ] **Step 2: Run red.**

~~~bash
php build/mu-plugins/tests/test-gv-members-core.php
~~~

Expected: non-zero exit because core.php or its functions do not exist.

- [ ] **Step 3: Implement the stable helper surface.**

~~~php
defined('GV_MEMBERS_FINALIZE_TTL') || define('GV_MEMBERS_FINALIZE_TTL', 30 * DAY_IN_SECONDS);

function gv_members_normalize_email($email) { return strtolower(trim((string) $email)); }
function gv_members_interest_options() { return ['private' => 'Private', 'small_group' => 'Small Group', 'elite' => 'Elite Performance']; }
function gv_members_interest_label($key) { $all = gv_members_interest_options(); return $all[$key] ?? ''; }
function gv_members_status_label($status) { return $status === 'approved' ? 'Confirmed' : ($status === 'pending' ? 'Submitted' : ucfirst((string) $status)); }
function gv_members_candidate_start_times() { return range(900, 1035, 15); }
function gv_members_token_hash($raw) { return hash_hmac('sha256', (string) $raw, function_exists('wp_salt') ? wp_salt('auth') : 'gv-members-test-salt'); }
function gv_members_secure_equals($known, $given) { return is_string($known) && is_string($given) && hash_equals($known, $given); }
function gv_members_token_expired($expires, $now = null) { return (int) $expires < (int) ($now ?? time()); }
function gv_members_change_mailto($reference) {
    return 'mailto:gvbasketballcoaching@gmail.com?subject=' . rawurlencode('GV Basketball booking ' . $reference) .
        '&body=' . rawurlencode('Hi Coach Gino, I need to request a change for booking ' . $reference . '.');
}
~~~

Implement gv_members_validate_payload() with the exact field limits from the tests and return keys player_name, player_age, training_interest, contact_alt, note, member_opt_in as yes/no, and day_request as yes.

- [ ] **Step 4: Run green.** Expected: PASS: GV Members core and exit 0.

- [ ] **Step 5: Commit.**

~~~bash
git add build/mu-plugins/gv-members/core.php build/mu-plugins/tests/test-gv-members-core.php
git commit -m "test: define GV members domain contracts"
~~~

### Task 2: Non-destructive LatePoint and page configuration

**Files:**
- Create: build/scripts/configure-members-consultation.php
- Create: build/scripts/configure-members-page.php
- Create: build/scripts/configure-consultation-page.php
- Modify: build/scripts/enable-member-auth.php
- Modify: build/scripts/build-functional.php
- Create: build/mu-plugins/tests/test-gv-members-contracts.php

- [ ] **Step 1: Write a static contract test that fails.** Assert all three scripts exist; none contains TRUNCATE, booking deletion, or service creation; the consultation script contains duration 45, interval 180, override status pending, require_otp_for_new_contacts, minimal native contact fields, /members/, and hidden public visibility for the three paid training services; the page-2982 script contains latepoint_book_form.

- [ ] **Step 2: Run red.** Run php build/mu-plugins/tests/test-gv-members-contracts.php. Expected: failure because the scripts are absent.

- [ ] **Step 3: Implement targeted consultation configuration.**

~~~php
$service = (new OsServiceModel())->where(['name' => 'Player Consultation'])->set_limit(1)->get_results_as_models();
if (!$service || $service->is_new_record()) {
    fwrite(STDERR, "Player Consultation not found\n");
    exit(1);
}
$service->duration = 45;
$service->timeblock_interval = 180;
$service->override_default_booking_status = LATEPOINT_BOOKING_STATUS_PENDING;
if (!$service->save()) {
    fwrite(STDERR, implode(', ', (array) $service->get_error_messages()) . "\n");
    exit(1);
}
$settings = [
    'notifications_email_processor' => 'wp_mail',
    'selected_customer_authentication_method' => 'otp',
    'default_customer_authentication_method' => 'otp',
    'selected_customer_authentication_field_type' => 'email',
    'require_otp_for_new_contacts' => 'on',
    'page_url_customer_dashboard' => '/members/',
    'page_url_customer_login' => '/members/',
    'enable_payments_local' => 'off',
];
foreach ($settings as $name => $value) OsSettingsHelper::save_setting_by_name($name, $value);
$customer_fields = OsSettingsHelper::get_default_fields_for_customer();
foreach (['first_name', 'last_name', 'email'] as $name) {
    $customer_fields[$name]['active'] = true;
    $customer_fields[$name]['required'] = true;
}
$customer_fields['phone']['active'] = false;
$customer_fields['phone']['required'] = false;
$customer_fields['notes']['active'] = false;
$customer_fields['notes']['required'] = false;
OsSettingsHelper::save_setting_by_name('default_fields_for_customer', wp_json_encode($customer_fields));
foreach (['Private Training', 'Small Group Training', 'Elite Performance'] as $private_name) {
    $private_service = (new OsServiceModel())->where(['name' => $private_name])->set_limit(1)->get_results_as_models();
    if ($private_service && !$private_service->is_new_record()) {
        $private_service->visibility = 'hidden';
        $private_service->save();
    }
}
printf("consultation=%d duration=%d interval=%d status=%s\n", $service->id, $service->duration, $service->timeblock_interval, $service->override_default_booking_status);
~~~

The script must find the existing service by name and never recreate or truncate configuration.

- [ ] **Step 4: Implement targeted page migration.** configure-members-page.php must fail if page 2983 is absent, update only its title to Members, slug to members, publish status, and Elementor blocks containing the branded intro, [gv_members_portal], and Help / Email GV Basketball section. Use gv_set_page_blocks(2983, $blocks).

- [ ] **Step 5: Implement the consultation landing fallback.** configure-consultation-page.php must find Player Consultation by name, fail if page 2982 or the service is absent, and build the shortcode with `$shortcode = '[latepoint_book_form selected_service="' . (int) $service->id . '" hide_side_panel="yes"]';` inside gv-bookform-wrap. Update only page 2982 with the existing consultation introduction plus that shortcode. This page must make no redirect and must not contain [gv_request_form].

- [ ] **Step 6: Keep rebuild sources aligned.** Change enable-member-auth.php URLs to /members/. In build-functional.php change page 2983 copy/shortcode to [gv_members_portal] and page 2982 to the native consultation form. Do not deploy the full script.

- [ ] **Step 7: Run green and commit.**

~~~bash
php build/mu-plugins/tests/test-gv-members-contracts.php
git add build/scripts/configure-members-consultation.php build/scripts/configure-members-page.php build/scripts/configure-consultation-page.php build/scripts/enable-member-auth.php build/scripts/build-functional.php build/mu-plugins/tests/test-gv-members-contracts.php
git commit -m "feat: add targeted members and consultation configuration"
~~~

### Task 3: Bootstrap, cache protection, redirects, and CTA bridge

**Files:**
- Create: build/mu-plugins/gv-members.php
- Create: build/mu-plugins/gv-members/assets/gv-members.js
- Create: build/mu-plugins/gv-members/assets/gv-members.css
- Modify: build/mu-plugins/tests/test-gv-members-contracts.php

- [ ] **Step 1: Extend the failing contract test.** Require the bootstrap to include all module files, register [gv_members_portal], version assets with filemtime, protect private responses, and redirect /booking/ and /customer-cabinet/.

- [ ] **Step 2: Implement the bootstrap.** Load core.php immediately and LatePoint-dependent files on plugins_loaded priority 20. Register:

~~~php
add_action('template_redirect', 'gv_members_private_response', 0);
add_action('template_redirect', 'gv_members_legacy_redirect', 1);
add_action('wp_enqueue_scripts', 'gv_members_enqueue_assets', 30);
add_action('wp_footer', 'gv_members_hidden_booking_trigger', 40);
~~~

gv_members_private_response() detects page 2983, gv_finalize_consultation=1, and GV OTP AJAX actions; it calls nocache_headers(), defines DONOTCACHEPAGE if absent, fires litespeed_control_set_nocache, and sends Cache-Control: private, no-store, max-age=0.

gv_members_legacy_redirect() sends 301 to /members/ only for front-end GET requests on /booking/ or /customer-cabinet/. It must skip AJAX, REST, admin, and WP-CLI.

- [ ] **Step 3: Add the hidden native wizard trigger.** Resolve Player Consultation by name at render time and output:

~~~php
echo '<div id="gv-consult-trigger" hidden>' .
    do_shortcode('[latepoint_book_button caption="Book a Consultation" selected_service="' . (int) $service->id . '" hide_side_panel="yes"]') .
    '</div>';
~~~

In gv-members.js, intercept same-origin /book-a-consultation/ links and [data-gv-consultation], preserve modified clicks, and click #gv-consult-trigger .os_trigger_booking.

- [ ] **Step 4: Add initial scoped CSS.** Define brand variables and base rules under .gv-members, .gv-finalize, .gv-bookform-wrap, and .latepoint-w. Include visible :focus-visible, 44px controls, 16px mobile inputs, reduced-motion support, and hidden behavior.

- [ ] **Step 5: Verify and commit.**

~~~bash
php -l build/mu-plugins/gv-members.php
php build/mu-plugins/tests/test-gv-members-contracts.php
git add build/mu-plugins/gv-members.php build/mu-plugins/gv-members/assets/gv-members.js build/mu-plugins/gv-members/assets/gv-members.css build/mu-plugins/tests/test-gv-members-contracts.php
git commit -m "feat: bootstrap private GV members experience"
~~~

### Task 4: Native wizard fields, Turnstile, OTP, and cart payload

**Files:**
- Create: build/mu-plugins/gv-members/booking.php
- Modify: gv-members.js, gv-members.css, core tests, contract tests

- [ ] **Step 1: Add failing field/hook tests.** Assert names gv_consult[player_name], player_age, training_interest, contact_alt, note, and member_opt_in; age 3–99; Turnstile required; and latepoint_process_step handlers at priorities 1 and 20.

- [ ] **Step 2: Render minimal fields.** Hook latepoint_booking_steps_contact_after and return unless the booking service is Player Consultation. Render real labels, required markers, the fixed interest values, optional phone/Instagram, one 500-character note, member-promotion checkbox, honeypot, and Turnstile using GV_TURNSTILE_SITEKEY.

- [ ] **Step 3: Reject invalid customer-step requests before LatePoint advances.**

~~~php
$checked = gv_members_validate_payload($params['gv_consult'] ?? []);
if ($checked['errors']) {
    wp_send_json(['status' => LATEPOINT_STATUS_ERROR, 'message' => reset($checked['errors']), 'send_to_step' => 'customer', 'fields_to_update' => []]);
}
if (!empty($params['gv_website'])) {
    wp_send_json(['status' => LATEPOINT_STATUS_ERROR, 'message' => 'Please refresh and try again.', 'send_to_step' => 'customer', 'fields_to_update' => []]);
}
$turnstile = sanitize_text_field($params['cf-turnstile-response'] ?? '');
if (!gv_members_verify_turnstile($turnstile, $_SERVER['REMOTE_ADDR'] ?? '')) {
    wp_send_json(['status' => LATEPOINT_STATUS_ERROR, 'message' => 'Please complete the security check.', 'send_to_step' => 'customer', 'fields_to_update' => []]);
}
~~~

Register this on latepoint_process_step priority 1. gv_members_verify_turnstile() posts secret, response, and remote IP to Cloudflare and requires success true. It fails closed when production constants are absent.

- [ ] **Step 4: Persist the validated payload after LatePoint customer processing.** Register priority 20 and call:

~~~php
OsStepsHelper::$cart_object->save_meta_by_key('gv_consult_payload', wp_json_encode($checked['data']));
~~~

The require_otp_for_new_contacts setting makes the native booking flow verify unknown emails; existing customer emails are also OTP-gated. No booking exists before order conversion.

- [ ] **Step 5: Theme day-only selection.** Keep internal 15:00 nominal data but replace visible consultation-slot text with Request this day after every step render. Add: Coach Gino will coordinate the exact 45-minute time after reviewing your request. Never alter the submitted start_time.

- [ ] **Step 6: Prevent accidental duplicates in the browser.** Disable the active forward/verify control while the LatePoint request is in flight, preserve LatePoint's retryable error state, and re-enable only after error or step transition. The server remains authoritative; the UI lock is not the only duplicate defense.

- [ ] **Step 7: Test, lint, and commit.**

~~~bash
php -l build/mu-plugins/gv-members/booking.php
php build/mu-plugins/tests/test-gv-members-core.php
php build/mu-plugins/tests/test-gv-members-contracts.php
git add build/mu-plugins/gv-members/booking.php build/mu-plugins/gv-members/assets/gv-members.js build/mu-plugins/gv-members/assets/gv-members.css build/mu-plugins/tests/test-gv-members-core.php build/mu-plugins/tests/test-gv-members-contracts.php
git commit -m "feat: collect verified consultation requests in LatePoint"
~~~

### Task 5: Booking metadata and exactly two initial workflow emails

**Files:**
- Create: build/mu-plugins/gv-members/emails.php
- Create: build/mu-plugins/tests/test-gv-members-emails.php
- Modify: booking.php and contract tests

- [ ] **Step 1: Write failing email tests.**

~~~php
$receipt = gv_members_parent_receipt_html($sample, ['member_opt_in' => 'no']);
gv_assert_contains('Request received', $receipt, 'receipt state');
gv_assert_contains('exact time', $receipt, 'timing copy');
gv_assert_not_contains('3:00 PM', $receipt, 'nominal time hidden');
gv_assert_not_contains('/members/', $receipt, 'no promotion');

$opted_in = gv_members_parent_receipt_html($sample, ['member_opt_in' => 'yes']);
gv_assert_contains('/members/', $opted_in, 'promotion shown');

$coach = gv_members_coach_request_html($sample, $meta, 'https://example.invalid/finalize');
foreach (['1. Review', '2. Contact', '3. Agree', '4. Open', '5. Select', '6. Personally send'] as $step) gv_assert_contains($step, $coach, $step);
gv_assert_contains('does not send an automatic final confirmation', $coach, 'manual final warning');
~~~

- [ ] **Step 2: Implement the email shell/builders.** Use the crest PNG and inline-safe navy/orange tables. Parent subject: GV Basketball — consultation request received. Coach subject: New consultation request — Player — Venue — Day. The receipt includes player, interest, venue/day, Request received, exact-time-to-follow copy, and /members/ only when opted in. The coach email includes submitted details, all six numbered steps, booking reference, and Finalize Consultation. Neither email includes the nominal time or Google Calendar.

Use one array view-model contract so the email tests and report renderer execute the same production builders:

~~~php
function gv_members_booking_email_data(OsBookingModel $booking): array;
function gv_members_parent_receipt_html(array $booking_data, array $meta): string;
function gv_members_coach_request_html(array $booking_data, array $meta, string $finalize_url): string;
~~~

- [ ] **Step 3: Persist meta at booking creation.** Register latepoint_booking_created priority 5. For Player Consultation, read gv_consult_payload from OsStepsHelper::$cart_object, revalidate, save every approved gv_* key, and force pending if a conflicting global setting changed it.

- [ ] **Step 4: Create the finalization secret.** Generate bin2hex(random_bytes(32)); store only gv_members_token_hash(raw), expiry time()+GV_MEMBERS_FINALIZE_TTL, and blank used/finalized values. Build the raw-token URL with add_query_arg(); never log the token.

- [ ] **Step 5: Own notification delivery.** Before priority 12, remove OsProcessJobsHelper::handle_booking_created for this consultation request. Send the parent receipt and coach email once, save gv_parent_receipt_sent_at and gv_coach_request_sent_at, and log mail failures with booking ID only. Do not retry in the web request.

- [ ] **Step 6: Verify and commit.**

~~~bash
php build/mu-plugins/tests/test-gv-members-emails.php
php build/mu-plugins/tests/test-gv-members-contracts.php
git add build/mu-plugins/gv-members/emails.php build/mu-plugins/gv-members/booking.php build/mu-plugins/tests/test-gv-members-emails.php build/mu-plugins/tests/test-gv-members-contracts.php
git commit -m "feat: send consultation receipt and coach workflow email"
~~~

### Task 6: Secure exact-time finalization for Coach Gino

**Files:**
- Create: build/mu-plugins/gv-members/finalize.php
- Modify: gv-members.css, core tests, contract tests

- [ ] **Step 1: Write failing finalizer tests.** Assert ten starts from 15:00 to 17:15; invalid, expired, used, wrong-booking, non-consultation, and non-pending tokens fail; GET has no update call; POST requires nonce and availability; a valid POST produces a 45-minute approved booking once.

- [ ] **Step 2: Register the private route.** Use gv_finalize_consultation=1 at /members/finalize/. GET accepts booking reference and token, loads by booking_code, verifies HMAC hash/expiry/unused/pending/service, and reveals no PII on failure.

- [ ] **Step 3: Generate available exact times using LatePoint.** For each candidate, clone the booking, set duration 45 and candidate start, build LatePoint\Misc\BookingRequest::create_from_booking_model(), and call:

~~~php
OsBookingHelper::is_booking_request_available($request, ['exclude_booking_ids' => [(int) $booking->id]])
~~~

Render only passing choices inside a nonce-protected POST form.

- [ ] **Step 4: Make approval atomic and single-use.** Start a DB transaction, lock the booking row FOR UPDATE, reload/reverify token and state, recheck availability, clone old booking, calculate end date/time and UTC fields, and update start/end/status in one update_attributes() call. Save used/finalized timestamps. Temporarily remove OsProcessJobsHelper::handle_booking_updated, fire latepoint_booking_updated once for activity consumers, restore the callback, and commit. Roll back every failure.

- [ ] **Step 5: Render success without email.** Display: Booking updated. Please contact the parent now to confirm the final schedule. Show parent contact only after token verification. Do not call wp_mail.

If no candidate passes availability, keep the booking pending and render: No 45-minute time remains on this day. Contact the parent to agree on another day. For an already-used token whose booking is approved, show the existing confirmed date/time read-only without firing hooks or mail.

- [ ] **Step 6: Test, lint, and commit.**

~~~bash
php -l build/mu-plugins/gv-members/finalize.php
php build/mu-plugins/tests/test-gv-members-core.php
php build/mu-plugins/tests/test-gv-members-contracts.php
git add build/mu-plugins/gv-members/finalize.php build/mu-plugins/gv-members/assets/gv-members.css build/mu-plugins/tests/test-gv-members-core.php build/mu-plugins/tests/test-gv-members-contracts.php
git commit -m "feat: let Coach Gino securely finalize consultation times"
~~~

### Task 7: Any-email member OTP signup and login

**Files:**
- Create: build/mu-plugins/gv-members/auth.php
- Modify: gv-members.js, core tests, contract tests

- [ ] **Step 1: Write failing auth contracts.** Assert logged-out request/verify AJAX actions, nonce checks, hashed email/IP limits, generic responses, one unknown-email customer, and no duplicate on repeat.

- [ ] **Step 2: Implement OTP request.** Validate nonce and email syntax; allow five sends per email and ten per IP per hour using HMAC-hashed transient keys; call:

~~~php
OsOTPHelper::generateAndSendOTP($email, 'email', 'email');
~~~

Known and unknown addresses get the same success: If the address can receive mail, a six-digit code is on its way. Invalid syntax may say Enter a valid email address.

- [ ] **Step 3: Verify OTP and create unknown customers.** Call OsOTPHelper::verifyOTP(). Find by normalized email. If absent, create one active non-guest LatePoint customer while temporarily filtering latepoint_customer_model_validations to require only unique valid email. Fire latepoint_customer_created, mark the contact verified, and call OsAuthHelper::authorize_customer().

- [ ] **Step 4: Prevent races.** Query by email immediately before save and rely on LatePoint uniqueness. If a concurrent request wins, load and authorize that row. Never create a WordPress user.

- [ ] **Step 5: Implement nonced POST logout.** Call OsAuthHelper::logout_customer() and redirect to /members/. No GET logout.

- [ ] **Step 6: Build accessible OTP JS.** Email form requests; code form verifies; role=status for progress and role=alert for errors; resend after 30 seconds; six one-character inputs support paste/backspace; submit one hidden six-digit value.

- [ ] **Step 7: Test and commit.**

~~~bash
php -l build/mu-plugins/gv-members/auth.php
php build/mu-plugins/tests/test-gv-members-core.php
php build/mu-plugins/tests/test-gv-members-contracts.php
git add build/mu-plugins/gv-members/auth.php build/mu-plugins/gv-members/assets/gv-members.js build/mu-plugins/tests/test-gv-members-core.php build/mu-plugins/tests/test-gv-members-contracts.php
git commit -m "feat: allow any verified email into GV Members"
~~~

### Task 8: Requests, sessions, players, and profile

**Files:**
- Create: build/mu-plugins/gv-members/portal.php
- Modify: gv-members.css, gv-members.js, core tests, contract tests

- [ ] **Step 1: Write failing portal tests.** With two customer fixtures, assert ownership isolation, pending time suppression, approved exact time, newest-first requests, pending/cancelled exclusion from sessions, unique player reuse, and booking reference in change email.

- [ ] **Step 2: Register [gv_members_portal].** Logged out renders the custom OTP application. Logged in loads only OsAuthHelper::get_logged_in_customer() and queries customer_id from that object; never accept customer ID from request data.

- [ ] **Step 3: Render Requests.** Player Consultation pending maps to Submitted and shows requested day/venue/player/age/interest/note/reference without time. Approved maps to Confirmed and shows exact date/time, venue, Coach Gino, player, interest, note, and reference. Every card has Need to make a change? Email GV Basketball.

- [ ] **Step 4: Render Confirmed Sessions.** Query all owned approved bookings, separate upcoming/past in Manila time, and show service, exact time, venue, agent, status, and ownership-checked ICS download. Never show orders, prices, payments, New Appointment, reschedule, or cancel controls.

- [ ] **Step 5: Render player reuse and New Request.** Derive unique player name/age pairs from owned booking meta. Expose escaped JSON to JS. In the signed-in wizard offer prior players plus New player; prefill/lock name and verified email.

- [ ] **Step 6: Render/update Profile.** Editable first/last/phone, read-only verified email, Email GV Basketball to change this address, and nonced logout. Profile POST updates only those three editable fields.

- [ ] **Step 7: Complete the training-journal UI.** White canvas, deep-navy framing, shallow real-photo strip, orange timeline nodes/actions, one readable column, keyboard tabs, focus states, mobile cards, and empty states with New Request.

- [ ] **Step 8: Test and commit.**

~~~bash
php -l build/mu-plugins/gv-members/portal.php
php build/mu-plugins/tests/test-gv-members-core.php
php build/mu-plugins/tests/test-gv-members-contracts.php
git add build/mu-plugins/gv-members/portal.php build/mu-plugins/gv-members/assets/gv-members.css build/mu-plugins/gv-members/assets/gv-members.js build/mu-plugins/tests/test-gv-members-core.php build/mu-plugins/tests/test-gv-members-contracts.php
git commit -m "feat: show member requests and confirmed sessions"
~~~

### Task 9: Retire the old modal and align every public entry point

**Files:**
- Modify: build/mu-plugins/gv-request-form.php
- Modify: build/templates/header.html and footer.html
- Modify: build/pages/training-programs.html
- Modify: build/scripts/deploy-training-programs.php
- Modify: old and new contract tests

- [ ] **Step 1: Write failing legacy-removal contracts.** Assert no admin_post_nopriv_gv_request_form, old wp_footer modal hook, gv_open_modal redirect, or [gv_request_form] in current page sources.

- [ ] **Step 2: Reduce gv-request-form.php to compatibility helpers.** Retain tested gcal, email-shell, Turnstile, location, and date helpers while removing shortcode, POST handlers, modal markup/JS, and redirect. Add deprecation docblocks; do not delete the file while callers/tests remain.

- [ ] **Step 3: Align source CTAs.** Member links become /members/. Book links remain crawlable at /book-a-consultation/ and receive data-gv-consultation so JS opens the native wizard. Update Training Programs source/deployer so future deploys cannot restore the old modal.

- [ ] **Step 4: Run both suites and commit.**

~~~bash
php build/mu-plugins/tests/test-gv-request-form.php
php build/mu-plugins/tests/test-gv-members-contracts.php
git add build/mu-plugins/gv-request-form.php build/templates/header.html build/templates/footer.html build/pages/training-programs.html build/scripts/deploy-training-programs.php build/mu-plugins/tests/test-gv-request-form.php build/mu-plugins/tests/test-gv-members-contracts.php
git commit -m "refactor: retire the email-only consultation modal"
~~~

### Task 10: Local verification gate

- [ ] **Step 1: Run every test.**

~~~bash
for test in build/mu-plugins/tests/test-*.php; do php "$test" || exit 1; done
~~~

Expected: every harness prints PASS and exits 0.

- [ ] **Step 2: Lint changed PHP.**

~~~bash
git diff --name-only main...HEAD -- '*.php' | while read -r file; do php -l "$file" || exit 1; done
~~~

- [ ] **Step 3: Scan safety contracts.**

~~~bash
rg -n "TRUNCATE|setup-latepoint.php|wp_create_user|wp_insert_user|data-gv-open-modal|gv_open_modal" build/mu-plugins/gv-members* build/scripts/configure-members-* build/templates build/pages/training-programs.html
~~~

Expected: no destructive SQL, production setup call, WordPress-user creation, or legacy modal token in active sources.

- [ ] **Step 4: Review and correct.** Run git diff --check, git status --short, and git diff main...HEAD. Commit verified corrections as fix: close GV members verification gaps.

### Task 11: Phased production deployment

- [ ] **Step 1: Reconfirm backups and baseline counts.** Stop if service ID/name/duration or page ID changed unexpectedly.

- [ ] **Step 2: Deploy code before configuration.** Upload gv-members.php, the gv-members/ directory, and slimmed gv-request-form.php. Verify remotely:

~~~bash
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && wp eval "echo shortcode_exists("gv_members_portal") ? "shortcode=ok\n" : "shortcode=missing\n";" && wp eval "echo function_exists("gv_members_validate_payload") ? "helpers=ok\n" : "helpers=missing\n";"'
~~~

Expected: shortcode=ok and helpers=ok, with no PHP fatal.

- [ ] **Step 3: Apply only the targeted LatePoint script.**

~~~bash
scp build/scripts/configure-members-consultation.php gvweb:~/configure-members-consultation.php
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && wp eval-file ~/configure-members-consultation.php && rm ~/configure-members-consultation.php'
~~~

Expected: duration 45, interval 180, pending, OTP required, URLs /members/.

- [ ] **Step 4: Apply page and theme changes.** Run configure-members-page.php and configure-consultation-page.php, then use documented targeted theme-part helpers for header/footer. Do not run full build-functional.php.

- [ ] **Step 5: Guard Training Programs against source drift.** Compare live Elementor data/content-image URLs to local source. Deploy through deploy-training-programs.php only if non-consultation content matches; otherwise patch only CTA/modal blocks and document drift.

- [ ] **Step 6: Purge Elementor, LiteSpeed, and Cloudflare caches.** Keep Rocket Loader off.

- [ ] **Step 7: Verify routes/cache.** /booking/ and /customer-cabinet/ 301 to /members/; /members/ has no public max-age; /book-a-consultation/ renders the themed native wizard.

### Task 12: Controlled production acceptance

- [ ] **Step 1: Submit one real controlled request.** Use a readable test inbox, venue/day, required fields, optional phone/Instagram, unchecked member promotion, Turnstile, and OTP.

- [ ] **Step 2: Verify persistence.** Exactly one customer, order, order item, and order-linked pending booking. Duration 45 and every gv_* meta key present. Nominal time hidden publicly.

- [ ] **Step 3: Verify email count/content.** One parent receipt and one coach operations email; no native duplicate. Parent has no nominal time and no /members/ CTA when unchecked. Coach email has six steps and finalizer. Repeat once with a separate controlled email and member promotion checked; its receipt must contain the /members/ CTA while the booking/customer behavior remains otherwise identical.

- [ ] **Step 4: Verify secure finalization.** GET does not mutate. One valid POST changes exact start/end/UTC/status once and marks token used. Repeat cannot mutate.

- [ ] **Step 5: Verify manual final-email boundary.** Parent receives no second automatic email. Success tells Coach Gino to contact the parent personally.

- [ ] **Step 6: Verify any-email signup.** A second unknown email creates exactly one active email-only customer. Logout/relogin does not duplicate.

- [ ] **Step 7: Verify member ownership/timeline.** Before approval, Submitted hides time. After approval, Confirmed shows exact time and Confirmed Sessions includes it. Another customer's records never appear.

- [ ] **Step 8: Verify accessibility/responsiveness.** At 375, 768, and 1440: keyboard, labels, focus, error announcements, OTP paste, modal focus return, 44px targets, 16px inputs, and no horizontal overflow.

- [ ] **Step 9: Remove or label test data.** Delete only controlled test records through LatePoint admin if Coach Gino does not want them retained.

### Task 13: Capture live UI and all email evidence; update July report

**Files:**
- Create: build/scripts/render-member-report-emails.php
- Create/replace: docs/screenshots/consultation-request.png
- Create: docs/screenshots/members-login.png
- Create: docs/screenshots/members-requests.png
- Create: docs/screenshots/coach-finalize-consultation.png
- Create: docs/screenshots/member-otp-email.png
- Create: docs/screenshots/consultation-parent-receipt-email.png
- Create: docs/screenshots/consultation-coach-email.png
- Modify: docs/CLIENT-REPORT-JULY.html

- [ ] **Step 1: Capture final live UI, not fixtures.**

~~~text
consultation-request.png — themed wizard with Request this day and minimal fields
members-login.png — /members/ unified email OTP entry
members-requests.png — signed-in timeline with Submitted and Confirmed examples and no unrelated PII
coach-finalize-consultation.png — valid token screen with time choices; token redacted from visible browser chrome
~~~

Use 1440px desktop report captures. Also inspect at 375px. Crop chrome consistently with existing report images.

- [ ] **Step 2: Render exact email builders safely.** render-member-report-emails.php calls gv_otp_email_html('482731'), gv_members_parent_receipt_html(), and gv_members_coach_request_html() using fictional identities, a future sample date, and https://example.invalid/ finalization. It writes HTML only under wp eval-file and never sends mail.

- [ ] **Step 3: Capture all three emails at 720px width/full height.**

~~~text
member-otp-email.png
consultation-parent-receipt-email.png
consultation-coach-email.png
~~~

Verify OTP visible, receipt hides nominal time, coach shows six steps, and no live token/PII appears.

- [ ] **Step 4: Replace Part 1 Member Login copy.** State any verified email can create/access a profile, requests appear automatically, members see Submitted/Confirmed history, and changes are requested by email. Remove the claim that staff manually adds consultations.

- [ ] **Step 5: Rewrite Part 2 section 03.** Rename to Consultation Requests That Flow Into Members. Explain one venue/day, minimal player fields, optional phone/Instagram, Turnstile+OTP, pending booking, and Coach Gino's personal exact-time confirmation. Use consultation-request.png. Remove both-parties confirmation and Google Calendar claims.

- [ ] **Step 6: Rewrite Part 2 section 05 with an evidence grid.** Rename to GV Members & Branded Workflow Emails. Show members-login.png, members-requests.png, and all three email screenshots. Explain one parent receipt, Coach Gino's numbered email, portal update after approval, and no automatic final parent email.

- [ ] **Step 7: Update Tested & Live.** Include CTA wizard, pending order-linked booking, one parent plus one coach email, secure exact-time approval, no final auto-email, any-email OTP, ownership-safe timeline, redirects, and no-cache headers.

- [ ] **Step 8: Render and inspect the entire report.** Use headless Chrome/Playwright at 1440px. Every image must resolve, captions match, layout has no overlap/clipping, and no obsolete calendar/manual-entry claim remains.

- [ ] **Step 9: Commit report evidence separately.**

~~~bash
git add build/scripts/render-member-report-emails.php docs/CLIENT-REPORT-JULY.html docs/screenshots/consultation-request.png docs/screenshots/members-login.png docs/screenshots/members-requests.png docs/screenshots/coach-finalize-consultation.png docs/screenshots/member-otp-email.png docs/screenshots/consultation-parent-receipt-email.png docs/screenshots/consultation-coach-email.png
git commit -m "docs: show final members and consultation workflow in July report"
~~~

### Task 14: Wiki synchronization, final verification, and handoff

**Files:**
- Modify: wiki/architecture.md
- Modify: wiki/booking-latepoint.md
- Modify: wiki/forms-and-emails.md
- Modify: wiki/pages.md
- Modify: wiki/deployment-workflows.md
- Modify: wiki/client-status.md
- Modify: wiki/log.md

- [ ] **Step 1: Synchronize concepts.** Document module boundaries, page 2983 at /members/, redirects, any-email OTP creation, booking meta, 45/180 distinction, pending-to-approved mapping, token finalization, one-parent-email rule, cache exclusions, targeted scripts, and rollback.

- [ ] **Step 2: Append completion log.** Record production outcome, files, settings, tests, screenshots, July report changes, and acceptance evidence using AGENTS.md format.

- [ ] **Step 3: Run fresh verification.**

~~~bash
for test in build/mu-plugins/tests/test-*.php; do php "$test" || exit 1; done
git diff --check
git status --short
ssh gvweb "cd /home/u907133977/domains/gvbasketball.com/public_html && wp db query 'SELECT name,duration,timeblock_interval,override_default_booking_status FROM wp_latepoint_services WHERE name=0x506c6179657220436f6e73756c746174696f6e;' && wp post get 2983 --fields=post_title,post_name,post_status --format=json"
~~~

Expected: all tests pass; service is 45/180/pending; page 2983 is published at members.

- [ ] **Step 4: Final browser smoke.** Verify /, /training-programs/, /book-a-consultation/, /members/, /booking/, and /customer-cabinet/ after cache purge, with no console/PHP errors and no old modal.

- [ ] **Step 5: Commit wiki.**

~~~bash
git add wiki/architecture.md wiki/booking-latepoint.md wiki/forms-and-emails.md wiki/pages.md wiki/deployment-workflows.md wiki/client-status.md wiki/log.md
git commit -m "docs: document GV Members operations and rollback"
~~~

- [ ] **Step 6: Review and finish.** Invoke superpowers:requesting-code-review, address verified findings with superpowers:receiving-code-review, rerun Step 3, then use superpowers:finishing-a-development-branch to offer merge/PR choices.

## Rollback sequence

1. Restore prior MU-plugin files and theme/page sources from backup/previous commit.
2. Restore page 2983 title, slug, and Elementor data; restore header 3002 and footer 2991 Elementor data.
3. Restore only backed-up Player Consultation and affected setting rows. Never truncate and never overwrite customer/booking tables containing new real records.
4. Purge Elementor, LiteSpeed, and Cloudflare.
5. Verify the old /booking/ flow and confirm all new real bookings remain.

## Definition of done

- An unknown email can OTP-sign up at /members/, gets one LatePoint customer, and can log back in without duplication.
- Every consultation CTA opens the themed native wizard with Player Consultation preselected.
- The wizard shows one day-request action, collects only approved fields, and creates one pending order-linked 45-minute booking after Turnstile and OTP.
- Parent gets one initial receipt; Coach Gino gets one six-step operations email; no native duplicate or final automatic parent email is sent.
- Coach Gino can select a genuinely available 45-minute time and approve once; GET and repeats do not mutate.
- Members see all owned Submitted/Confirmed requests, confirmed sessions, player reuse, profile, and email-for-changes help without payment/reschedule controls.
- Old URLs redirect, private responses do not cache, and cross-customer access is impossible.
- The July report accurately reflects the final flow and includes current live UI plus OTP, parent-receipt, and coach-email screenshots.
- Automated tests, PHP lint, production acceptance, accessibility checks, wiki synchronization, and review pass.
