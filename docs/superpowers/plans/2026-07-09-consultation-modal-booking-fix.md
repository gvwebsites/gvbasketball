# Consultation Modal Fix + Location/Day Selection Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restore the empty "Book a Consultation" modal on the live `/training-programs/` page and add a required location dropdown plus a location-filtered day multi-select to the request form.

**Architecture:** The public modal embeds a custom WordPress request form rendered by the `gv-request-form` mu-plugin as a shortcode. We extend the shortcode with a location→days data model (single source of truth in PHP, mirrored to JS for live filtering), add server-side validation, and surface the new fields in both notification emails. The empty-modal bug is a deployment gap (the shortcode block is missing from live page 2981); it is fixed by redeploying that page with both blocks via `deploy-training-programs.php`.

**Tech Stack:** PHP (WordPress mu-plugin), vanilla JS (inline in shortcode), WP-CLI over SSH (`gvweb`), Elementor blocks via `gv_set_page_blocks()`.

## Global Constraints

- Package manager for any JS tooling: `pnpm` (not npm/yarn). (No JS build is needed for this plan.)
- Locations and operating days (exact): Dasma, Makati → Mon, Wed, Thu · Urdaneta Village → Fri, Sun · Corinthian Gardens → Sun · Open to any location → Mon, Tue, Wed, Thu, Fri, Sat, Sun.
- Canonical day tokens & order: `Mon, Tue, Wed, Thu, Fri, Sat, Sun`.
- Location keys (stable values): `dasma`, `urdaneta`, `corinth`, `any`.
- Keep existing anti-abuse intact: nonce, honeypot (`gv_website`), Cloudflare Turnstile.
- Do NOT integrate LatePoint into the public modal; no live calendar; no pricing display.
- Live training-programs page (WP id `2981`) content is owned by `build/scripts/deploy-training-programs.php` only.
- Modal subtitle copy must be third-person and must NOT name "Coach Gino".
- Production deploy is gated on explicit user approval (overwrites page 2981; hard to reverse).

---

### Task 1: Location/day data model + validation (test-first, framework-free)

**Files:**
- Modify: `build/mu-plugins/gv-request-form.php` (add `gv_rf_locations()`, `gv_rf_validate_location_days()`)
- Test: `build/mu-plugins/tests/test-gv-request-form.php` (new; CLI-runnable, stubs WP funcs)

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `gv_rf_locations(): array` — keyed by location key, each value
    `['label'=>string, 'days_label'=>string, 'days'=>string[]]`.
  - `gv_rf_validate_location_days(string $location, array $days): bool` — true
    iff `$location` is a known key, `$days` is non-empty, and every day is in
    that location's allowed set.

- [ ] **Step 1: Write the failing test harness**

Create `build/mu-plugins/tests/test-gv-request-form.php`:

```php
<?php
// Framework-free CLI test. Run: php build/mu-plugins/tests/test-gv-request-form.php
// Stub the WordPress functions the plugin calls at include time.
define('ABSPATH', __DIR__);
function add_action() {}
function add_shortcode() {}
function add_filter() {}

require __DIR__ . '/../gv-request-form.php';

$failures = 0;
function check($label, $cond) {
    global $failures;
    if ($cond) { echo "ok   - $label\n"; }
    else { echo "FAIL - $label\n"; $failures++; }
}

// --- gv_rf_locations() shape ---
$locs = gv_rf_locations();
check('has all four location keys',
    isset($locs['dasma'], $locs['urdaneta'], $locs['corinth'], $locs['any']));
check('dasma days are Mon/Wed/Thu',
    $locs['dasma']['days'] === array('Mon','Wed','Thu'));
check('urdaneta days are Fri/Sun',
    $locs['urdaneta']['days'] === array('Fri','Sun'));
check('corinth days are Sun',
    $locs['corinth']['days'] === array('Sun'));
check('any days are all 7 in order',
    $locs['any']['days'] === array('Mon','Tue','Wed','Thu','Fri','Sat','Sun'));
check('dasma label is human-readable',
    $locs['dasma']['label'] === 'Dasma, Makati');

// --- gv_rf_validate_location_days() ---
check('valid: dasma + [Mon,Wed]',
    gv_rf_validate_location_days('dasma', array('Mon','Wed')) === true);
check('invalid: dasma + [Fri] (Fri not a dasma day)',
    gv_rf_validate_location_days('dasma', array('Fri')) === false);
check('invalid: unknown location',
    gv_rf_validate_location_days('nowhere', array('Mon')) === false);
check('invalid: empty day list',
    gv_rf_validate_location_days('dasma', array()) === false);
check('valid: any + [Tue,Sat] (only valid under any)',
    gv_rf_validate_location_days('any', array('Tue','Sat')) === true);
check('invalid: days not an array',
    gv_rf_validate_location_days('dasma', 'Mon') === false);

echo $failures ? "\n$failures FAILED\n" : "\nALL PASS\n";
exit($failures ? 1 : 0);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php build/mu-plugins/tests/test-gv-request-form.php`
Expected: FAIL — fatal error "Call to undefined function gv_rf_locations()" (functions not defined yet).

