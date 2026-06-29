# Request Training Form — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the LatePoint calendar booking on `/book-a-consultation/` (post 2982) with a simple, branded "Request Training" form that emails `info@gvbasketball.com` and auto-replies to the submitter, protected by Cloudflare Turnstile.

**Architecture:** A new must-use plugin `gv-request-form.php` registers a `[gv_request_form]` shortcode (branded form + Turnstile widget + honeypot + nonce) and an `admin-post.php` handler (validate → verify Turnstile → send two branded HTML emails → Post-Redirect-Get). Page 2982's Elementor block swaps `[latepoint_book_form]` for the shortcode. CTA labels site-wide change from "Book a Consultation" to "Request Training" (URL slug unchanged). LatePoint stays installed for the member portal (2983).

**Tech Stack:** WordPress (PHP 8.2) + Elementor + must-use plugins, `wp_mail` via FluentSMTP/Gmail, Cloudflare Turnstile, deploy over SSH with WP-CLI.

## Global Constraints

- This is a deployed WordPress site; there is **no local PHP test harness**. Per-task verification = `php -l` lint locally + a server/`wp eval`/`curl`/browser check after deploy.
- Edit source in `build/`, deploy via `scp` + a `gv_*` helper over SSH, then `wp elementor flush-css && wp litespeed-purge all`. SSH host alias: `gvweb`. WP root: `/home/u907133977/domains/gvbasketball.com/public_html`.
- Filter the harmless SSH stderr noise: `2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"`.
- **Never print secret values.** Turnstile secret + Cloudflare token stay out of stdout and out of git. Set server constants with `wp config set … --quiet`. `.env` is gitignored.
- Brand tokens (verbatim): Navy `#123B78` · Deep Navy `#021F51` · Orange `#F47B20` · Charcoal `#1C1C1E` · Steel `#6B6F76` · Light `#E6E7E9`. Fonts: Bebas Neue / Montserrat / Inter. Logo: `https://gvbasketball.com/wp-content/uploads/2025/07/GV_Logo_Main.png`.
- Email recipient: `info@gvbasketball.com`. Instagram DM: `https://ig.me/m/gvbasketballl`, handle `@gvbasketballl`. No pricing shown publicly. No phone/WhatsApp/Facebook re-added.
- Training type options (verbatim, exactly three): `Private Training`, `Small Group`, `Elite Performance`.
- CTA label: `Request Training`. Page slug stays `/book-a-consultation/`.
- Work on branch `feature/request-training-form`. `.env` already holds `CLOUDFLARE_ACCOUNT_ID` + `CLOUDFLARE_API_TOKEN` (verified 2026-06-29: token has Turnstile scope; 0 widgets exist).

---

### Task 1: Provision Cloudflare Turnstile widget + store keys

**Files:**
- Create: `build/scripts/setup-turnstile.sh`
- Modify: `.env` (append the two keys — gitignored)
- Server: set wp-config constants `GV_TURNSTILE_SITEKEY` / `GV_TURNSTILE_SECRET`

**Interfaces:**
- Produces: PHP constants `GV_TURNSTILE_SITEKEY` (string) and `GV_TURNSTILE_SECRET` (string), available to `gv-request-form.php` on the server.

- [ ] **Step 1: Write the provisioning script**

Create `build/scripts/setup-turnstile.sh`:

