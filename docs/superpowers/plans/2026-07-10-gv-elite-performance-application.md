# GV Elite Performance — Card Rebrand + Application Experience Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebrand the Elite Performance program as the selective, application-based **GV Elite Performance** — new card/detail copy with APPLY NOW CTAs, plus a 6-step branded application at `/apply/` backed by a new mu-plugin.

**Architecture:** Static page HTML lives in `build/pages/*.html` and is pushed into WordPress with `gv_set_page_html()` via `wp eval-file` scripts. The application form is a new must-use plugin `build/mu-plugins/gv-elite-application.php` (prefix `gv_ea_`): a field-model-driven form rendered by shortcode `[gv_elite_application]`, handled on `template_redirect`, stored as a private CPT `gv_application`, with two branded emails. It **reuses** the tested legacy helpers in `gv-request-form.php` (`gv_rf_email_shell()`, `gv_rf_verify_turnstile()`, `GV_RF_RECIPIENT`) — do not duplicate them.

**Tech Stack:** WordPress mu-plugins (plain PHP, no framework), framework-free CLI tests (`php build/mu-plugins/tests/test-*.php`), vanilla JS/CSS printed with the shortcode, WP-CLI deploys over SSH.

**Spec:** `docs/superpowers/specs/2026-07-10-gv-elite-performance-application-design.md` — copy is client-final; apply verbatim where quoted.

## Global Constraints

- Program name is exactly **GV Elite Performance**; button label exactly **Apply Now** (rendered uppercase by existing `.gv-btn` CSS).
- Card kicker text: **Application Required · Limited Enrollment**.
- Bullets exactly: **Court Training / Strength & Conditioning / Recovery / Nutrition** (never "Court", never plain "Strength", never "aqua training" anywhere).
- Tone statement (verbatim, em-dash as written): *"GV Elite Performance is a selective development program for committed student-athletes. Admission is based on character, coachability, commitment, and potential—not solely on current basketball ability."*
- Only Elite Performance CTAs change; every other `data-gv-consultation` button stays untouched.
- Video is a **URL field** (no file upload). Age is **derived from DOB** server-side (no Age input).
- All function names in the new plugin use the `gv_ea_` prefix. Coach inbox = `GV_RF_RECIPIENT` (`gvbasketballcoaching@gmail.com`).
- mu-plugins load alphabetically: `gv-elite-application.php` loads **before** `gv-request-form.php`, so never call `gv_rf_*` at include time — only inside hooks/handlers. Tests must `require` gv-request-form.php first.
- Tests are framework-free CLI scripts; every existing suite must stay green: `for t in build/mu-plugins/tests/test-*.php; do php "$t" || break; done`

---

### Task 1: Copy changes on existing pages + LatePoint service description

**Files:**
- Modify: `build/pages/home.html:81-87`
- Modify: `build/pages/training-programs.html:43-53` and `:118-146` and `:154`
- Modify: `build/scripts/setup-latepoint.php:50`

**Interfaces:**
- Produces: APPLY NOW anchors pointing to `/apply/` (page built in Task 6).

- [ ] **Step 1: Update the home page card**

In `build/pages/home.html`, replace lines 81–87 (the Elite Performance `gv-program` card) with:

```html
        <div class="gv-program">
          <div class="gv-program__head"><div class="gv-program__ic"><svg viewBox="0 0 24 24"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg></div><h3 class="gv-program__name">GV Elite Performance</h3><div class="gv-program__for">Application Required · Limited Enrollment</div></div>
          <div class="gv-program__body">
            <p>The complete performance system for aspiring elite athletes — court training, strength &amp; conditioning, recovery, and nutrition.</p>
            <div class="gv-program__foot"><a class="gv-btn gv-btn--primary" href="/apply/">Apply Now</a></div>
          </div>
        </div>
```

(Only this card: keep the surrounding Private/Small Group cards and their `data-gv-consultation` buttons byte-identical.)

- [ ] **Step 2: Update the training-programs card**

In `build/pages/training-programs.html`, replace lines 43–53 with:

```html
        <div class="gv-program">
          <div class="gv-program__head"><div class="gv-program__ic"><svg viewBox="0 0 24 24"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg></div><h3 class="gv-program__name">GV Elite Performance</h3><div class="gv-program__for">Application Required · Limited Enrollment</div></div>
          <div class="gv-program__body">
            <p style="margin:0 0 10px;">The complete performance system for aspiring elite athletes.</p>
            <ul class="gv-list">
              <li>Court Training</li>
              <li>Strength &amp; Conditioning</li>
              <li>Recovery</li>
              <li>Nutrition</li>
            </ul>
            <div class="gv-program__foot"><a class="gv-btn gv-btn--primary" href="/apply/">Apply Now</a></div>
          </div>
        </div>
```

- [ ] **Step 3: Update the training-programs detail section**

In `build/pages/training-programs.html`, inside the `<!-- ELITE PERFORMANCE DETAIL -->` section (lines 118–146), replace the inner text column (`<h2>` through the button row) with:

```html
          <h2 class="gv-section-title">GV Elite Performance</h2>
          <div class="gv-rule"></div>
          <h3 class="gv-subhead">Who It's For</h3>
          <p>Serious athletes preparing for the next level of competition. GV Elite Performance is the complete performance system — court training, strength &amp; conditioning, recovery, and nutrition — delivered as one integrated program. Admission is by application, based on character, coachability, commitment, and potential.</p>
          <h3 class="gv-subhead">What's Included</h3>
          <ul class="gv-list">
            <li>Court Training — offensive &amp; defensive skill development</li>
            <li>Strength &amp; Conditioning</li>
            <li>Recovery, mobility &amp; injury prevention</li>
            <li>Nutrition guidance</li>
            <li>Structured, periodized progression</li>
          </ul>
          <h3 class="gv-subhead">Admission</h3>
          <p>Application required. Limited enrollment.</p>
          <div class="gv-btn-row" style="margin-top:28px;">
            <a class="gv-btn gv-btn--primary" href="/apply/">Apply Now</a>
          </div>
```

Keep the media `<img>` block unchanged. Also update line ~154 ("Private &amp; Elite Performance are scheduled by appointment.") to:

```html
        <p class="gv-lead">Small-group sessions run across Metro Manila. Private training is scheduled by appointment; GV Elite Performance is by application.</p>
```

- [ ] **Step 4: Update the LatePoint service description**

In `build/scripts/setup-latepoint.php:50`:

```php
$elite   = gv_svc('GV Elite Performance', 90, 1, 6, 'The complete performance system: court training, strength & conditioning, recovery, and nutrition. Application required.');
```

- [ ] **Step 5: Verify no stragglers**

```bash
grep -rni "aqua" build/ ; echo "exit=$?"
```
Expected: `exit=1` (no matches).

```bash
grep -c "data-gv-consultation" build/pages/home.html build/pages/training-programs.html
```
Expected: `home.html:2` (Private + Small Group) and `training-programs.html:2`.

- [ ] **Step 6: Run existing test suites (regression guard)**

```bash
for t in build/mu-plugins/tests/test-*.php; do echo "== $t"; php "$t" || break; done
```
Expected: all `ok` lines, no `FAIL`.

- [ ] **Step 7: Commit**

```bash
git add build/pages/home.html build/pages/training-programs.html build/scripts/setup-latepoint.php
git commit -m "feat: rebrand Elite Performance card/detail as GV Elite Performance with Apply Now CTA"
```

---

### Task 2: Mu-plugin skeleton — field model, age derivation, validation (TDD)

**Files:**
- Create: `build/mu-plugins/gv-elite-application.php`
- Test: `build/mu-plugins/tests/test-gv-elite-application.php`

**Interfaces:**
- Produces:
  - `gv_ea_sections(): array` — ordered map `stepKey => ['title' => string, 'fields' => [fieldKey => def]]`. Field def keys: `label` (string), `type` (`text|date|textarea|radio|checkbox|url|email|confirm`), `required` (bool), `options` (map value=>label, radio/checkbox only), `max` (int, checkbox only), `help` (string, optional), `placeholder` (string, optional).
  - `gv_ea_age_from_dob(string $dob, ?DateTime $today = null): ?int`
  - `gv_ea_collect(array $in): array` — sanitized values keyed by fieldKey (arrays for checkboxes, trimmed strings otherwise).
  - `gv_ea_validate(array $values): array` — `fieldKey => error message`, empty array when valid.