- [ ] **Step 3: Add the two functions to the mu-plugin**

In `build/mu-plugins/gv-request-form.php`, immediately AFTER the existing
`gv_rf_types()` function (currently around line 20-22), add:

```php
/* ---------------- Locations & day model (single source of truth) ---------------- */
function gv_rf_locations() {
    return array(
        'dasma'    => array('label' => 'Dasma, Makati',       'days_label' => 'Mon, Wed & Thu', 'days' => array('Mon','Wed','Thu')),
        'urdaneta' => array('label' => 'Urdaneta Village',    'days_label' => 'Fri & Sun',      'days' => array('Fri','Sun')),
        'corinth'  => array('label' => 'Corinthian Gardens',  'days_label' => 'Sun',            'days' => array('Sun')),
        'any'      => array('label' => 'Open to any location','days_label' => '',               'days' => array('Mon','Tue','Wed','Thu','Fri','Sat','Sun')),
    );
}

function gv_rf_validate_location_days($location, $days) {
    $locs = gv_rf_locations();
    if (!is_string($location) || !isset($locs[$location])) return false;
    if (!is_array($days) || count($days) === 0) return false;
    $allowed = $locs[$location]['days'];
    foreach ($days as $d) {
        if (!in_array($d, $allowed, true)) return false;
    }
    return true;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php build/mu-plugins/tests/test-gv-request-form.php`
Expected: `ALL PASS` (exit 0).

- [ ] **Step 5: Commit**

