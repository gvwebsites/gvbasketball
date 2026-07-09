# Design: Fix Empty Consultation Modal + Add Location & Day Selection

**Date:** 2026-07-09
**Status:** Approved (design), pending implementation
**Author:** Claude (brainstormed with Rico)

## Problem

Two related problems on the "Book a Consultation" flow:

1. **Bug — empty modal (root cause found).** On the live `/training-programs/`
   page (WordPress page ID `2981`), clicking any "Book a Consultation" button
   opens the modal, but the modal is **empty**.

   Evidence (live HTML fetched 2026-07-09): the only occurrence of
   `gv-rform-wrap` on the page is inside the modal's own JavaScript
   (`document.querySelector('.gv-rform-wrap')`). There are **zero** form
   fields (`parent_name`, `training_type`, `preferred_times`), no "Send
   Request" button, no Turnstile widget, and no literal `[gv_request_form]`
   text.

   The page is deployed as two Elementor blocks — an HTML widget (page markup +
   modal) and a separate `[gv_request_form]` **shortcode** widget. At runtime
   `moveForm()` relocates `.gv-rform-wrap` into the modal slot. The split exists
   because Elementor's HTML widget does not execute shortcodes. **The shortcode
   block is missing from what is currently live**, so there is nothing to move
   and the modal renders empty.

   Contributing risk: `build/scripts/build-functional.php` attaches the
   modal + `[gv_request_form]` block to page `2982` (`/book-a-consultation/`),
   which is 302-redirected to `/training-programs/` by the mu-plugin. Only
   `build/scripts/deploy-training-programs.php` targets the correct live page
   (`2981`).

2. **Feature — the form has no location or structured day selection.** The
   custom request form ([gv-request-form.php](../../../build/mu-plugins/gv-request-form.php))
   collects parent/player/age/email/training-type and a single free-text
   "Preferred days & times" textarea. It has no location field at all, even
   though sessions run at three specific venues on specific days.

Decision (from brainstorming): keep the custom branded request form (not
LatePoint), and add structured location + day selection to it.

## Locations (confirmed correct)

| Location            | Operating days      |
|---------------------|---------------------|
| Dasma, Makati       | Mon, Wed, Thu       |
| Urdaneta Village    | Fri, Sun            |
| Corinthian Gardens  | Sun                 |
| Open to any location| All 7 days (Mon–Sun)|

## Solution

### Part A — Fix the empty modal

1. Redeploy live page `2981` with **both** blocks (HTML widget + shortcode
   widget) using `deploy-training-programs.php`. This restores the form and
   ships the new fields in the same operation.
2. Deploy the updated `gv-request-form.php` mu-plugin.
3. Correct `build/scripts/build-functional.php` so the training-programs
   modal + shortcode block targets page `2981`, not the redirected `2982`, to
   prevent this regression from recurring. (Housekeeping so the two deploy
   paths agree.)

### Part B — Form field changes (`gv-request-form.php`)

Replace the single free-text days/times field with structured fields:

1. **Preferred location** — `select`, **required**. Options:
   - `Dasma, Makati — Mon, Wed & Thu`
   - `Urdaneta Village — Fri & Sun`
   - `Corinthian Gardens — Sun`
   - `Open to any location`
   Values are stable keys (e.g. `dasma`, `urdaneta`, `corinth`, `any`);
   labels show the days.

2. **Preferred day(s)** — checkbox group (`preferred_days[]`), **required**
   (at least one). Options are **conditionally filtered by the chosen
   location** via client-side JS:
   - `dasma` → Mon, Wed, Thu
   - `urdaneta` → Fri, Sun
   - `corinth` → Sun
   - `any` → Mon, Tue, Wed, Thu, Fri, Sat, Sun (all 7)
   When the location changes, re-filter the day checkboxes and **preserve any
   previously-checked days that remain valid** under the new location (do not
   wipe the user's selection unnecessarily). Days that are no longer valid are
   removed/unchecked.

3. **Preferred time of day / notes** — the existing `preferred_times`
   textarea, relabeled and made **optional**. Placeholder: e.g. "after 4pm on
   weekdays".

### Single source of truth for the map

Define the location→days map once, mirrored in:
- **JS** (in the shortcode output) to filter day checkboxes live.
- **PHP** to validate on submit.

### Server-side validation (`gv_rf_handle`)

- `location` must be one of the known keys.
- `preferred_days` must be a non-empty array; **every** submitted day must be
  valid for the chosen location (for `any`, all 7 days are valid). Reject
  otherwise (`err`).
- Existing checks unchanged: nonce, honeypot, Turnstile, name/player/email/age
  bounds, training type in allowed set.

### Emails

Add two rows to the admin notification table and reflect them in the
auto-reply:
- **Preferred location** (human-readable label, e.g. "Dasma, Makati").
- **Preferred day(s)** (comma-joined, e.g. "Mon, Wed").

### Copy change

Modal subtitle in `build/pages/training-programs.html` (currently: *"Tell
Coach Gino about your athlete. We'll follow up to confirm your
consultation."*) → third-person, no mention of Coach Gino:

> "Share a few details about your athlete and the team will follow up to
> confirm the consultation."

Out of scope (not requested): the auto-reply email body still references
"Coach Gino's team" — left unchanged unless Rico asks.

## Sequencing & safety

1. Edit `gv-request-form.php`, `training-programs.html` copy, and
   `build-functional.php` locally — safe, reversible.
2. **Get explicit go-ahead before deploying.** Deployment overwrites page
   `2981`'s Elementor content and is the one hard-to-reverse step.
3. Deploy via the documented flow:
   ```
   scp build/mu-plugins/gv-request-form.php gvweb:.../mu-plugins/
   scp build/scripts/deploy-training-programs.php gvweb:~/
   scp build/pages/training-programs.html gvweb:~/
   ssh gvweb 'cd <docroot> && wp eval-file ~/deploy-training-programs.php \
     && wp elementor flush-css && wp litespeed-purge all \
     && rm ~/deploy-training-programs.php ~/training-programs.html'
   ```
   (Exact paths confirmed at deploy time.)

## Verification (post-deploy)

- Open `/training-programs/`, click "Book a Consultation" → modal shows the
  full form (not empty).
- Change location → day checkboxes re-filter; a previously-checked valid day
  stays checked.
- Submit with a valid location + day(s) → success banner; admin + auto-reply
  emails include location and days.
- Submit tampered/invalid day for a location (via devtools) → server rejects
  with the error banner.

## Non-goals

- No LatePoint integration in the public modal.
- No live calendar / real-time availability.
- No pricing display (remains "shared during your consultation").