- Step keys in order: `athlete`, `background`, `commitment`, `character`, `video_parent`, `final`.

- [ ] **Step 1: Write the failing test**

Create `build/mu-plugins/tests/test-gv-elite-application.php`:

```php
<?php
// Framework-free CLI test. Run: php build/mu-plugins/tests/test-gv-elite-application.php
define('ABSPATH', __DIR__);
function add_action() {}
function add_shortcode() {}
function add_filter() {}
function register_post_type() {}
function esc_attr($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_html($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_url($s){ return (string)$s; }
function esc_textarea($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function sanitize_text_field($s){ return trim(preg_replace('/[\r\n\t ]+/', ' ', (string)$s)); }
function sanitize_textarea_field($s){ return trim((string)$s); }
function wp_create_nonce($a=''){ return 'testnonce'; }
function home_url($p=''){ return 'https://example.test'.$p; }

require __DIR__ . '/../gv-request-form.php';       // helpers first (email shell etc.)
require __DIR__ . '/../gv-elite-application.php';

$failures = 0;
function check($label, $cond) {
    global $failures;
    if ($cond) { echo "ok   - $label\n"; }
    else { echo "FAIL - $label\n"; $failures++; }
}

// --- gv_ea_sections() shape ---
$secs = gv_ea_sections();
check('six steps in client order',
    array_keys($secs) === array('athlete','background','commitment','character','video_parent','final'));
check('athlete step has no separate age field',
    !isset($secs['athlete']['fields']['age']) && isset($secs['athlete']['fields']['dob']));
check('qualities is checkbox capped at 3',
    $secs['character']['fields']['qualities']['type'] === 'checkbox'
    && $secs['character']['fields']['qualities']['max'] === 3
    && count($secs['character']['fields']['qualities']['options']) === 8);
check('video_url is a required url field with 2-4 minute helper',
    $secs['video_parent']['fields']['video_url']['type'] === 'url'
    && $secs['video_parent']['fields']['video_url']['required'] === true
    && strpos($secs['video_parent']['fields']['video_url']['help'], '2–4') !== false);
check('final step is three required confirms',
    count($secs['final']['fields']) === 3
    && $secs['final']['fields']['confirm_no_guarantee']['type'] === 'confirm'
    && $secs['final']['fields']['confirm_criteria']['required'] === true
    && $secs['final']['fields']['confirm_standards']['type'] === 'confirm');
check('improvement radio includes Other', isset($secs['commitment']['fields']['improvement']['options']['other']));
check('parent_support is Yes/No radio',
    $secs['video_parent']['fields']['parent_support']['options'] === array('yes'=>'Yes','no'=>'No'));

// --- gv_ea_age_from_dob() ---
$today = new DateTime('2026-07-10', new DateTimeZone('Asia/Manila'));
check('age: birthday already passed this year', gv_ea_age_from_dob('2012-03-05', $today) === 14);
check('age: birthday later this year', gv_ea_age_from_dob('2012-12-25', $today) === 13);
check('age: birthday today counts', gv_ea_age_from_dob('2012-07-10', $today) === 14);
check('age: future DOB rejected', gv_ea_age_from_dob('2027-01-01', $today) === null);
check('age: garbage rejected', gv_ea_age_from_dob('not-a-date', $today) === null);
check('age: impossible date rejected', gv_ea_age_from_dob('2012-02-31', $today) === null);

// --- gv_ea_collect() + gv_ea_validate() ---
function gv_ea_valid_input() {
    return array(
        'full_name'=>'Miguel Santos','dob'=>'2012-03-05','school'=>'Xavier School','grade'=>'Grade 8',
        'height'=>"5'4\"",'weight'=>'110 lbs',
        'years'=>'y4_6','level'=>'school_team','positions'=>'PG / SG','current_team'=>'Xavier Jr. Team',
        'why_join'=>'I want to train seriously.','goals_12mo'=>'Make varsity.',
        'improvement'=>'shooting','improvement_other'=>'','days_per_week'=>'d3',
        'qualities'=>array('coachable','disciplined'),
        'coachable_meaning'=>'Listening and applying corrections.','challenge'=>'Came back from an ankle injury.',
        'video_url'=>'https://youtu.be/abc123',
        'parent_name'=>'Ana Santos','parent_relationship'=>'Mother','parent_mobile'=>'0917 555 1234',
        'parent_email'=>'ana@example.com','parent_why'=>'Structured development.',
        'parent_values'=>array('discipline','work_ethic'),'parent_values_other'=>'',
        'parent_support'=>'yes','parent_notes'=>'',
        'confirm_no_guarantee'=>'1','confirm_criteria'=>'1','confirm_standards'=>'1',
    );
}
check('valid application passes', gv_ea_validate(gv_ea_collect(gv_ea_valid_input())) === array());

$in = gv_ea_valid_input(); $in['full_name'] = '  ';
check('blank required field fails', isset(gv_ea_validate(gv_ea_collect($in))['full_name']));

$in = gv_ea_valid_input(); $in['qualities'] = array('coachable','disciplined','leader','resilient');
check('more than 3 qualities fails', isset(gv_ea_validate(gv_ea_collect($in))['qualities']));

$in = gv_ea_valid_input(); $in['qualities'] = array('coachable','hax0r');
check('unknown checkbox value fails', isset(gv_ea_validate(gv_ea_collect($in))['qualities']));

$in = gv_ea_valid_input(); $in['parent_email'] = 'not-an-email';
check('bad parent email fails', isset(gv_ea_validate(gv_ea_collect($in))['parent_email']));

$in = gv_ea_valid_input(); $in['video_url'] = 'youtube.com/watch?v=abc';
check('schemeless video url fails', isset(gv_ea_validate(gv_ea_collect($in))['video_url']));

$in = gv_ea_valid_input(); $in['confirm_criteria'] = '';
check('missing confirmation fails', isset(gv_ea_validate(gv_ea_collect($in))['confirm_criteria']));

$in = gv_ea_valid_input(); $in['level'] = 'nba';
check('unknown radio value fails', isset(gv_ea_validate(gv_ea_collect($in))['level']));

$in = gv_ea_valid_input(); unset($in['parent_notes'], $in['current_team'], $in['height'], $in['weight']);
check('optional fields may be absent', gv_ea_validate(gv_ea_collect($in)) === array());

echo $failures ? "\n$failures FAILURES\n" : "\nALL PASS\n";
exit($failures ? 1 : 0);
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php build/mu-plugins/tests/test-gv-elite-application.php
```
Expected: fatal error — `gv-elite-application.php` does not exist yet.

- [ ] **Step 3: Write the implementation**

Create `build/mu-plugins/gv-elite-application.php`:

```php
<?php
/*
Plugin Name: GV Basketball — Elite Performance Application
Description: Renders and processes the GV Elite Performance athlete application ([gv_elite_application] on /apply/). Stores submissions as the private gv_application CPT and sends branded coach + applicant emails. Reuses gv-request-form.php legacy helpers (email shell, Turnstile verification, coach recipient) — those load after this file, so only call gv_rf_* inside hooks.
Version: 1.0
*/
if (!defined('ABSPATH')) exit;

/* ---------------- Field model (single source of truth) ---------------- */
function gv_ea_sections() {
    return array(
        'athlete' => array('title' => 'Athlete Information', 'fields' => array(
            'full_name' => array('label' => 'Full Name', 'type' => 'text', 'required' => true),
            'dob'       => array('label' => 'Date of Birth', 'type' => 'date', 'required' => true, 'help' => 'We calculate the athlete\'s age from this.'),
            'school'    => array('label' => 'School', 'type' => 'text', 'required' => true),
            'grade'     => array('label' => 'Grade Level', 'type' => 'text', 'required' => true),
            'height'    => array('label' => 'Height', 'type' => 'text', 'required' => false),
            'weight'    => array('label' => 'Weight', 'type' => 'text', 'required' => false),
        )),
        'background' => array('title' => 'Basketball Background', 'fields' => array(
            'years' => array('label' => 'How many years have you been playing organized basketball?', 'type' => 'radio', 'required' => true, 'options' => array(
                'lt1' => 'Less than 1 year', 'y1_3' => '1–3 years', 'y4_6' => '4–6 years', 'gt6' => 'More than 6 years')),
            'level' => array('label' => 'Which best describes your current level?', 'type' => 'radio', 'required' => true, 'options' => array(
                'beginner' => 'Beginner', 'recreational' => 'Recreational', 'school_team' => 'School Team',
                'club_team' => 'Club Team', 'varsity' => 'Varsity', 'regional' => 'Regional/National Level')),
            'positions'    => array('label' => 'What position(s) do you play?', 'type' => 'text', 'required' => true),
            'current_team' => array('label' => 'Current team, club, or school (if applicable)', 'type' => 'text', 'required' => false),
        )),
        'commitment' => array('title' => 'Commitment', 'fields' => array(
            'why_join'   => array('label' => 'Why do you want to join GV Elite Performance?', 'type' => 'textarea', 'required' => true),
            'goals_12mo' => array('label' => 'What are your basketball goals over the next 12 months?', 'type' => 'textarea', 'required' => true),
            'improvement' => array('label' => 'What do you believe is your biggest area for improvement?', 'type' => 'radio', 'required' => true, 'options' => array(
                'shooting' => 'Shooting', 'ball_handling' => 'Ball Handling', 'footwork' => 'Footwork', 'defense' => 'Defense',
                'decision_making' => 'Decision Making', 'strength' => 'Strength', 'conditioning' => 'Conditioning',
                'confidence' => 'Confidence', 'leadership' => 'Leadership', 'other' => 'Other')),
            'improvement_other' => array('label' => 'If other, tell us more', 'type' => 'text', 'required' => false),
            'days_per_week' => array('label' => 'How many days per week are you willing to train?', 'type' => 'radio', 'required' => true, 'options' => array(
                'd2' => '2', 'd3' => '3', 'd4' => '4', 'd5plus' => '5+')),
        )),
        'character' => array('title' => 'Character', 'fields' => array(
            'qualities' => array('label' => 'Which qualities describe you? (Select up to 3)', 'type' => 'checkbox', 'required' => true, 'max' => 3, 'options' => array(
                'coachable' => 'Coachable', 'competitive' => 'Competitive', 'disciplined' => 'Disciplined', 'hardworking' => 'Hardworking',
                'positive_teammate' => 'Positive Teammate', 'resilient' => 'Resilient', 'accountable' => 'Accountable', 'leader' => 'Leader')),
            'coachable_meaning' => array('label' => 'What does being coachable mean to you?', 'type' => 'textarea', 'required' => true),
            'challenge' => array('label' => 'Tell us about a challenge you\'ve overcome in basketball or in life.', 'type' => 'textarea', 'required' => true),
        )),
        'video_parent' => array('title' => 'Video & Parent Information', 'fields' => array(
            'video_url' => array('label' => 'Highlight Video Link', 'type' => 'url', 'required' => true,
                'placeholder' => 'https://…',
                'help' => 'Paste a YouTube, Google Drive, or similar link — 2–4 minutes showing ball handling, shooting, finishing, and footwork. Game footage is encouraged but not required.'),
            'parent_name'         => array('label' => 'Parent/Guardian Name', 'type' => 'text', 'required' => true),
            'parent_relationship' => array('label' => 'Relationship to Athlete', 'type' => 'text', 'required' => true),
            'parent_mobile'       => array('label' => 'Mobile Number', 'type' => 'text', 'required' => true),
            'parent_email'        => array('label' => 'Email Address', 'type' => 'email', 'required' => true),
            'parent_why' => array('label' => 'Why are you interested in GV Elite Performance for your child?', 'type' => 'textarea', 'required' => true),
            'parent_values' => array('label' => 'What values do you hope basketball teaches your child?', 'type' => 'checkbox', 'required' => true, 'options' => array(
                'discipline' => 'Discipline', 'confidence' => 'Confidence', 'leadership' => 'Leadership', 'accountability' => 'Accountability',
                'teamwork' => 'Teamwork', 'resilience' => 'Resilience', 'work_ethic' => 'Work Ethic', 'other' => 'Other')),
            'parent_values_other' => array('label' => 'If other, tell us more', 'type' => 'text', 'required' => false),
            'parent_support' => array('label' => 'Are you prepared to support your child\'s commitment to training, recovery, nutrition, and attendance?', 'type' => 'radio', 'required' => true, 'options' => array('yes' => 'Yes', 'no' => 'No')),
            'parent_notes' => array('label' => 'Is there anything Coach Gino and the coaching staff should know about your child?', 'type' => 'textarea', 'required' => false),
        )),
        'final' => array('title' => 'Final Commitment', 'fields' => array(
            'confirm_no_guarantee' => array('label' => 'I understand that submitting this application does not guarantee acceptance into GV Elite Performance.', 'type' => 'confirm', 'required' => true),
            'confirm_criteria'     => array('label' => 'I understand that athletes are selected based on commitment, coachability, character, and potential, not solely on current skill level.', 'type' => 'confirm', 'required' => true),
            'confirm_standards'    => array('label' => 'If accepted, I am prepared to commit to the standards and expectations of the program.', 'type' => 'confirm', 'required' => true),
        )),
    );
}

/* ---------------- Age derivation (Manila tz, injectable for tests) ---------------- */
function gv_ea_age_from_dob($dob, $today = null) {
    if (!is_string($dob) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) return null;
    $d = DateTime::createFromFormat('!Y-m-d', $dob, new DateTimeZone('Asia/Manila'));
    if (!$d || $d->format('Y-m-d') !== $dob) return null; // catches 2012-02-31
    if (!$today instanceof DateTime) $today = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $t = clone $today; $t->setTime(0, 0, 0);
    if ($d > $t) return null;
    $age = $d->diff($t)->y;
    return ($age > 100) ? null : $age;
}

/* ---------------- Sanitize raw input into typed values ---------------- */
function gv_ea_collect($in) {
    $out = array();
    foreach (gv_ea_sections() as $sec) {
        foreach ($sec['fields'] as $key => $f) {
            $raw = isset($in[$key]) ? $in[$key] : ($f['type'] === 'checkbox' ? array() : '');
            if ($f['type'] === 'checkbox') {
                $out[$key] = is_array($raw) ? array_values(array_map('sanitize_text_field', $raw)) : array();
            } elseif ($f['type'] === 'textarea') {
                $out[$key] = is_string($raw) ? sanitize_textarea_field($raw) : '';
            } else {
                $out[$key] = is_string($raw) ? sanitize_text_field($raw) : '';
            }
        }
    }
    return $out;
}

/* ---------------- Validation: fieldKey => message, empty array when valid ---------------- */
function gv_ea_validate($values) {
    $errors = array();
    foreach (gv_ea_sections() as $sec) {
        foreach ($sec['fields'] as $key => $f) {
            $val = isset($values[$key]) ? $values[$key] : ($f['type'] === 'checkbox' ? array() : '');
            if ($f['type'] === 'checkbox') {
                if (array_diff($val, array_keys($f['options']))) { $errors[$key] = 'Invalid selection.'; continue; }
                if (!empty($f['required']) && !$val) { $errors[$key] = 'Please select at least one.'; continue; }
                if (!empty($f['max']) && count($val) > $f['max']) $errors[$key] = 'Please select up to ' . $f['max'] . '.';
                continue;
            }
            if (!empty($f['required']) && $val === '') { $errors[$key] = 'This field is required.'; continue; }
            if ($val === '') continue;
            switch ($f['type']) {
                case 'radio':
                    if (!isset($f['options'][$val])) $errors[$key] = 'Invalid selection.';
                    break;
                case 'confirm':
                    if ($val !== '1') $errors[$key] = 'Please confirm to continue.';
                    break;
                case 'email':
                    if (!filter_var($val, FILTER_VALIDATE_EMAIL)) $errors[$key] = 'Please enter a valid email address.';
                    break;
                case 'url':
                    if (!preg_match('#^https?://\S+$#i', $val)) $errors[$key] = 'Please paste the full link, starting with https://.';
                    break;
                case 'date':
                    if (gv_ea_age_from_dob($val) === null) $errors[$key] = 'Please enter a valid date of birth.';
                    break;
            }
        }
    }
    return $errors;
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php build/mu-plugins/tests/test-gv-elite-application.php
```
Expected: all `ok`, ends `ALL PASS`, exit 0.