```bash
#!/usr/bin/env bash
# Provision a Cloudflare Turnstile (managed) widget for gvbasketball.com and
# store the keys: into local .env and as wp-config constants on the server.
# Never prints the secret. Idempotent: reuses an existing widget for the domain.
set -euo pipefail
cd "$(dirname "$0")/../.."            # repo root
set -a; . ./.env; set +a

API="https://api.cloudflare.com/client/v4/accounts/${CLOUDFLARE_ACCOUNT_ID}/challenges/widgets"
AUTH=(-H "Authorization: Bearer ${CLOUDFLARE_API_TOKEN}" -H "Content-Type: application/json")

# Reuse an existing widget for the domain if present, else create one.
EXISTING=$(curl -s "${API}" "${AUTH[@]}" \
  | python3 -c "import sys,json;[print(w['sitekey']) for w in (json.load(sys.stdin).get('result') or []) if 'gvbasketball.com' in (w.get('domains') or [])]" | head -n1)

if [ -n "${EXISTING}" ]; then
  SITEKEY="${EXISTING}"
  SECRET=$(curl -s -X POST "${API}/${SITEKEY}/rotate_secret" "${AUTH[@]}" -d '{}' \
    | python3 -c "import sys,json;print(json.load(sys.stdin)['result']['secret'])")
else
  RESP=$(curl -s -X POST "${API}" "${AUTH[@]}" \
    -d '{"name":"GV Basketball — Request Training","domains":["gvbasketball.com"],"mode":"managed"}')
  SITEKEY=$(echo "${RESP}" | python3 -c "import sys,json;print(json.load(sys.stdin)['result']['sitekey'])")
  SECRET=$(echo "${RESP}"  | python3 -c "import sys,json;print(json.load(sys.stdin)['result']['secret'])")
fi

# Persist into local .env (replace if already present).
grep -v '^GV_TURNSTILE_' .env > .env.tmp || true
{ echo "GV_TURNSTILE_SITEKEY=${SITEKEY}"; echo "GV_TURNSTILE_SECRET=${SECRET}"; } >> .env.tmp
mv .env.tmp .env

# Set on server as wp-config constants (quiet = no echo of values).
WPROOT="/home/u907133977/domains/gvbasketball.com/public_html"
ssh gvweb "cd ${WPROOT} && wp config set GV_TURNSTILE_SITEKEY '${SITEKEY}' --quiet && wp config set GV_TURNSTILE_SECRET '${SECRET}' --quiet" \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable" || true

echo "sitekey=${SITEKEY}"
echo "secret stored (not shown)"
```

- [ ] **Step 2: Run it**

```bash
chmod +x build/scripts/setup-turnstile.sh && ./build/scripts/setup-turnstile.sh
```
Expected: prints `sitekey=0x4AAA…` and `secret stored (not shown)`; no secret in output.

- [ ] **Step 3: Verify constants are set on the server**

```bash
ssh gvweb "cd /home/u907133977/domains/gvbasketball.com/public_html && wp eval 'echo (defined(\"GV_TURNSTILE_SITEKEY\")?\"sitekey_len=\".strlen(GV_TURNSTILE_SITEKEY):\"NO_SITEKEY\").\" \".(defined(\"GV_TURNSTILE_SECRET\")?\"secret_len=\".strlen(GV_TURNSTILE_SECRET):\"NO_SECRET\").\"\\n\";'" 2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: `sitekey_len=… secret_len=…` (both non-zero). Secret length only, never the value.

- [ ] **Step 4: Commit** (the script only — `.env` is gitignored)

```bash
git add build/scripts/setup-turnstile.sh
git commit -m "feat: provision Cloudflare Turnstile widget for request form"
```

---

### Task 2: Create the `gv-request-form.php` mu-plugin

**Files:**
- Create: `build/mu-plugins/gv-request-form.php`

**Interfaces:**
- Consumes: `GV_TURNSTILE_SITEKEY`, `GV_TURNSTILE_SECRET` (Task 1).
- Produces: shortcode `[gv_request_form]`; actions `admin_post_nopriv_gv_request_form` + `admin_post_gv_request_form` → `gv_rf_handle()`; helper `gv_rf_types()` returning `['Private Training','Small Group','Elite Performance']`. POST fields: `parent_name, player_name, player_age, email, contact_alt, training_type, preferred_times, gv_rf_nonce, gv_website (honeypot), cf-turnstile-response`. Redirect param `?gv_request=ok|err|spam`.

- [ ] **Step 1: Write the full plugin file**

Create `build/mu-plugins/gv-request-form.php` with exactly this content:

```php
<?php
/*
Plugin Name: GV Basketball — Request Training Form
Description: Branded training-request form ([gv_request_form]) with Cloudflare Turnstile, honeypot + nonce, and two branded HTML emails (admin notification + submitter auto-reply). Replaces the public LatePoint booking form on /book-a-consultation/.
Version: 1.0
*/
if (!defined('ABSPATH')) exit;

if (!defined('GV_RF_RECIPIENT')) define('GV_RF_RECIPIENT', 'info@gvbasketball.com');

function gv_rf_types() {
    return array('Private Training', 'Small Group', 'Elite Performance');
}