```bash
git add build/mu-plugins/gv-request-form.php build/mu-plugins/tests/test-gv-request-form.php
git commit -m "feat(form): add location/day data model + validation with tests

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: Render location select + conditional day checkboxes in the shortcode

**Files:**
- Modify: `build/mu-plugins/gv-request-form.php` (`gv_rf_styles()`, `gv_rf_shortcode()`)
- Test: `build/mu-plugins/tests/test-gv-request-form.php` (extend)

**Interfaces:**
- Consumes: `gv_rf_locations()` (Task 1).
- Produces: shortcode HTML containing a `select[name="location"]`, seven
  `input[name="preferred_days[]"]` checkboxes wrapped in `label.gv-rform-day`
  with `data-day="<token>"`, a relabeled optional `textarea[name="preferred_times"]`,
  and an inline `<script>` exposing `GV_RF_LOC_DAYS` (JSON of location→days).

- [ ] **Step 1: Extend the test to assert the rendered markup**

Append to `build/mu-plugins/tests/test-gv-request-form.php` BEFORE the final
`echo $failures ...` line. Add WP stubs at the top of the file (just after the
existing `add_filter` stub) so `gv_rf_shortcode()` can run:

```php
// (add near the other stubs at top of file)
function esc_attr($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_html($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_url($s){ return (string)$s; }
function wp_create_nonce($a=''){ return 'testnonce'; }
function admin_url($p=''){ return 'https://example.test/wp-admin/'.$p; }
function sanitize_key($s){ return strtolower(preg_replace('/[^a-z0-9_]/','', (string)$s)); }
function wp_json_encode($d){ return json_encode($d); }
```

```php
// (append before the final summary echo)
$html = gv_rf_shortcode();
check('renders a location select', strpos($html, 'name="location"') !== false);
check('location select has dasma option', strpos($html, 'value="dasma"') !== false);
check('location option shows its days', strpos($html, 'Mon, Wed &amp; Thu') !== false
    || strpos($html, 'Mon, Wed & Thu') !== false);
check('renders day checkboxes', strpos($html, 'name="preferred_days[]"') !== false);
check('day checkbox carries data-day', strpos($html, 'data-day="Mon"') !== false);
check('exposes GV_RF_LOC_DAYS json', strpos($html, 'GV_RF_LOC_DAYS') !== false);
check('time field relabeled to optional note',
    stripos($html, 'time of day') !== false);
```

- [ ] **Step 2: Run test to verify the new assertions fail**

Run: `php build/mu-plugins/tests/test-gv-request-form.php`
Expected: FAIL on the new `location`/`preferred_days[]`/`GV_RF_LOC_DAYS`/time-label checks (Task 1 checks still pass).

- [ ] **Step 3: Add day-field styles**

In `gv_rf_styles()` (the `<style>` string), add these rules BEFORE the closing
`</style>` (just after the existing `.gv-rform-field textarea{resize:vertical;}` line):

```css
    .gv-rform-days{display:flex;flex-wrap:wrap;gap:10px;margin-top:2px;}
    .gv-rform-day{display:inline-flex;align-items:center;gap:7px;padding:9px 14px;border:1px solid #d6d9de;border-radius:999px;font:600 14px/1 Inter,Arial,sans-serif;color:#1C1C1E;cursor:pointer;user-select:none;}
    .gv-rform-day input{width:auto;margin:0;accent-color:#F47B20;}
    .gv-rform-day--hidden{display:none;}
    .gv-rform-dayhint{font:600 13px/1.4 Inter,Arial,sans-serif;color:#6B6F76;margin-top:2px;}
```

- [ ] **Step 4: Build the location select, day checkboxes, and JSON in `gv_rf_shortcode()`**

In `gv_rf_shortcode()`, AFTER the existing `$opts` loop that builds the
training-type `<option>`s (currently ends around line 189), add:

```php
    // Location <option>s
    $locs = gv_rf_locations();
    $loc_opts = '<option value="" disabled selected>Choose a location…</option>';
    foreach ($locs as $key => $info) {
        $label = $info['label'];
        if ($info['days_label'] !== '') $label .= ' — ' . $info['days_label'];
        $loc_opts .= '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
    }

    // All 7 day checkboxes (JS shows/hides per location)
    $all_days = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
    $day_boxes = '';
    foreach ($all_days as $d) {
        $day_boxes .= '<label class="gv-rform-day gv-rform-day--hidden" data-day="' . esc_attr($d) . '">'
                    . '<input type="checkbox" name="preferred_days[]" value="' . esc_attr($d) . '">'
                    . '<span>' . esc_html($d) . '</span></label>';
    }

    // location -> valid days map for the client
    $loc_days_json = wp_json_encode(array_map(function ($i) { return $i['days']; }, $locs));
```

- [ ] **Step 5: Replace the old preferred-times field block with the three new fields**

In `gv_rf_shortcode()`, find the training-type + preferred-times lines
(currently):

```php
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Training type <i>*</i></span><select name="training_type" required>' . $opts . '</select></label>';
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Preferred days &amp; times to meet <i>*</i></span><textarea name="preferred_times" rows="3" placeholder="e.g. Weekday afternoons after 4pm, or Saturday mornings" required></textarea></label>';
```

Replace BOTH lines with:

```php
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Training type <i>*</i></span><select name="training_type" required>' . $opts . '</select></label>';
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Preferred location <i>*</i></span><select name="location" id="gv-rf-location" required>' . $loc_opts . '</select></label>';
    $html .= '<div class="gv-rform-field gv-rform-field--full"><span>Preferred day(s) <i>*</i></span>'
           . '<div class="gv-rform-days" id="gv-rf-days">' . $day_boxes . '</div>'
           . '<p class="gv-rform-dayhint" id="gv-rf-dayhint">Choose a location to see available days.</p></div>';
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Preferred time of day / notes <small>(optional)</small></span><textarea name="preferred_times" rows="2" placeholder="e.g. after 4pm on weekdays, or Sunday mornings"></textarea></label>';
```

- [ ] **Step 6: Add the client-side filter script**

In `gv_rf_shortcode()`, change the final return assembly. Current:

```php
    $html .= '</form></div>' . $ts_script;
    return $html;
```

Replace with:

```php
    $filter_script = '<script>(function(){'
        . 'var MAP=' . $loc_days_json . ';'
        . 'var sel=document.getElementById("gv-rf-location");'
        . 'var wrap=document.getElementById("gv-rf-days");'
        . 'var hint=document.getElementById("gv-rf-dayhint");'
        . 'if(!sel||!wrap)return;'
        . 'function apply(){'
        . 'var allowed=MAP[sel.value]||[];'
        . 'var boxes=wrap.querySelectorAll(".gv-rform-day");'
        . 'var any=false;'
        . 'boxes.forEach(function(b){'
        . 'var day=b.getAttribute("data-day");'
        . 'var ok=allowed.indexOf(day)!==-1;'
        . 'b.classList.toggle("gv-rform-day--hidden",!ok);'
        . 'var cb=b.querySelector("input");'
        . 'if(ok){any=true;}else if(cb){cb.checked=false;}'
        . '});'
        . 'if(hint)hint.style.display=any?"none":"block";'
        . '}'
        . 'sel.addEventListener("change",apply);apply();'
        . '})();</script>';
    $html .= '</form></div>' . $ts_script . $filter_script;
    return $html;
```

- [ ] **Step 7: Run tests + PHP lint**

Run: `php -l build/mu-plugins/gv-request-form.php`
Expected: `No syntax errors detected`.
Run: `php build/mu-plugins/tests/test-gv-request-form.php`
Expected: `ALL PASS`.

- [ ] **Step 8: Eyeball the rendered form**

Run: `php -r 'define("ABSPATH",1);function add_action(){}function add_shortcode(){}function add_filter(){}function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES);}function esc_html($s){return htmlspecialchars($s,ENT_QUOTES);}function esc_url($s){return $s;}function wp_create_nonce($a=""){return "n";}function admin_url($p=""){return "/wp-admin/".$p;}function sanitize_key($s){return $s;}function wp_json_encode($d){return json_encode($d);}require "build/mu-plugins/gv-request-form.php";echo gv_rf_shortcode();' > /tmp/gv-form-preview.html; echo "wrote /tmp/gv-form-preview.html"; grep -c 'name="preferred_days\[\]"' /tmp/gv-form-preview.html`
Expected: prints `wrote ...` and a count of `7` day checkboxes.

- [ ] **Step 9: Commit**

```bash
git add build/mu-plugins/gv-request-form.php build/mu-plugins/tests/test-gv-request-form.php
git commit -m "feat(form): render location select + location-filtered day checkboxes

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: Server-side handling — validate location/days, make time optional, add to emails

**Files:**
- Modify: `build/mu-plugins/gv-request-form.php` (`gv_rf_handle()`)

**Interfaces:**
- Consumes: `gv_rf_locations()`, `gv_rf_validate_location_days()` (Task 1).
- Produces: on invalid location/days → redirect `err`; on success → admin email
  and auto-reply include "Preferred location" and "Preferred day(s)".

- [ ] **Step 1: Parse and validate the new POST fields**

In `gv_rf_handle()`, find the block that reads POST values (currently ends with
`$times = sanitize_textarea_field(...);`). Immediately AFTER that line add:

```php
    $location = sanitize_key(wp_unslash($_POST['location'] ?? ''));
    $days_in  = (isset($_POST['preferred_days']) && is_array($_POST['preferred_days']))
        ? array_map('sanitize_text_field', wp_unslash($_POST['preferred_days']))
        : array();
```

- [ ] **Step 2: Update the required-fields guard (time now optional; add location/days)**

Replace the current guard:

```php
    if (!$parent || !$player || !is_email($email) || $age < 4 || $age > 25 || !in_array($type, gv_rf_types(), true) || !$times) $back('err');
```

with (drops `!$times`, adds location/day validation):

```php
    if (!$parent || !$player || !is_email($email) || $age < 4 || $age > 25 || !in_array($type, gv_rf_types(), true)) $back('err');
    if (!gv_rf_validate_location_days($location, $days_in)) $back('err');
```

- [ ] **Step 3: Add location + days to the admin notification table**

In the `$rows` array (admin email), currently:

```php
    $rows = array(
        'Parent / Guardian'      => esc_html($parent),
        'Player'                 => esc_html($player),
        'Player age'             => esc_html((string) $age),
        'Email'                  => esc_html($email),
        'Phone / Instagram'      => $alt ? esc_html($alt) : '&mdash;',
        'Training type'          => esc_html($type),
        'Preferred days &amp; times' => nl2br(esc_html($times)),
    );
```

replace with:

```php
    $locs      = gv_rf_locations();
    $loc_label = $locs[$location]['label'];
    $days_str  = implode(', ', $days_in);
    $rows = array(
        'Parent / Guardian'      => esc_html($parent),
        'Player'                 => esc_html($player),
        'Player age'             => esc_html((string) $age),
        'Email'                  => esc_html($email),
        'Phone / Instagram'      => $alt ? esc_html($alt) : '&mdash;',
        'Training type'          => esc_html($type),
        'Preferred location'     => esc_html($loc_label),
        'Preferred day(s)'       => esc_html($days_str),
        'Preferred time / notes' => $times ? nl2br(esc_html($times)) : '&mdash;',
    );
```

- [ ] **Step 4: Reflect location/days in the auto-reply body**

In the auto-reply `$reply_inner`, replace the "Your preferred times" paragraph:

```php
        . '<p style="margin:14px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1C1C1E;">'
        . 'Your preferred times: <em>' . nl2br(esc_html($times)) . '</em></p>'
```

with:

```php
        . '<p style="margin:14px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1C1C1E;">'
        . 'Location: <em>' . esc_html($loc_label) . '</em><br>'
        . 'Preferred day(s): <em>' . esc_html($days_str) . '</em>'
        . ($times ? '<br>Notes: <em>' . nl2br(esc_html($times)) . '</em>' : '') . '</p>'
```

- [ ] **Step 5: Lint + regression test**

Run: `php -l build/mu-plugins/gv-request-form.php`
Expected: `No syntax errors detected`.
Run: `php build/mu-plugins/tests/test-gv-request-form.php`
Expected: `ALL PASS` (unchanged — handler is WP-runtime only).

- [ ] **Step 6: Commit**

```bash
git add build/mu-plugins/gv-request-form.php
git commit -m "feat(form): validate location/days server-side, make time optional, add to emails

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: Modal subtitle copy (third-person, no Coach Gino)

**Files:**
- Modify: `build/pages/training-programs.html:225`

**Interfaces:**
- Consumes: nothing. Produces: updated modal subtitle text.

- [ ] **Step 1: Replace the subtitle line**

In `build/pages/training-programs.html`, replace:

```html
        <p class="gv-lead">Tell Coach Gino about your athlete. We'll follow up to confirm your consultation.</p>
```

with:

```html
        <p class="gv-lead">Share a few details about your athlete and the team will follow up to confirm the consultation.</p>
```

- [ ] **Step 2: Verify the change**

Run: `grep -n "Coach Gino" build/pages/training-programs.html`
Expected: no match on the modal subtitle line (line ~225).

- [ ] **Step 3: Commit**

```bash
git add build/pages/training-programs.html
git commit -m "copy: third-person consultation modal subtitle (drop Coach Gino mention)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 5: Regression guard comment in build-functional.php

**Files:**
- Modify: `build/scripts/build-functional.php` (near the page-2982 block, ~line 103)

**Note (deviation from spec):** The spec proposed repointing this block to
page 2981. That is unsafe — this block's HTML (`$book_a`/`$book_c`) has no
modal markup, so writing it to 2981 would overwrite the live modal. Instead we
add a guard comment establishing that 2981 is owned solely by
`deploy-training-programs.php`.

**Interfaces:** Consumes/produces nothing at runtime (comment only).

- [ ] **Step 1: Add the guard comment**

In `build/scripts/build-functional.php`, immediately BEFORE the
`echo gv_set_page_blocks(2982, array(` line, add:

```php
// NOTE: Page 2982 (/book-a-consultation/) is 302-redirected to /training-programs/
// by gv-request-form.php. The LIVE consultation form + modal lives on page 2981
// and is owned SOLELY by build/scripts/deploy-training-programs.php.
// Do NOT deploy the training-programs form/modal from here (this HTML has no modal).
```

- [ ] **Step 2: Lint + commit**

Run: `php -l build/scripts/build-functional.php`
Expected: `No syntax errors detected`.

```bash
git add build/scripts/build-functional.php
git commit -m "docs(build): mark page 2981 as owned by deploy-training-programs.php

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 6: Deploy to production (GATED — requires explicit user go-ahead)

**Files:** none changed. Deploys `gv-request-form.php`, `training-programs.html`, and re-runs `deploy-training-programs.php` against page 2981.

**Interfaces:** Consumes the committed working tree. Produces the fixed, live page.

- [ ] **Step 1: STOP and get explicit approval**

Confirm with the user before running anything in this task. This overwrites
live page 2981 content and pushes an mu-plugin to production.

- [ ] **Step 2: Confirm the live docroot + mu-plugins path**

Run: `ssh gvweb 'ls -d /home/u907133977/domains/gvbasketball.com/public_html && ls /home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins/'`
Expected: docroot exists; `gv-request-form.php` listed. Note the exact paths for the next steps (adjust if different).

- [ ] **Step 3: Upload the updated files**

```bash
DOCROOT=/home/u907133977/domains/gvbasketball.com/public_html
scp build/mu-plugins/gv-request-form.php gvweb:"$DOCROOT/wp-content/mu-plugins/gv-request-form.php"
scp build/pages/training-programs.html gvweb:'~/training-programs.html'
scp build/scripts/deploy-training-programs.php gvweb:'~/deploy-training-programs.php'
```
Expected: three files transfer (100%).

- [ ] **Step 4: Rebuild page 2981 + flush caches**

```bash
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html \
  && wp eval-file ~/deploy-training-programs.php \
  && wp elementor flush-css \
  && wp litespeed-purge all \
  && rm ~/deploy-training-programs.php ~/training-programs.html'
```
Expected: `gv_set_page_blocks` prints a success line for page 2981; no PHP errors.

- [ ] **Step 5: Verify the form is now on the live page**

Run:
```bash
curl -sL -A "Mozilla/5.0" "https://gvbasketball.com/training-programs/" \
 | grep -c -e 'name="preferred_days\[\]"' -e 'name="location"' -e 'name="parent_name"'
```
Expected: non-zero counts (form fields present — previously all zero).

- [ ] **Step 6: Manual smoke test (browser)**

Instruct the user (or drive via browser tooling): open `/training-programs/`,
click "Book a Consultation" → modal shows the full form. Pick "Dasma, Makati"
→ only Mon/Wed/Thu day chips appear. Switch to "Urdaneta Village" → Fri/Sun
appear, previously-checked Dasma days are cleared (invalid). Pick "Open to any
location" → all 7 days. Submit with valid data → success banner; confirm the
admin email + auto-reply include location and day(s).

- [ ] **Step 7: Confirm completion**

Report results with evidence (the curl counts + a note on the browser smoke
test). Do not claim success without the verification output.

---

## Self-Review

**Spec coverage:**
- Part A (empty modal fix): Task 6 (redeploy 2981 with both blocks) + Task 5 (regression guard). ✅
- Part B field changes — location dropdown: Task 2. Conditional day multi-select (preserve valid on change): Task 2 Step 6. Time field optional/relabeled: Task 2 Step 5 + Task 3 Step 2. ✅
- Single source of truth (PHP + JS mirror): Task 1 (`gv_rf_locations`) + Task 2 Step 4 (`GV_RF_LOC_DAYS`). ✅
- Server-side validation: Task 3. ✅
- Emails include location + days: Task 3 Steps 3-4. ✅
- Copy change (third-person, no Coach Gino): Task 4. ✅
- "Open to any" = all 7 days: Task 1 map + validation test. ✅
- Deploy gated on approval: Task 6 Step 1. ✅

**Deviation logged:** Spec Part A item 3 (repoint build-functional.php to 2981) replaced with a guard comment (Task 5) because repointing would push modal-less markup to the live page. Flagged to user.

**Placeholder scan:** No TBD/TODO; every code step shows complete code. ✅

**Type consistency:** `gv_rf_locations()` returns `['label','days_label','days']` used identically in Tasks 2 and 3. `gv_rf_validate_location_days($location, $days)` signature consistent across Task 1 (def), Task 3 (call). Location keys `dasma/urdaneta/corinth/any` and day tokens consistent throughout. JS `GV_RF_LOC_DAYS` map matches PHP `days` arrays. ✅