- [ ] **Step 5: Commit**

```bash
git add build/mu-plugins/gv-elite-application.php build/mu-plugins/tests/test-gv-elite-application.php
git commit -m "feat: gv-elite-application field model, age derivation, validation (TDD)"
```

---

### Task 3: Form rendering — shortcode, stepper markup, scoped CSS/JS

**Files:**
- Modify: `build/mu-plugins/gv-elite-application.php` (append)
- Test: `build/mu-plugins/tests/test-gv-elite-application.php` (append before the exit lines)

**Interfaces:**
- Consumes: `gv_ea_sections()`, `gv_ea_collect()` from Task 2.
- Produces:
  - `gv_ea_render_field(string $key, array $f, array $values, array $errors): string`
  - `gv_ea_render_form(array $values = [], array $errors = []): string` — full `<form>` with steps, nonce, honeypot, Turnstile slot.
  - Shortcode `gv_elite_application` → `gv_ea_shortcode()` (form, or confirmation panel when `$_GET['submitted']`).
  - Form POST marker field: `gv_ea_submit=1`; nonce field name `gv_ea_nonce`, action `gv_ea_apply`; honeypot field `gv_ea_website`.

- [ ] **Step 1: Write the failing tests (append to the test file, above the final `echo $failures…` block)**

```php
// --- gv_ea_render_field() ---
$secs2 = gv_ea_sections();
$html = gv_ea_render_field('parent_email', $secs2['video_parent']['fields']['parent_email'], array('parent_email' => 'ana@example.com'), array());
check('email field renders type=email with value',
    strpos($html, 'type="email"') !== false && strpos($html, 'value="ana@example.com"') !== false
    && strpos($html, 'name="parent_email"') !== false && strpos($html, 'required') !== false);

$html = gv_ea_render_field('qualities', $secs2['character']['fields']['qualities'], array('qualities' => array('coachable')), array());
check('checkbox group renders name[] and checked state',
    strpos($html, 'name="qualities[]"') !== false && substr_count($html, '<input') === 8
    && preg_match('/value="coachable"[^>]*checked/', $html) === 1
    && strpos($html, 'data-max="3"') !== false);

$html = gv_ea_render_field('full_name', $secs2['athlete']['fields']['full_name'], array(), array('full_name' => 'This field is required.'));
check('field error message renders', strpos($html, 'This field is required.') !== false
    && strpos($html, 'gv-ea-error') !== false);

$html = gv_ea_render_field('confirm_criteria', $secs2['final']['fields']['confirm_criteria'], array(), array());
check('confirm renders single checkbox value=1',
    strpos($html, 'type="checkbox"') !== false && strpos($html, 'value="1"') !== false);

// --- gv_ea_render_form() ---
$form = gv_ea_render_form();
check('form posts to itself with marker + nonce + honeypot',
    strpos($form, 'name="gv_ea_submit"') !== false
    && strpos($form, 'name="gv_ea_nonce"') !== false
    && strpos($form, 'name="gv_ea_website"') !== false);
check('form has six steps with titles',
    substr_count($form, 'data-gv-ea-step') === 6 && strpos($form, 'Video &amp; Parent Information') !== false);
check('form includes stepper controls and submit',
    strpos($form, 'data-gv-ea-next') !== false && strpos($form, 'data-gv-ea-prev') !== false
    && strpos($form, 'type="submit"') !== false);
check('form repopulates values', strpos(gv_ea_render_form(array('school' => 'Xavier School')), 'value="Xavier School"') !== false);
```

- [ ] **Step 2: Run to verify failure**

```bash
php build/mu-plugins/tests/test-gv-elite-application.php
```
Expected: fatal `Call to undefined function gv_ea_render_field()`.

- [ ] **Step 3: Implement rendering (append to `gv-elite-application.php`)**