/* ---------------- Branded email shell (mirrors gv-otp-email.php) ---------------- */
function gv_rf_email_shell($heading, $intro, $inner) {
    $logo   = 'https://gvbasketball.com/wp-content/uploads/2025/07/GV_Logo_Main.png';
    $navy   = '#123B78'; $orange = '#F47B20'; $char = '#1C1C1E'; $steel = '#6B6F76';
    $ig     = 'https://instagram.com/gvbasketballl';
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f5f7;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 12px;">
<tr><td align="center">
  <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="max-width:520px;width:100%;background:#ffffff;border:1px solid #E6E7E9;border-radius:14px;overflow:hidden;">
    <tr><td style="border-top:4px solid {$orange};"></td></tr>
    <tr><td align="center" style="padding:30px 32px 6px;">
      <img src="{$logo}" width="64" height="64" alt="GV Basketball" style="display:block;width:64px;height:auto;">
      <div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;letter-spacing:3px;color:{$char};margin-top:12px;">GV BASKETBALL</div>
    </td></tr>
    <tr><td align="center" style="padding:12px 36px 0;">
      <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:800;color:{$navy};">{$heading}</h1>
      <p style="margin:10px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:{$steel};">{$intro}</p>
    </td></tr>
    <tr><td style="padding:22px 36px 0;">{$inner}</td></tr>
    <tr><td style="padding:24px 36px 0;"><hr style="border:none;border-top:1px solid #E6E7E9;margin:0;"></td></tr>
    <tr><td align="center" style="padding:16px 36px 30px;">
      <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:{$steel};">
        GV Basketball &middot; Makati &amp; Ortigas, Metro Manila &middot; <a href="{$ig}" style="color:{$navy};text-decoration:none;">@gvbasketballl</a>
      </p>
    </td></tr>
  </table>
</td></tr>
</table>
</body></html>
HTML;
}

/* ---------------- Turnstile server-side verification ---------------- */
function gv_rf_verify_turnstile($token, $ip) {
    if (!defined('GV_TURNSTILE_SECRET') || !GV_TURNSTILE_SECRET) return true; // not configured: don't lock out
    if (empty($token)) return false;
    $resp = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
        'timeout' => 10,
        'body'    => array('secret' => GV_TURNSTILE_SECRET, 'response' => $token, 'remoteip' => $ip),
    ));
    if (is_wp_error($resp)) return false;
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    return !empty($data['success']);
}

