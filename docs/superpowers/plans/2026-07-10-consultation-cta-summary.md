# Consultation CTA and Summary Panel Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the consultation wizard's available-day action read `BOOK A CONSULTATION`, style it as a centered rounded GV button, and remove the unused LatePoint Summary panel.

**Architecture:** Keep the native LatePoint booking flow and its internal nominal start time unchanged. Add LatePoint's supported `hide_summary="yes"` option at the hidden trigger source, then use the existing post-render UI pass to add a namespaced action class and visible copy. Keep styling in the existing GV Members asset stylesheet and guard the contract with the current framework-free PHP test.

**Tech Stack:** WordPress/PHP must-use plugin, LatePoint 5.6.6 shortcode attributes, jQuery UI post-render hook, CSS, framework-free PHP contract tests.

---

## File Map

- Modify `build/mu-plugins/tests/test-gv-members-contracts.php` — static and rendered-markup contracts for the four consultation trigger variants plus the JS/CSS presentation hooks.
- Modify `build/mu-plugins/gv-members.php` — add `hide_summary="yes"` to the per-venue, any-venue, and no-location hidden LatePoint triggers.
- Modify `build/mu-plugins/gv-members/assets/gv-members.js` — centralize the visible label, add the action class, and keep all visible slot-time rewrites consistent.
- Modify `build/mu-plugins/gv-members/assets/gv-members.css` — center and brand the available-day action with rounded edges and focus/hover states.
- Modify `wiki/booking-latepoint.md` — document that the modal hides both the side panel and Summary panel, and that available days use the consultation CTA label.
- Modify `wiki/log.md` — append the required completion entry after implementation and verification.

## Task 1: Add failing regression contracts

**Files:**
- Modify: `build/mu-plugins/tests/test-gv-members-contracts.php:682-702`
- Test: `build/mu-plugins/tests/test-gv-members-contracts.php`

- [ ] **Step 1: Add trigger, JS, and CSS assertions before implementation.**

Insert after the existing venue-trigger assertions:

```php
$members_plugin = file_get_contents(__DIR__ . '/../gv-members.php');
gv_assert_same(3, substr_count($members_plugin, 'hide_summary="yes"'), 'all consultation trigger variants define hidden Summary');
gv_assert_same(4, substr_count($trigger_html, 'hide_summary="yes"'), 'all consultation triggers hide the Summary panel');

$members_asset_dir = __DIR__ . '/../gv-members/assets';
$members_js = file_get_contents($members_asset_dir . '/gv-members.js');
$members_css = file_get_contents($members_asset_dir . '/gv-members.css');
gv_assert_contains("BOOK A CONSULTATION", $members_js, 'wizard uses the consultation CTA label');
gv_assert_contains("gv-consult-day-action", $members_js, 'wizard adds the namespaced consultation action class');
gv_assert_contains(".gv-consult-day-action", $members_css, 'consultation action has dedicated CSS');
gv_assert_contains("justify-content: center", $members_css, 'consultation action is centered');
gv_assert_contains("border-radius: 10px", $members_css, 'consultation action has rounded edges');
```

- [ ] **Step 2: Run the contract test and confirm the new assertions fail for the missing behavior.**

Run:

```bash
php build/mu-plugins/tests/test-gv-members-contracts.php
```

Expected: the existing contracts pass, while the new Summary/CTA/style assertions report `FAIL`. The test must execute normally; an include or PHP syntax error is not an acceptable red state.

- [ ] **Step 3: Commit the red test.**

```bash
git add build/mu-plugins/tests/test-gv-members-contracts.php
git commit -m "test: cover consultation CTA and hidden summary"
```

## Task 2: Hide the native Summary panel at the trigger source

**Files:**
- Modify: `build/mu-plugins/gv-members.php:145-159`
- Test: `build/mu-plugins/tests/test-gv-members-contracts.php`

- [ ] **Step 1: Add the native LatePoint attribute to every trigger variant.**