```php
/* ---------------- Rendering ---------------- */
function gv_ea_render_field($key, $f, $values, $errors) {
    $val   = isset($values[$key]) ? $values[$key] : ($f['type'] === 'checkbox' ? array() : '');
    $req   = !empty($f['required']);
    $err   = isset($errors[$key]) ? '<p class="gv-ea-error">' . esc_html($errors[$key]) . '</p>' : '';
    $help  = !empty($f['help']) ? '<p class="gv-ea-help">' . esc_html($f['help']) . '</p>' : '';
    $reqA  = $req ? ' required' : '';
    $label = esc_html($f['label']) . ($req ? ' <span class="gv-ea-req">*</span>' : '');
    $id    = 'gv-ea-' . $key;

    if ($f['type'] === 'confirm') {
        $chk = ($val === '1') ? ' checked' : '';
        return '<div class="gv-ea-field gv-ea-field--confirm"><label class="gv-ea-check"><input type="checkbox" name="' . esc_attr($key) . '" value="1"' . $chk . $reqA . '> <span>' . $label . '</span></label>' . $err . '</div>';
    }
    if ($f['type'] === 'radio' || $f['type'] === 'checkbox') {
        $isCb = $f['type'] === 'checkbox';
        $name = esc_attr($key) . ($isCb ? '[]' : '');
        $max  = ($isCb && !empty($f['max'])) ? ' data-max="' . (int) $f['max'] . '"' : '';
        $opts = '';
        foreach ($f['options'] as $ov => $ol) {
            $on = $isCb ? in_array($ov, (array) $val, true) : ($val === $ov);
            $opts .= '<label class="gv-ea-opt"><input type="' . ($isCb ? 'checkbox' : 'radio') . '" name="' . $name . '" value="' . esc_attr($ov) . '"' . ($on ? ' checked' : '') . (($req && !$isCb) ? ' required' : '') . '> <span>' . esc_html($ol) . '</span></label>';
        }
        return '<fieldset class="gv-ea-field gv-ea-group"' . $max . '><legend>' . $label . '</legend>' . $help . '<div class="gv-ea-opts">' . $opts . '</div>' . $err . '</fieldset>';
    }
    if ($f['type'] === 'textarea') {
        return '<div class="gv-ea-field"><label for="' . $id . '">' . $label . '</label>' . $help . '<textarea id="' . $id . '" name="' . esc_attr($key) . '" rows="4"' . $reqA . '>' . esc_textarea($val) . '</textarea>' . $err . '</div>';
    }
    $type = in_array($f['type'], array('date', 'email', 'url'), true) ? $f['type'] : 'text';
    $ph   = !empty($f['placeholder']) ? ' placeholder="' . esc_attr($f['placeholder']) . '"' : '';
    return '<div class="gv-ea-field"><label for="' . $id . '">' . $label . '</label>' . $help . '<input id="' . $id . '" type="' . $type . '" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '"' . $ph . $reqA . '>' . $err . '</div>';
}

function gv_ea_render_form($values = array(), $errors = array()) {
    $steps = ''; $i = 0; $n = count(gv_ea_sections());
    foreach (gv_ea_sections() as $skey => $sec) {
        $i++;
        $fields = '';
        foreach ($sec['fields'] as $key => $f) $fields .= gv_ea_render_field($key, $f, $values, $errors);
        $nav = '<div class="gv-ea-nav">'
             . ($i > 1 ? '<button type="button" class="gv-btn gv-btn--ghost" data-gv-ea-prev>Back</button>' : '<span></span>')
             . ($i < $n ? '<button type="button" class="gv-btn gv-btn--primary" data-gv-ea-next>Next</button>'
                        : '<button type="submit" class="gv-btn gv-btn--primary">Submit Application</button>')
             . '</div>';
        $steps .= '<section class="gv-ea-step" data-gv-ea-step="' . esc_attr($skey) . '">'
                . '<p class="gv-ea-progress">Step ' . $i . ' of ' . $n . '</p>'
                . '<h3 class="gv-ea-step__title">' . esc_html($sec['title']) . '</h3>'
                . $fields . $nav . '</section>';
    }
    $summary   = $errors ? '<div class="gv-ea-summary" role="alert">Please review the highlighted answers below and resubmit.</div>' : '';
    $turnstile = (defined('GV_TURNSTILE_SITEKEY') && GV_TURNSTILE_SITEKEY)
        ? '<div class="cf-turnstile" data-sitekey="' . esc_attr(GV_TURNSTILE_SITEKEY) . '"></div>'
          . '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>'
        : '';
    return '<form class="gv-ea-form" method="post" action="" novalidate>'
         . $summary
         . '<input type="hidden" name="gv_ea_submit" value="1">'
         . '<input type="hidden" name="gv_ea_nonce" value="' . esc_attr(wp_create_nonce('gv_ea_apply')) . '">'
         . '<div class="gv-ea-hp" aria-hidden="true"><label>Website<input type="text" name="gv_ea_website" tabindex="-1" autocomplete="off"></label></div>'
         . $steps . $turnstile . '</form>' . gv_ea_assets();
}

function gv_ea_assets() {
    // Scoped styles + stepper. Without JS every step stays visible and the form still posts.
    $css = '<style>
.gv-ea-form{max-width:680px;margin:0 auto}
.gv-ea-step{background:#fff;border:1px solid #E6E7E9;border-radius:14px;padding:28px;margin:0 0 18px}
.gv-ea-form.gv-ea-js .gv-ea-step{display:none}
.gv-ea-form.gv-ea-js .gv-ea-step.gv-ea-active{display:block}
.gv-ea-progress{margin:0 0 4px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#F47B20}
.gv-ea-step__title{margin:0 0 18px;color:#123B78}
.gv-ea-field{margin:0 0 16px}
.gv-ea-field label,.gv-ea-group legend{display:block;font-weight:600;color:#1C1C1E;margin:0 0 6px}
.gv-ea-field input[type=text],.gv-ea-field input[type=date],.gv-ea-field input[type=email],.gv-ea-field input[type=url],.gv-ea-field textarea{width:100%;padding:10px 12px;border:1px solid #C9CCD1;border-radius:8px;font:inherit}
.gv-ea-group{border:0;padding:0;margin:0 0 16px}
.gv-ea-opts{display:flex;flex-wrap:wrap;gap:8px}
.gv-ea-opt{display:inline-flex;align-items:center;gap:6px;border:1px solid #C9CCD1;border-radius:999px;padding:7px 14px;cursor:pointer;font-weight:500}
.gv-ea-opt:has(input:checked){border-color:#F47B20;background:#FFF4EB}
.gv-ea-check{display:flex;gap:10px;align-items:flex-start;font-weight:500}
.gv-ea-help{margin:0 0 8px;font-size:13px;color:#6B6F76}
.gv-ea-req{color:#F47B20}
.gv-ea-error{margin:6px 0 0;font-size:13px;font-weight:600;color:#C0392B}
.gv-ea-summary{background:#FDEDEC;border:1px solid #C0392B;color:#C0392B;border-radius:10px;padding:12px 16px;margin:0 0 18px;font-weight:600}
.gv-ea-nav{display:flex;justify-content:space-between;margin-top:22px}
.gv-ea-hp{position:absolute;left:-9999px}
</style>';
    $js = '<script>(function(){
var form=document.querySelector(".gv-ea-form");if(!form)return;
form.classList.add("gv-ea-js");
var steps=[].slice.call(form.querySelectorAll(".gv-ea-step")),cur=0;
function show(i){steps.forEach(function(s,j){s.classList.toggle("gv-ea-active",j===i)});cur=i;
  if(i>0)steps[i].scrollIntoView({behavior:"smooth",block:"start"});}
function stepValid(i){var ok=true;
  steps[i].querySelectorAll("input,textarea").forEach(function(el){
    if(!el.checkValidity()){el.reportValidity();ok=false;}});
  return ok;}
form.addEventListener("click",function(e){
  if(e.target.hasAttribute("data-gv-ea-next")){if(stepValid(cur))show(cur+1);}
  if(e.target.hasAttribute("data-gv-ea-prev"))show(cur-1);});
form.querySelectorAll(".gv-ea-group[data-max]").forEach(function(g){
  var max=parseInt(g.getAttribute("data-max"),10);
  g.addEventListener("change",function(){
    var on=g.querySelectorAll("input:checked").length;
    g.querySelectorAll("input:not(:checked)").forEach(function(cb){cb.disabled=on>=max;});});});
var firstErr=form.querySelector(".gv-ea-error");
show(firstErr?steps.indexOf(firstErr.closest(".gv-ea-step")):0);
})();</script>';
    return $css . $js;
}

/* ---------------- Shortcode ---------------- */
add_shortcode('gv_elite_application', 'gv_ea_shortcode');
function gv_ea_shortcode() {
    if (isset($_GET['submitted'])) {
        return '<div class="gv-ea-step" style="max-width:680px;margin:0 auto;text-align:center;">'
             . '<h3 class="gv-ea-step__title">Application Received</h3>'
             . '<p>Thank you — your application to GV Elite Performance has been submitted. Every application is personally reviewed by Coach Gino and the coaching staff. We\'ll be in touch by email.</p></div>';
    }
    $values = isset($GLOBALS['gv_ea_values']) ? $GLOBALS['gv_ea_values'] : array();
    $errors = isset($GLOBALS['gv_ea_errors']) ? $GLOBALS['gv_ea_errors'] : array();
    return gv_ea_render_form($values, $errors);
}
```

- [ ] **Step 4: Run tests**

```bash
php build/mu-plugins/tests/test-gv-elite-application.php
```
Expected: `ALL PASS`.

- [ ] **Step 5: Commit**

```bash
git add build/mu-plugins/gv-elite-application.php build/mu-plugins/tests/test-gv-elite-application.php
git commit -m "feat: elite application form rendering, stepper, shortcode"
```

---

### Task 4: Submission handling + CPT storage + admin list

**Files:**
- Modify: `build/mu-plugins/gv-elite-application.php` (append)
- Test: `build/mu-plugins/tests/test-gv-elite-application.php` (append)

**Interfaces:**
- Consumes: `gv_ea_collect()`, `gv_ea_validate()`, `gv_ea_age_from_dob()`; `gv_rf_verify_turnstile()` (runtime only).
- Produces:
  - `gv_ea_post_title(array $values, ?DateTime $today = null): string` — e.g. `Miguel Santos — Age 14`.
  - `gv_ea_store(array $values): int` — inserts `gv_application` post, meta keys `_gv_ea_<fieldKey>` (+ `_gv_ea_age`), returns post ID.
  - `gv_ea_maybe_handle()` on `template_redirect`; on success redirects to `?submitted=1`; on failure sets `$GLOBALS['gv_ea_errors']` / `$GLOBALS['gv_ea_values']` (consumed by `gv_ea_shortcode()`).
  - CPT `gv_application`, admin menu **Elite Applications**.

- [ ] **Step 1: Write the failing tests (append above the exit block)**

```php
// --- gv_ea_post_title() ---
$today2 = new DateTime('2026-07-10', new DateTimeZone('Asia/Manila'));
check('post title is name + derived age',
    gv_ea_post_title(array('full_name' => 'Miguel Santos', 'dob' => '2012-03-05'), $today2) === 'Miguel Santos — Age 14');
check('post title tolerates bad dob',
    gv_ea_post_title(array('full_name' => 'Miguel Santos', 'dob' => 'nope'), $today2) === 'Miguel Santos');
```

- [ ] **Step 2: Run to verify failure**

```bash
php build/mu-plugins/tests/test-gv-elite-application.php
```
Expected: fatal `Call to undefined function gv_ea_post_title()`.

- [ ] **Step 3: Implement (append to `gv-elite-application.php`)**