/* ---------------- Submission handler ---------------- */
function gv_rf_handle() {
    $ref = wp_get_referer();
    if (!$ref) $ref = home_url('/book-a-consultation/');
    $back = function ($status) use ($ref) {
        $url = add_query_arg('gv_request', $status, remove_query_arg('gv_request', $ref)) . '#gv-request-form';
        wp_safe_redirect($url);
        exit;
    };

    if (!isset($_POST['gv_rf_nonce']) || !wp_verify_nonce($_POST['gv_rf_nonce'], 'gv_request_form')) $back('err');
    if (!empty($_POST['gv_website'])) $back('ok'); // honeypot tripped -> fake success

    $token = isset($_POST['cf-turnstile-response']) ? sanitize_text_field(wp_unslash($_POST['cf-turnstile-response'])) : '';
    if (!gv_rf_verify_turnstile($token, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')) $back('spam');

    $parent = sanitize_text_field(wp_unslash($_POST['parent_name'] ?? ''));
    $player = sanitize_text_field(wp_unslash($_POST['player_name'] ?? ''));
    $age    = intval($_POST['player_age'] ?? 0);
    $email  = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $alt    = sanitize_text_field(wp_unslash($_POST['contact_alt'] ?? ''));
    $type   = sanitize_text_field(wp_unslash($_POST['training_type'] ?? ''));
    $times  = sanitize_textarea_field(wp_unslash($_POST['preferred_times'] ?? ''));

    if (!$parent || !$player || !is_email($email) || $age < 4 || $age > 25 || !in_array($type, gv_rf_types(), true) || !$times) $back('err');

    // ---- Admin notification ----
    $rows = array(
        'Parent / Guardian'      => esc_html($parent),
        'Player'                 => esc_html($player),
        'Player age'             => esc_html((string) $age),
        'Email'                  => esc_html($email),
        'Phone / Instagram'      => $alt ? esc_html($alt) : '&mdash;',
        'Training type'          => esc_html($type),
        'Preferred days &amp; times' => nl2br(esc_html($times)),
    );
    $tbl = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1C1C1E;border-collapse:collapse;">';
    foreach ($rows as $k => $v) {
        $tbl .= '<tr>'
              . '<td style="padding:9px 12px;background:#f0f4fa;border:1px solid #E6E7E9;font-weight:700;color:#123B78;width:42%;vertical-align:top;">' . $k . '</td>'
              . '<td style="padding:9px 12px;border:1px solid #E6E7E9;vertical-align:top;">' . $v . '</td>'
              . '</tr>';
    }
    $tbl .= '</table>';
    $admin_html = gv_rf_email_shell('New training request', 'A new request came in from the website.', $tbl);
    $admin_headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $parent . ' <' . $email . '>',
    );
    wp_mail(GV_RF_RECIPIENT, 'New training request — ' . $player, $admin_html, $admin_headers);

    // ---- Auto-reply to submitter ----
    $reply_inner =
        '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1C1C1E;">'
        . 'Thanks for reaching out about <strong>' . esc_html($player) . '</strong>! We\'ve received your request for '
        . '<strong>' . esc_html($type) . '</strong> and Coach Gino\'s team will get back to you to confirm days, times, and the best-fit plan.</p>'
        . '<p style="margin:14px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1C1C1E;">'
        . 'Your preferred times: <em>' . nl2br(esc_html($times)) . '</em></p>'
        . '<p style="margin:18px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#6B6F76;">'
        . 'Want to chat sooner? Message us on Instagram: '
        . '<a href="https://ig.me/m/gvbasketballl" style="color:#123B78;">@gvbasketballl</a>.</p>';
    $reply_html = gv_rf_email_shell('We got your request', 'Welcome to GV Basketball, ' . esc_html($parent) . '.', $reply_inner);
    wp_mail($email, 'We got your request — GV Basketball', $reply_html, array('Content-Type: text/html; charset=UTF-8'));

    $back('ok');
}
add_action('admin_post_nopriv_gv_request_form', 'gv_rf_handle');
add_action('admin_post_gv_request_form', 'gv_rf_handle');

/* ---------------- Scoped styles (emitted once) ---------------- */
function gv_rf_styles() {
    static $done = false;
    if ($done) return '';
    $done = true;
    return '<style>
    .gv-rform-wrap{max-width:680px;margin:0 auto;}
    .gv-rform-note{padding:14px 18px;border-radius:10px;margin-bottom:22px;font:600 15px/1.5 Montserrat,Arial,sans-serif;}
    .gv-rform-note--ok{background:#e8f5ec;border:1px solid #b8e0c4;color:#1d6b3a;}
    .gv-rform-note--err{background:#fdecea;border:1px solid #f5c2bb;color:#a3271a;}
    .gv-rform-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
    .gv-rform-field{display:flex;flex-direction:column;gap:7px;}
    .gv-rform-field--full{grid-column:1/-1;}
    .gv-rform-field>span{font:700 13px/1.2 Montserrat,Arial,sans-serif;letter-spacing:.02em;color:#123B78;text-transform:uppercase;}
    .gv-rform-field i{color:#F47B20;font-style:normal;}
    .gv-rform-field small{color:#6B6F76;font-weight:600;text-transform:none;letter-spacing:0;}
    .gv-rform-field input,.gv-rform-field select,.gv-rform-field textarea{
      width:100%;padding:13px 14px;border:1px solid #d6d9de;border-radius:10px;
      font:400 15px/1.4 Inter,Arial,sans-serif;color:#1C1C1E;background:#fff;box-sizing:border-box;}
    .gv-rform-field input:focus,.gv-rform-field select:focus,.gv-rform-field textarea:focus{
      outline:none;border-color:#F47B20;box-shadow:0 0 0 3px rgba(244,123,32,.15);}
    .gv-rform-field textarea{resize:vertical;}
    .gv-rform .cf-turnstile{margin:22px 0 4px;}
    .gv-rform-submit{margin-top:22px;width:100%;justify-content:center;}
    .gv-rform-fine{margin:14px 0 0;font:400 12.5px/1.5 Inter,Arial,sans-serif;color:#6B6F76;text-align:center;}
    .gv-rform-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;}
    @media(max-width:560px){.gv-rform-grid{grid-template-columns:1fr;}}
    </style>';
}

/* ---------------- Shortcode: the branded form ---------------- */
function gv_rf_shortcode() {
    $sitekey = defined('GV_TURNSTILE_SITEKEY') ? GV_TURNSTILE_SITEKEY : '';
    $action  = esc_url(admin_url('admin-post.php'));
    $nonce   = wp_create_nonce('gv_request_form');
    $status  = isset($_GET['gv_request']) ? sanitize_key($_GET['gv_request']) : '';

    $banner = '';
    if ($status === 'ok') {
        $banner = '<div class="gv-rform-note gv-rform-note--ok">Thanks! Your request is in — we\'ll be in touch shortly. Check your inbox for a confirmation.</div>';
    } elseif ($status === 'spam') {
        $banner = '<div class="gv-rform-note gv-rform-note--err">We couldn\'t verify you weren\'t a robot. Please try again.</div>';
    } elseif ($status === 'err') {
        $banner = '<div class="gv-rform-note gv-rform-note--err">Please check your entries and try again — all required fields are needed.</div>';
    }

    $opts = '<option value="" disabled selected>Choose a program…</option>';
    foreach (gv_rf_types() as $t) {
        $opts .= '<option value="' . esc_attr($t) . '">' . esc_html($t) . '</option>';
    }

    $ts_script = $sitekey ? '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>' : '';
    $ts_widget = $sitekey ? '<div class="cf-turnstile" data-sitekey="' . esc_attr($sitekey) . '" data-theme="light"></div>' : '';

    $html  = gv_rf_styles();
    $html .= '<div class="gv-rform-wrap" id="gv-request-form">' . $banner;
    $html .= '<form class="gv-rform" method="post" action="' . $action . '" novalidate>';
    $html .= '<input type="hidden" name="action" value="gv_request_form">';
    $html .= '<input type="hidden" name="gv_rf_nonce" value="' . esc_attr($nonce) . '">';
    $html .= '<div class="gv-rform-hp" aria-hidden="true"><label>Website<input type="text" name="gv_website" tabindex="-1" autocomplete="off"></label></div>';
    $html .= '<div class="gv-rform-grid">';
    $html .= '<label class="gv-rform-field"><span>Your name <i>*</i></span><input type="text" name="parent_name" required autocomplete="name"></label>';
    $html .= '<label class="gv-rform-field"><span>Player name <i>*</i></span><input type="text" name="player_name" required></label>';
    $html .= '<label class="gv-rform-field"><span>Player age <i>*</i></span><input type="number" name="player_age" min="4" max="25" required></label>';
    $html .= '<label class="gv-rform-field"><span>Email <i>*</i></span><input type="email" name="email" required autocomplete="email"></label>';
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Phone or Instagram handle <small>(optional)</small></span><input type="text" name="contact_alt"></label>';
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Training type <i>*</i></span><select name="training_type" required>' . $opts . '</select></label>';
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Preferred days &amp; times to meet <i>*</i></span><textarea name="preferred_times" rows="3" placeholder="e.g. Weekday afternoons after 4pm, or Saturday mornings" required></textarea></label>';
    $html .= '</div>';
    $html .= $ts_widget;
    $html .= '<button type="submit" class="gv-btn gv-btn--primary gv-rform-submit">Send Request</button>';
    $html .= '<p class="gv-rform-fine">We\'ll only use your details to follow up about training. Pricing is shared during your consultation.</p>';
    $html .= '</form></div>' . $ts_script;
    return $html;
}
add_shortcode('gv_request_form', 'gv_rf_shortcode');
```

- [ ] **Step 2: Lint locally**

```bash
php -l build/mu-plugins/gv-request-form.php
```
Expected: `No syntax errors detected in build/mu-plugins/gv-request-form.php`. (If `php` is unavailable locally, run it after deploy via `ssh gvweb 'php -l <remote path>'`.)

- [ ] **Step 3: Deploy the mu-plugin**

```bash
DEST=/home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins
scp build/mu-plugins/gv-request-form.php gvweb:$DEST/gv-request-form.php
```

- [ ] **Step 4: Verify the shortcode registers and renders the form + Turnstile**

```bash
ssh gvweb "cd /home/u907133977/domains/gvbasketball.com/public_html && wp eval 'echo (shortcode_exists(\"gv_request_form\")?\"OK \":\"MISSING \"); \$h=do_shortcode(\"[gv_request_form]\"); echo (strpos(\$h,\"cf-turnstile\")!==false?\"turnstile_ok\":\"no_turnstile\").\" \".(strpos(\$h,\"name=\\\"training_type\\\"\")!==false?\"select_ok\":\"no_select\").\"\\n\";'" 2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: `OK turnstile_ok select_ok`.

- [ ] **Step 5: Commit**

```bash
git add build/mu-plugins/gv-request-form.php
git commit -m "feat: branded Request Training form mu-plugin (Turnstile + 2 emails)"
```

---

### Task 3: Swap the booking page (2982) to the new form + reframe its copy

**Files:**
- Modify: `build/scripts/build-functional.php` (the page-2982 section, lines ~54–95, plus the shared `$cta` band button at line 9)

**Interfaces:**
- Consumes: shortcode `[gv_request_form]` (Task 2); helper `gv_set_page_blocks` (existing).

- [ ] **Step 1: Reframe the page-2982 intro block and shared CTA**

In `build/scripts/build-functional.php`:

In the shared `$cta` band, change the primary button label (keep href):
```php
<a class="gv-btn gv-btn--primary" href="/book-a-consultation/">Request Training</a>
```

In `$book_a`, change the hero eyebrow, and replace the "Pick a Date & Time" sub-block. Replace:
```php
<span class="gv-eyebrow">Book a Consultation</span>
<h1 class="gv-h1">Start Your Player's Journey</h1>
```
with:
```php
<span class="gv-eyebrow">Request Training</span>
<h1 class="gv-h1">Start Your Player's Journey</h1>
```
And replace the final head-block in `$book_a`:
```php
<div class="gv-head-block gv-center" style="margin-top:54px;margin-bottom:0;"><span class="gv-eyebrow">Reserve Your Slot</span><h2 class="gv-section-title">Pick a Date &amp; Time</h2><p class="gv-lead">Choose a slot below. You'll receive a confirmation by email — pricing is shared during the consultation.</p></div>
```
with:
```php
<div class="gv-head-block gv-center" style="margin-top:54px;margin-bottom:0;"><span class="gv-eyebrow">Tell Us About Your Player</span><h2 class="gv-section-title">Request Training</h2><p class="gv-lead">Send us a few details and your preferred days and times. Coach Gino's team will follow up to confirm — pricing is shared during your consultation.</p></div>
```

- [ ] **Step 2: Swap the form block**

In the `gv_set_page_blocks(2982, …)` call, replace:
```php
  array('type'=>'shortcode','content'=>'[latepoint_book_form]','css'=>'gv-bookform-wrap'),
```
with:
```php
  array('type'=>'shortcode','content'=>'[gv_request_form]','css'=>'gv-bookform-wrap'),
```

- [ ] **Step 3: Lint**

```bash
php -l build/scripts/build-functional.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 4: Deploy + rebuild the page**

```bash
scp build/scripts/build-functional.php gvweb:~/build-functional.php
ssh gvweb "cd /home/u907133977/domains/gvbasketball.com/public_html && wp eval-file ~/build-functional.php && wp elementor flush-css && wp litespeed-purge all && rm ~/build-functional.php" 2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: prints page IDs (incl. `2982`) with no PHP errors.

- [ ] **Step 5: Verify the page now serves the form and not LatePoint**

```bash
curl -s "https://gvbasketball.com/book-a-consultation/?cache=$RANDOM" | grep -o "gv-rform\|cf-turnstile\|latepoint_book_form\|name=\"training_type\"" | sort -u
```
Expected: shows `gv-rform`, `cf-turnstile`, `name="training_type"`; does **not** show `latepoint_book_form`.

- [ ] **Step 6: Commit**

```bash
git add build/scripts/build-functional.php
git commit -m "feat: booking page 2982 uses Request Training form, reframed copy"
```

---

### Task 4: Reframe CTA labels site-wide (slug unchanged)

**Files:**
- Modify: `build/templates/header.html:13`, `build/templates/footer.html:40`
- Modify: `build/scripts/build-menu.php:25`
- Modify: `build/scripts/build-functional.php` (portal CTA, line ~106 — `Book a Consultation` → `Request Training`)
- Modify pages: `build/pages/home.html`, `about.html`, `athlete-development.html`, `gallery.html`, `training-programs.html`, `success-stories.html`, `testimonials.html`, `faq.html`
- Modify: `build/scripts/ensure-pages.php:5` (page title) — optional title rename

**Interfaces:** none (copy-only; all `href` values stay `/book-a-consultation/`).

- [ ] **Step 1: Swap button label text in all page/template files**

Replace the visible CTA text only (not hrefs, not narrative prose). Both label variants map to `Request Training`:

```bash
# Button labels: ">Book a Consultation<" and ">Book Consultation<"
grep -rl ">Book a Consultation<\|>Book Consultation<" build/pages build/templates \
  | xargs sed -i '' -e 's/>Book a Consultation</>Request Training</g' -e 's/>Book Consultation</>Request Training</g'
```
(On Linux/GNU sed use `sed -i` without the `''`.)

- [ ] **Step 2: Update the menu label, portal CTA, and the journey-step title**

In `build/scripts/build-menu.php:25` change the label argument `'Book a Consultation'` → `'Request Training'` (keep the `/book-a-consultation/` path).

In `build/scripts/build-functional.php` portal block (`$port_a`), the button `>Book a Consultation<` → `>Request Training<` (Step 1's sed does not touch `.php` files — edit it explicitly here).

In `build/pages/training-programs.html:192`, the journey step title `<h3 class="gv-step__title">Book a Consultation</h3>` becomes `<h3 class="gv-step__title">Request Training</h3>` (covered by Step 1's sed since it's `>Book a Consultation<`).

In `build/pages/faq.html:22`, update the prose page-name reference: `book it online from the "Book a Consultation" page` → `send a request from the "Request Training" page`.

- [ ] **Step 3: (Optional) Rename the WP page title, keep slug**

In `build/scripts/ensure-pages.php:5`, `'book-a-consultation' => 'Book a Consultation'` → `'book-a-consultation' => 'Request Training'`. (Slug key unchanged.)

- [ ] **Step 4: Verify no stray button labels remain**

```bash
grep -rn ">Book a Consultation<\|>Book Consultation<" build/ || echo "CLEAN"
```
Expected: `CLEAN`.

- [ ] **Step 5: Lint changed PHP**

```bash
php -l build/scripts/build-menu.php && php -l build/scripts/build-functional.php && php -l build/scripts/ensure-pages.php
```
Expected: all `No syntax errors detected`.

- [ ] **Step 6: Deploy header + footer + menu + all marketing pages**

```bash
HOME_R=/home/u907133977/domains/gvbasketball.com/public_html
# stage files
scp build/templates/header.html build/templates/footer.html gvweb:~/
scp build/pages/*.html gvweb:~/pages/ 2>/dev/null || (ssh gvweb 'mkdir -p ~/pages' && scp build/pages/*.html gvweb:~/pages/)
scp build/scripts/deploy-refine.php build/scripts/build-extras.php build/scripts/build-menu.php build/scripts/build-functional.php gvweb:~/
# apply: header + all marketing pages (deploy-refine), footer (build-extras), menu, portal/contact (build-functional)
ssh gvweb "cd $HOME_R && wp eval-file ~/deploy-refine.php && wp eval-file ~/build-extras.php && wp eval-file ~/build-menu.php && wp eval-file ~/build-functional.php && wp elementor flush-css && wp litespeed-purge all" 2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: each script prints its IDs with no errors. (`build-extras.php`/`deploy-refine.php` read `~/footer.html`/`~/header.html`/`~/pages/*` already staged.)

- [ ] **Step 7: Verify labels live**

```bash
curl -s "https://gvbasketball.com/?cache=$RANDOM" | grep -o "Request Training\|Book a Consultation" | sort | uniq -c
```
Expected: `Request Training` present; `Book a Consultation` absent (or only inside hrefs, which grep above won't catch since it matches visible text).

- [ ] **Step 8: Commit**

```bash
git add build/templates/header.html build/templates/footer.html build/scripts/build-menu.php build/scripts/build-functional.php build/scripts/ensure-pages.php build/pages/*.html
git commit -m "feat: reframe CTAs to 'Request Training' site-wide (slug unchanged)"
```

---

### Task 5: Auto-verify with screenshots + email render

**Files:**
- Create (temp, scratchpad): rendered email HTML for screenshotting
- No source changes (verification only)

**Interfaces:** Consumes the deployed site from Tasks 1–4.

- [ ] **Step 1: Screenshot the live form (desktop + mobile)**

Use the `browse` skill (or `claude-in-chrome`) to load `https://gvbasketball.com/book-a-consultation/`:
- Desktop screenshot (1440px) of the full form.
- Mobile screenshot (390px) — confirm the grid collapses to one column.
- Confirm the Turnstile widget is visible.

- [ ] **Step 2: Submit a real test request and screenshot the success banner**

Fill the form with test data (`parent_name=Test Parent`, `player_name=Test Player`, `age=12`, `email=techteam@favor.church`, type=`Private Training`, times=`Weekday afternoons`), solve Turnstile, submit. Screenshot the `?gv_request=ok` success banner.

- [ ] **Step 3: Confirm both emails were sent**

```bash
ssh gvweb "cd /home/u907133977/domains/gvbasketball.com/public_html && wp eval 'var_dump(wp_mail(\"techteam@favor.church\",\"gv-rf selftest\",\"ok\"));'" 2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Then confirm in the `info@gvbasketball.com` / `techteam@favor.church` inboxes that the branded **New training request** and **We got your request** emails arrived (from the Step 2 submission). (FluentSMTP log can also be checked in wp-admin if needed.)

- [ ] **Step 4: Screenshot the branded emails**

Render both email bodies to local HTML and screenshot them so the branding is visible without inbox access:
```bash
ssh gvweb "cd /home/u907133977/domains/gvbasketball.com/public_html && wp eval '
require_once WPMU_PLUGIN_DIR.\"/gv-request-form.php\";
\$t=\"<table style=\\\"width:100%\\\"><tr><td>Player</td><td>Test Player</td></tr></table>\";
echo gv_rf_email_shell(\"New training request\",\"A new request came in from the website.\",\$t);
'" 2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable" > /private/tmp/claude-501/-Users-rico-Git-gvbasketball/0d334684-14d1-441d-89fb-50459cb6c887/scratchpad/email-admin.html
```
Open the saved HTML in the browser and screenshot. (Repeat with the auto-reply heading/intro if desired.)

- [ ] **Step 5: Verify Turnstile actually blocks a tokenless POST**

```bash
curl -s -i -X POST "https://gvbasketball.com/wp-admin/admin-post.php" \
  --data "action=gv_request_form&parent_name=Bot&player_name=Bot&player_age=12&email=bot@example.com&training_type=Private%20Training&preferred_times=now" \
  | grep -i "location:"
```
Expected: a redirect `Location:` ending in `gv_request=err` or `gv_request=spam` (no email sent — nonce missing → `err`, which already proves the unauthenticated path is rejected; with a valid nonce but no token it would be `spam`).

- [ ] **Step 6: Confirm member portal still works (LatePoint untouched)**

```bash
curl -s "https://gvbasketball.com/booking/?cache=$RANDOM" | grep -o "latepoint" | head -1
```
Expected: `latepoint` still present on the portal page.

- [ ] **Step 7: Update logs + commit**

Update `PROJECT_LOG.md` (technical changelog) and `PROGRESS_LOG.md` (client summary) with the form swap. Then:
```bash
git add PROJECT_LOG.md PROGRESS_LOG.md
git commit -m "docs: log Request Training form rollout"
```

---

## Self-Review

**Spec coverage:**
- Fields (parent name, player name, age, email, phone/IG, type×3, days/times) → Task 2 shortcode. ✅
- No date/time picker → Task 3 removes `[latepoint_book_form]`. ✅
- Branded email to info@ + branded auto-reply → Task 2 `gv_rf_handle`. ✅
- Cloudflare Turnstile (provisioned via API, server-side verify) → Task 1 + Task 2. ✅
- Reframe to "Request Training", slug kept → Task 3 + Task 4. ✅
- LatePoint left intact for portal → not removed; verified in Task 5 Step 6. ✅
- Auto-verify with screenshots → Task 5. ✅

**Placeholder scan:** No TBD/TODO; all steps carry concrete code/commands. ✅

**Type consistency:** `gv_rf_types()`, `gv_rf_email_shell()`, `gv_rf_verify_turnstile()`, `gv_rf_handle()`, shortcode `gv_request_form`, POST field names, and redirect param `gv_request=ok|err|spam` are used consistently across Tasks 2, 3, 5. ✅

**Notes / minor risks:**
- `sed -i ''` syntax is macOS BSD sed (this host is darwin). Step 4 notes the GNU variant.
- If `php` CLI isn't installed locally, lint via `ssh gvweb 'php -l …'` after scp.
- The honeypot returns a fake `ok` to bots — by design.