Update each shortcode string in `gv_members_hidden_booking_trigger()` so the existing side-panel option is followed by the Summary option:

```php
do_shortcode('[latepoint_book_button caption="Book a Consultation" selected_service="' . (int) $service->id . '" selected_location="' . (int) $location->id . '" hide_side_panel="yes" hide_summary="yes"]')
```

Use the same `hide_summary="yes"` suffix for the `selected_location="any"` trigger and the no-location fallback trigger. Do not change service, location, or booking data attributes.

Update the nearby availability comment from `Request this day` to `BOOK A CONSULTATION` so the source comments describe the public action accurately.

- [ ] **Step 2: Run the contract test and confirm the Summary contract now passes while the CTA/style contracts remain red.**

Run:

```bash
php build/mu-plugins/tests/test-gv-members-contracts.php
```

Expected: `all consultation triggers hide the Summary panel` is `ok`; the JS/CSS contracts remain `FAIL` until Tasks 3 and 4.

- [ ] **Step 3: Commit the trigger change.**

```bash
git add build/mu-plugins/gv-members.php
git commit -m "feat: hide consultation summary panel"
```

## Task 3: Relabel available-day actions in the existing UI pass

**Files:**
- Modify: `build/mu-plugins/gv-members/assets/gv-members.js:76-99`
- Test: `build/mu-plugins/tests/test-gv-members-contracts.php`

- [ ] **Step 1: Define one visible-label constant and apply it to rendered slot text.**

In the existing Theme day-only selection section, add:

```js
var GV_CONSULTATION_ACTION_LABEL = 'BOOK A CONSULTATION';
```

Then replace the slot rewrite block with:

```js
$('.timeslots .dp-timebox').each(function() {
    var $box = $(this);
    var $time = $box.find('.dp-label-time');
    if ($time.length) {
        $time.addClass('gv-consult-day-action');
        if ($time.text().trim() !== GV_CONSULTATION_ACTION_LABEL) {
            $time.text(GV_CONSULTATION_ACTION_LABEL);
        }
    }
    // The compact tick label leaks the nominal time; keep it hidden.
    $box.find('.dp-tick').css('visibility', 'hidden');
});
```

Use the same constant in the selected-slot rewrite so any still-visible LatePoint echo does not expose `Request this day`:

```js
$('.summary-item-time, .os-selected-slot, .dp-selected-time').each(function() {
    var $this = $(this);
    if ($this.text().trim() !== '' && $this.text().trim() !== GV_CONSULTATION_ACTION_LABEL) {
        $this.text(GV_CONSULTATION_ACTION_LABEL);
    }
});
```

Update the nearby comments from `Request this day` to the new CTA wording. Do not change `data-minutes`, `start_time`, or the `.sbc-highlighted-item` date-only cleanup.

- [ ] **Step 2: Run the contract test and confirm the JS contracts pass while the CSS contracts remain red.**

Run:

```bash
php build/mu-plugins/tests/test-gv-members-contracts.php
```

Expected: the visible-copy and namespaced-class assertions are `ok`; the CSS assertions remain `FAIL` until Task 4.

- [ ] **Step 3: Commit the UI-label change.**

```bash
git add build/mu-plugins/gv-members/assets/gv-members.js
git commit -m "feat: relabel consultation day actions"
```

## Task 4: Style the CTA as a centered rounded button

**Files:**
- Modify: `build/mu-plugins/gv-members/assets/gv-members.css:602-615`
- Test: `build/mu-plugins/tests/test-gv-members-contracts.php`

- [ ] **Step 1: Add scoped presentation rules after the existing LatePoint pricing rules.**

Add:

```css
/* ==================== Consultation day action ==================== */
/* The visible action requests a day; the exact 45-minute time is coordinated later. */

.timeslots .dp-label {
    display: flex;
    justify-content: center;
}

.timeslots .dp-label .gv-consult-day-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: calc(100% - 16px);
    min-height: 52px;
    margin: 4px 8px;
    padding: 12px 18px;
    border-radius: 10px;
    background: var(--gv-brand-orange);
    color: var(--gv-brand-white);
    font-size: 15px;
    font-weight: 800;
    line-height: 1.2;
    text-align: center;
    box-sizing: border-box;
}

.timeslots .dp-timebox:hover .gv-consult-day-action,
.timeslots .dp-timebox:focus-within .gv-consult-day-action {
    background: #D9630C;
}
```

The action remains inside LatePoint's clickable `.dp-timebox`; the new class is presentational only. Do not remove the existing global focus rules or pricing suppression.

- [ ] **Step 2: Run the contract test and confirm all new contracts pass.**

Run:

```bash
php build/mu-plugins/tests/test-gv-members-contracts.php
```

Expected: all trigger, label, class, centering, and rounded-edge assertions are `ok`, with no new `FAIL` output.

- [ ] **Step 3: Commit the styling change.**

```bash
git add build/mu-plugins/gv-members/assets/gv-members.css
git commit -m "style: emphasize consultation day CTA"
```

## Task 5: Synchronize project documentation

**Files:**
- Modify: `wiki/booking-latepoint.md:70-78`
- Modify: `wiki/log.md` (append at EOF only after verification)

- [ ] **Step 1: Update the booking-flow documentation.**

Change the trigger description to say all modal triggers use both `hide_side_panel="yes"` and `hide_summary="yes"`. Change the day-selection description to say the wizard shows one `BOOK A CONSULTATION` action per available day and that the exact time is still coordinated later.

- [ ] **Step 2: Run a documentation consistency scan.**

Run:

```bash
rg -n "Request this day|hide_side_panel|hide_summary|BOOK A CONSULTATION" wiki/booking-latepoint.md build/mu-plugins/gv-members.php build/mu-plugins/gv-members/assets/gv-members.js
```

Expected: the active booking-flow documentation and implementation use the new label and native Summary option. Historical plan/spec files may retain the old phrase as prior design context; do not rewrite those records.

- [ ] **Step 3: Append the required changelog entry.**

Append:

```markdown
## [2026-07-10] task | Consultation CTA and Summary cleanup
- **Goal:** Make the available-day consultation action more obvious and remove the unused LatePoint Summary panel.
- **Changes:**
  - Added LatePoint `hide_summary="yes"` to all modal consultation triggers.
  - Relabeled the visible day action to `BOOK A CONSULTATION` and styled it as a centered, rounded GV orange button.
  - Added regression contracts and synchronized `booking-latepoint.md`.
```

- [ ] **Step 4: Commit the documentation changes.**

```bash
git add wiki/booking-latepoint.md wiki/log.md
git commit -m "docs: record consultation CTA cleanup"
```

## Task 6: Verify the complete change

**Files:**
- Test: `build/mu-plugins/tests/test-*.php`
- Review: all files listed in the File Map

- [ ] **Step 1: Run PHP syntax checks.**

Run:

```bash
php -l build/mu-plugins/gv-members.php
php -l build/mu-plugins/tests/test-gv-members-contracts.php
```

Expected: both commands report `No syntax errors detected`.

- [ ] **Step 2: Run the targeted contract test.**

Run:

```bash
php build/mu-plugins/tests/test-gv-members-contracts.php
```

Expected: exit code 0 and no `FAIL -` lines.

- [ ] **Step 3: Run the complete framework-free GV Members suite.**

Run:

```bash
for t in build/mu-plugins/tests/test-*.php; do php "$t"; done
```

Expected: every test process exits 0.

- [ ] **Step 4: Check the final diff and worktree scope.**

Run:

```bash
git diff --check
git status --short
git show --stat --oneline --decorate -5
```

Expected: no whitespace errors; the recent commits contain only the consultation CTA/Summary work and documentation, while the pre-existing unrelated modifications remain preserved and unstaged.