```php
/* ---------------- CPT ---------------- */
add_action('init', 'gv_ea_register_cpt');
function gv_ea_register_cpt() {
    register_post_type('gv_application', array(
        'labels' => array('name' => 'Elite Applications', 'singular_name' => 'Elite Application',
            'menu_name' => 'Elite Applications', 'edit_item' => 'Review Application'),
        'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-clipboard',
        'supports' => array('title'), 'capability_type' => 'post', 'map_meta_cap' => true,
        'capabilities' => array('create_posts' => 'do_not_allow'),
    ));
}

function gv_ea_post_title($values, $today = null) {
    $name = isset($values['full_name']) ? $values['full_name'] : '';
    $age  = gv_ea_age_from_dob(isset($values['dob']) ? $values['dob'] : '', $today);
    return $age === null ? $name : $name . ' — Age ' . $age;
}

function gv_ea_store($values) {
    $post_id = wp_insert_post(array(
        'post_type' => 'gv_application', 'post_status' => 'private',
        'post_title' => gv_ea_post_title($values),
    ), true);
    if (is_wp_error($post_id)) return 0;
    foreach ($values as $key => $val) {
        update_post_meta($post_id, '_gv_ea_' . $key, is_array($val) ? implode(', ', $val) : $val);
    }
    $age = gv_ea_age_from_dob(isset($values['dob']) ? $values['dob'] : '');
    if ($age !== null) update_post_meta($post_id, '_gv_ea_age', $age);
    return (int) $post_id;
}

/* ---------------- Request handling on /apply/ ---------------- */
add_action('template_redirect', 'gv_ea_maybe_handle');
function gv_ea_maybe_handle() {
    if (empty($_POST['gv_ea_submit'])) return;
    if (!is_page('apply')) return;
    if (!isset($_POST['gv_ea_nonce']) || !wp_verify_nonce($_POST['gv_ea_nonce'], 'gv_ea_apply')) {
        $GLOBALS['gv_ea_errors'] = array('_form' => 'verify');
        $GLOBALS['gv_ea_values'] = gv_ea_collect(wp_unslash($_POST));
        return;
    }
    if (!empty($_POST['gv_ea_website'])) { // honeypot: pretend success
        wp_safe_redirect(add_query_arg('submitted', '1', get_permalink())); exit;
    }
    $token = isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : '';
    $ip    = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if (!gv_rf_verify_turnstile($token, $ip)) {
        $GLOBALS['gv_ea_errors'] = array('_form' => 'verify');
        $GLOBALS['gv_ea_values'] = gv_ea_collect(wp_unslash($_POST));
        return;
    }
    $values = gv_ea_collect(wp_unslash($_POST));
    $errors = gv_ea_validate($values);
    if ($errors) {
        $GLOBALS['gv_ea_errors'] = $errors;
        $GLOBALS['gv_ea_values'] = $values;
        return;
    }
    $post_id = gv_ea_store($values);              // store FIRST — mail failure must not lose the application
    gv_ea_send_emails($values, $post_id);         // defined in Task 5
    wp_safe_redirect(add_query_arg('submitted', '1', get_permalink())); exit;
}

/* ---------------- Admin list columns + read-only detail ---------------- */
add_filter('manage_gv_application_posts_columns', 'gv_ea_admin_columns');
function gv_ea_admin_columns($cols) {
    return array('cb' => isset($cols['cb']) ? $cols['cb'] : '', 'title' => 'Athlete',
        'gv_level' => 'Level', 'gv_parent' => 'Parent Email', 'gv_video' => 'Video', 'date' => 'Submitted');
}
add_action('manage_gv_application_posts_custom_column', 'gv_ea_admin_column_value', 10, 2);
function gv_ea_admin_column_value($col, $post_id) {
    $secs = gv_ea_sections();
    if ($col === 'gv_level') {
        $v = get_post_meta($post_id, '_gv_ea_level', true);
        echo esc_html(isset($secs['background']['fields']['level']['options'][$v]) ? $secs['background']['fields']['level']['options'][$v] : $v);
    } elseif ($col === 'gv_parent') {
        echo esc_html(get_post_meta($post_id, '_gv_ea_parent_email', true));
    } elseif ($col === 'gv_video') {
        $u = get_post_meta($post_id, '_gv_ea_video_url', true);
        if ($u) echo '<a href="' . esc_url($u) . '" target="_blank" rel="noopener">Watch</a>';
    }
}
add_action('add_meta_boxes_gv_application', 'gv_ea_add_meta_box');
function gv_ea_add_meta_box() {
    add_meta_box('gv_ea_detail', 'Application Answers', 'gv_ea_render_meta_box', 'gv_application', 'normal', 'high');
}
function gv_ea_render_meta_box($post) {
    echo '<table class="widefat striped">';
    foreach (gv_ea_sections() as $sec) {
        echo '<tr><th colspan="2" style="background:#f0f0f1;"><strong>' . esc_html($sec['title']) . '</strong></th></tr>';
        foreach ($sec['fields'] as $key => $f) {
            $raw = get_post_meta($post->ID, '_gv_ea_' . $key, true);
            $disp = $raw;
            if ($f['type'] === 'radio' && isset($f['options'][$raw])) $disp = $f['options'][$raw];
            if ($f['type'] === 'checkbox' && $raw !== '') {
                $labels = array();
                foreach (explode(', ', $raw) as $v) $labels[] = isset($f['options'][$v]) ? $f['options'][$v] : $v;
                $disp = implode(', ', $labels);
            }
            if ($f['type'] === 'confirm') $disp = $raw === '1' ? 'Confirmed' : '—';
            if ($f['type'] === 'url' && $disp) $disp = '<a href="' . esc_url($disp) . '" target="_blank" rel="noopener">' . esc_html($disp) . '</a>';
            else $disp = nl2br(esc_html($disp));
            echo '<tr><td style="width:38%;">' . esc_html($f['label']) . '</td><td>' . $disp . '</td></tr>';
        }
    }
    echo '</table>';
}
```

Also update `gv_ea_render_form()`'s summary line so the `_form => verify` error shows a friendlier message — replace the `$summary` assignment with:

```php
    $summary = '';
    if (isset($errors['_form'])) {
        $summary = '<div class="gv-ea-summary" role="alert">We couldn\'t verify your submission — please try again.</div>';
        unset($errors['_form']);
    } elseif ($errors) {
        $summary = '<div class="gv-ea-summary" role="alert">Please review the highlighted answers below and resubmit.</div>';
    }
```

(Note: `unset` needs `$errors` used after — pass the cleaned `$errors` into the field loop; simplest is to do this at the top of `gv_ea_render_form()` before building `$steps`.)

- [ ] **Step 4: Run tests + full regression**

```bash
for t in build/mu-plugins/tests/test-*.php; do echo "== $t"; php "$t" || break; done
```
Expected: every suite `ALL PASS` / zero `FAIL`.

- [ ] **Step 5: Commit**

```bash
git add build/mu-plugins/gv-elite-application.php build/mu-plugins/tests/test-gv-elite-application.php
git commit -m "feat: elite application submission handling, gv_application CPT, admin review UI"
```

---

### Task 5: Branded emails — coach alert + applicant auto-reply

**Files:**
- Modify: `build/mu-plugins/gv-elite-application.php` (append)
- Test: `build/mu-plugins/tests/test-gv-elite-application.php` (append)

**Interfaces:**
- Consumes: `gv_ea_sections()`, `gv_ea_post_title()`; `gv_rf_email_shell($heading, $intro, $inner)` and `GV_RF_RECIPIENT` from `gv-request-form.php` (loaded by the time hooks run; in tests, required first).
- Produces:
  - `gv_ea_email_rows(array $values): string` — section-grouped HTML table rows with human-readable option labels.
  - `gv_ea_admin_email(array $values, int $post_id): array{subject: string, body: string}`
  - `gv_ea_applicant_email(array $values): array{subject: string, body: string}`
  - `gv_ea_send_emails(array $values, int $post_id): void` — called by Task 4's handler.

- [ ] **Step 1: Write the failing tests (append above the exit block)**

```php
// --- emails ---
$vals = gv_ea_collect(gv_ea_valid_input());
$rows = gv_ea_email_rows($vals);
check('email rows humanize option values',
    strpos($rows, 'School Team') !== false && strpos($rows, '4–6 years') !== false
    && strpos($rows, 'school_team') === false);
check('email rows include section headings and video link',
    strpos($rows, 'Basketball Background') !== false && strpos($rows, 'https://youtu.be/abc123') !== false);

$admin = gv_ea_admin_email($vals, 123);
check('admin email subject names athlete',
    strpos($admin['subject'], 'Miguel Santos') !== false
    && strpos($admin['subject'], 'GV Elite Performance') !== false);
check('admin email body is branded shell with review link',
    strpos($admin['body'], 'gv-logo-crest.png') !== false
    && strpos($admin['body'], 'post=123') !== false);

$reply = gv_ea_applicant_email($vals);
check('applicant reply restates selective review',
    stripos($reply['body'], 'character, coachability, commitment, and potential') !== false);
check('applicant reply is branded', strpos($reply['body'], 'gv-logo-crest.png') !== false);
```

Also add these stubs at the top of the test file with the other stubs:

```php
function admin_url($p=''){ return 'https://example.test/wp-admin/'.$p; }
function wp_json_encode($d){ return json_encode($d); }
```

- [ ] **Step 2: Run to verify failure**

```bash
php build/mu-plugins/tests/test-gv-elite-application.php
```
Expected: fatal `Call to undefined function gv_ea_email_rows()`.

- [ ] **Step 3: Implement (append to `gv-elite-application.php`)**

```php
/* ---------------- Emails (reuse gv_rf_email_shell + GV_RF_RECIPIENT) ---------------- */
function gv_ea_email_rows($values) {
    $navy = '#123B78'; $steel = '#6B6F76';
    $out = '';
    foreach (gv_ea_sections() as $sec) {
        $out .= '<tr><td colspan="2" style="padding:14px 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:' . $navy . ';">' . esc_html($sec['title']) . '</td></tr>';
        foreach ($sec['fields'] as $key => $f) {
            $raw = isset($values[$key]) ? $values[$key] : '';
            if ($f['type'] === 'checkbox') {
                $labels = array();
                foreach ((array) $raw as $v) $labels[] = isset($f['options'][$v]) ? $f['options'][$v] : $v;
                $disp = esc_html(implode(', ', $labels));
            } elseif ($f['type'] === 'radio') {
                $disp = esc_html(isset($f['options'][$raw]) ? $f['options'][$raw] : $raw);
            } elseif ($f['type'] === 'confirm') {
                $disp = $raw === '1' ? 'Confirmed' : '—';
            } elseif ($f['type'] === 'url' && $raw !== '') {
                $disp = '<a href="' . esc_url($raw) . '" style="color:' . $navy . ';">' . esc_html($raw) . '</a>';
            } else {
                $disp = nl2br(esc_html($raw));
            }
            if ($disp === '') $disp = '—';
            $out .= '<tr><td style="padding:6px 12px 6px 0;vertical-align:top;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:' . $steel . ';width:42%;">' . esc_html($f['label']) . '</td>'
                  . '<td style="padding:6px 0;vertical-align:top;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1C1C1E;">' . $disp . '</td></tr>';
        }
    }
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $out . '</table>';
}

function gv_ea_admin_email($values, $post_id) {
    $title  = gv_ea_post_title($values);
    $review = admin_url('post.php?post=' . (int) $post_id . '&action=edit');
    $inner  = gv_ea_email_rows($values)
            . '<p style="margin:20px 0 0;text-align:center;"><a href="' . esc_url($review) . '" style="display:inline-block;background:#F47B20;color:#fff;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;padding:12px 26px;border-radius:8px;text-decoration:none;">Review in Dashboard</a></p>';
    return array(
        'subject' => 'GV Elite Performance Application — ' . $title,
        'body'    => gv_rf_email_shell('New Elite Application', esc_html($title) . ' has applied to GV Elite Performance.', $inner),
    );
}

function gv_ea_applicant_email($values) {
    $first = trim(strtok(isset($values['parent_name']) ? $values['parent_name'] : '', ' '));
    $inner = '<p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#1C1C1E;">'
           . 'Hi ' . esc_html($first ?: 'there') . ',<br><br>'
           . 'We\'ve received ' . esc_html(isset($values['full_name']) ? $values['full_name'] : 'your athlete') . '\'s application to <strong>GV Elite Performance</strong>. '
           . 'Every application is personally reviewed by Coach Gino and the coaching staff. Athletes are selected based on character, coachability, commitment, and potential — not solely on current basketball ability.<br><br>'
           . 'We\'ll be in touch by email with the next step. No payment is required at this stage.</p>';
    return array(
        'subject' => 'We received your GV Elite Performance application',
        'body'    => gv_rf_email_shell('Application Received', 'Thank you for applying to GV Elite Performance.', $inner),
    );
}

function gv_ea_send_emails($values, $post_id) {
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $admin   = gv_ea_admin_email($values, $post_id);
    $adminH  = $headers;
    if (!empty($values['parent_email'])) $adminH[] = 'Reply-To: ' . $values['parent_email'];
    if (!wp_mail(GV_RF_RECIPIENT, $admin['subject'], $admin['body'], $adminH)) {
        error_log('gv-elite-application: admin email failed for post ' . $post_id);
    }
    if (!empty($values['parent_email'])) {
        $reply = gv_ea_applicant_email($values);
        if (!wp_mail($values['parent_email'], $reply['subject'], $reply['body'], $headers)) {
            error_log('gv-elite-application: applicant email failed for post ' . $post_id);
        }
    }
}
```

- [ ] **Step 4: Run tests + full regression**

```bash
for t in build/mu-plugins/tests/test-*.php; do echo "== $t"; php "$t" || break; done
```
Expected: all suites pass.

- [ ] **Step 5: Commit**

```bash
git add build/mu-plugins/gv-elite-application.php build/mu-plugins/tests/test-gv-elite-application.php
git commit -m "feat: elite application branded coach alert + applicant auto-reply"
```

---

### Task 6: `/apply/` page, footer link, deploy script

**Files:**
- Create: `build/pages/apply.html`
- Create: `build/scripts/deploy-elite-apply.php`
- Modify: `build/templates/footer.html:17-19`

**Interfaces:**
- Consumes: shortcode `[gv_elite_application]` (Task 3); `gv_ensure_page()` / `gv_set_page_html()` from `gv-build.php` (server-side).
- Produces: page slug `apply`, title `Apply — GV Elite Performance`.

- [ ] **Step 1: Create `build/pages/apply.html`**

Copy the hero markup conventions from `build/pages/faq.html` (open it and mirror its outer wrappers/hero classes exactly — hero section, `gv-wrap`, eyebrow/title/lead). Content:

```html
  <!-- HERO (mirror the hero block structure/classes from faq.html, with these texts) -->
  <!--   eyebrow: GV Elite Performance -->
  <!--   title:   Athlete Application -->
  <!--   lead:    A selective development program for committed student-athletes. -->

  <!-- TONE-SETTING STATEMENT -->
  <section class="gv-section">
    <div class="gv-wrap">
      <div class="gv-head-block gv-center" style="max-width:760px;margin-left:auto;margin-right:auto;">
        <p class="gv-lead" style="font-weight:600;color:#123B78;">GV Elite Performance is a selective development program for committed student-athletes. Admission is based on character, coachability, commitment, and potential—not solely on current basketball ability.</p>
      </div>
    </div>
  </section>

  <!-- APPLICATION FORM -->
  <section class="gv-section gv-section--light">
    <div class="gv-wrap">
      [gv_elite_application]
    </div>
  </section>
```

(The comment lines describe the hero; build the real hero HTML by copying faq.html's hero block and swapping the three texts. Do not leave the comments in as a substitute for the hero.)

Verify shortcode expansion: `gv_set_page_html()` stores raw HTML as page content and Elementor/Astra run `the_content`, so `[gv_elite_application]` expands. Confirm after deploy (Task 7 checklist) — if the literal text `[gv_elite_application]` appears on the live page, wrap the shortcode expansion by adding `do_shortcode` handling in `gv-build.php`'s render path per how other dynamic blocks are handled (check `grep -n "do_shortcode" build/mu-plugins/*.php` first).

- [ ] **Step 2: Add the footer Explore link**

In `build/templates/footer.html`, after the Training Programs `<li>` (line 16), insert:

```html
        <li><a href="/apply/">Apply — GV Elite Performance</a></li>
```

- [ ] **Step 3: Create `build/scripts/deploy-elite-apply.php`**

```php
<?php
// Run on host: wp eval-file deploy-elite-apply.php
// Requires apply.html scp'd to $HOME. Creates/updates the /apply/ page.
$id = gv_ensure_page('apply', 'Apply — GV Elite Performance');
if (!$id) { echo "FAILED to ensure apply page\n"; return; }
echo gv_set_page_html($id, file_get_contents(getenv('HOME') . '/apply.html')) . "\n";
echo "apply=$id\n";
```

- [ ] **Step 4: Local sanity check**

```bash
php -l build/scripts/deploy-elite-apply.php && grep -c "gv_elite_application" build/pages/apply.html && grep -n "/apply/" build/templates/footer.html
```
Expected: `No syntax errors`, count `1`, one footer match.

- [ ] **Step 5: Commit**

```bash
git add build/pages/apply.html build/scripts/deploy-elite-apply.php build/templates/footer.html
git commit -m "feat: /apply/ page with elite application shortcode + footer link + deploy script"
```

---

### Task 7: Deploy to Hostinger + live verification

**Files:** none (server operation). Consult `wiki/deployment-workflows.md` for the Golden Workflow, SSH alias, and exact remote paths before running anything — commands below follow its pattern; use the wiki's host alias and mu-plugins path verbatim.

- [ ] **Step 1: Run all tests one final time**

```bash
for t in build/mu-plugins/tests/test-*.php; do echo "== $t"; php "$t" || break; done
```
Expected: all pass. Do not deploy on any FAIL.

- [ ] **Step 2: Upload changed assets** (per `wiki/deployment-workflows.md` — adjust alias/paths to match the wiki)

```bash
scp build/pages/home.html build/pages/training-programs.html build/pages/apply.html <host>:~/pages/  # per wiki
scp build/templates/footer.html <host>:~/
scp build/scripts/deploy-elite-apply.php <host>:~/
scp build/mu-plugins/gv-elite-application.php <host>:<wp-content>/mu-plugins/
```

- [ ] **Step 3: Apply pages + theme parts on host**

```bash
ssh <host> 'cd <wp-root> && wp eval-file ~/deploy-elite-apply.php'
```
Expected output: `updated` (or similar from `gv_set_page_html`) and `apply=<id>`. Record the new page ID.

Re-apply home + training-programs using the existing pattern (`gv_set_page_html(2887, ...)`, `gv_set_page_html(2981, ...)` — reuse/adapt an existing script such as `deploy-refine.php`'s map or `wp eval 'echo gv_set_page_html(...)'`), then redeploy the footer theme part via `deploy-members-theme-parts.php` (needs `header.html` + `footer.html` in `$HOME` — scp the header too).

- [ ] **Step 4: Clear cache** (per wiki: LiteSpeed/Hostinger cache purge command documented in `deployment-workflows.md`)

- [ ] **Step 5: Live verification checklist**

1. `https://gvbasketball.com/` — Elite card reads **GV ELITE PERFORMANCE / Application Required · Limited Enrollment**, button **APPLY NOW** → `/apply/`.
2. `https://gvbasketball.com/training-programs/` — card + detail updated; no "aqua training" anywhere (Cmd-F).
3. Private/Small Group buttons still open the consultation wizard.
4. `/apply/` — hero + tone statement render; form shows Step 1 of 6; shortcode is expanded (no literal `[gv_elite_application]`).
5. Submit a full test application (use `techteam@favor.church` as parent email, video link `https://youtu.be/dQw4w9WgXcQ`, athlete name `TEST DELETE ME`). Expect redirect to `?submitted=1` confirmation.
6. wp-admin → Elite Applications: entry exists, columns populated, detail meta box shows humanized answers.
7. Coach inbox (`gvbasketballcoaching@gmail.com`) received the branded alert with Review link; parent email received the auto-reply.
8. Delete the test application entry from wp-admin.
9. No-JS check: with JS disabled (or via `curl -s https://gvbasketball.com/apply/ | grep -c gv-ea-step` → `6`), all steps render.

- [ ] **Step 6: Commit any deploy-script adjustments made during rollout**

```bash
git add -A build/scripts && git commit -m "chore: deploy scripts for elite application rollout" || echo "nothing to commit"
```

---

### Task 8: Wiki sync + changelog + client status

**Files:**
- Modify: `wiki/forms-and-emails.md` (new section 4), `wiki/pages.md` (add `/apply/` with its page ID), `wiki/architecture.md` (add mu-plugin), `wiki/client-status.md` (new highlight + note parked Part A items), `wiki/log.md` (append entry), `wiki/index.md` (only if a new wiki page were added — none is).

- [ ] **Step 1: `wiki/forms-and-emails.md`** — append:

```markdown
---

## 4. GV Elite Performance Application (`gv-elite-application.php`)

The selective-program application at `/apply/`, rendered by shortcode `[gv_elite_application]` from mu-plugin `gv-elite-application.php` (prefix `gv_ea_`).

- **Structure:** 6 client-side steps (Athlete → Background → Commitment → Character → Video & Parent → Final Commitment). Without JS the form renders as one page and still submits. Age is derived from Date of Birth server-side (no Age field).
- **Video:** URL field only (YouTube/Drive link) — no file uploads by design.
- **Anti-abuse:** nonce (`gv_ea_apply`), honeypot (`gv_ea_website`), Cloudflare Turnstile via `gv_rf_verify_turnstile()`.
- **Storage:** private CPT `gv_application` → wp-admin "Elite Applications" (list columns + read-only answer table). Entries are saved before any email is sent.
- **Emails:** branded shell via `gv_rf_email_shell()` — coach alert to `gvbasketballcoaching@gmail.com` (Reply-To = parent, "Review in Dashboard" button) + applicant auto-reply restating the selective-review framing.
- **Tests:** `php build/mu-plugins/tests/test-gv-elite-application.php`
```

- [ ] **Step 2: `wiki/pages.md`** — add the `/apply/` row (ID from Task 7 Step 3 output) noting it is footer-linked only, not in primary nav.

- [ ] **Step 3: `wiki/architecture.md`** — add `gv-elite-application.php` to the mu-plugins list with one line: reuses `gv-request-form.php` legacy helpers; loads alphabetically before it, so `gv_rf_*` is only called at hook time.

- [ ] **Step 4: `wiki/client-status.md`** — add a Recent Highlight for the GV Elite Performance rebrand + application, and a Pending item: "Part A creative direction (authentic photos, Coach Gino visibility, emotional copy) parked until client sends photography/certifications — requirements in `revisions/2026-07-10-creative-direction-client.md`."

- [ ] **Step 5: `wiki/log.md`** — append:

```markdown
## [2026-07-10] task | GV Elite Performance rebrand + application experience
- **Goal:** Reposition Elite Performance as the selective, application-based GV Elite Performance per client creative direction.
- **Changes:**
  - `build/pages/home.html`, `build/pages/training-programs.html`: card + detail copy (Court Training / Strength & Conditioning / Recovery / Nutrition; "aqua training" removed), APPLY NOW → `/apply/`.
  - New mu-plugin `build/mu-plugins/gv-elite-application.php`: 6-step application, `gv_application` CPT, branded coach + applicant emails, Turnstile/nonce/honeypot; tests in `build/mu-plugins/tests/test-gv-elite-application.php`.
  - New page `/apply/` (`build/pages/apply.html`, deployed via `build/scripts/deploy-elite-apply.php`); footer Explore link added.
  - `build/scripts/setup-latepoint.php`: service renamed/description updated.
  - Client requirements archived in `revisions/2026-07-10-creative-direction-client.md`; spec in `docs/superpowers/specs/`.
```

- [ ] **Step 6: Commit**

```bash
git add wiki/
git commit -m "docs: wiki sync for GV Elite Performance application launch"
```

---

## Self-Review Notes

- **Spec coverage:** §3 copy → Task 1; §4 page → Task 6; §5.1 form → Tasks 2–3; §5.2 processing/storage/emails → Tasks 4–5; §5.3 anti-abuse → Tasks 3–4; §6 error handling → Tasks 3–4 (summary + per-field + value preservation; CPT-before-email in Task 4); §7 testing → per-task TDD + Task 7 live checklist; §8 deploy/docs → Tasks 7–8. Footer link (§4) → Task 6.
- **Known judgment calls:** honeypot hits fake success (don't tip off bots); `improvement_other`/`parent_values_other` are optional free-text companions to the "Other" options; the `_form => verify` pseudo-error renders as a friendly banner.
- **Deploy specifics** (host alias, remote paths, cache purge) intentionally defer to `wiki/deployment-workflows.md` — read it before Task 7; do not guess paths.
