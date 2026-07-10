# Minimal Edits Round Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the client-approved copy edits (GV Elite Performance rebrand, Apply Now label, pricing-copy removal), a one-screen GV Elite Academy "Coming 2027" page with a nav tab, and a JULY-v2 client report covering only this round.

**Architecture:** Pure content round — no new mu-plugins, forms, CPTs, or emails. Page HTML lives in `build/pages/*.html`, header/footer chrome in `build/templates/`, and everything is pushed with `gv_set_page_html()` / theme-part scripts via `wp eval-file` (Golden Workflow, `wiki/deployment-workflows.md`). The client report follows the existing `docs/CLIENT-REPORT-JULY.html` convention (styled per `docs/report.template.html`, uploaded to `public_html/`).

**Tech Stack:** Static HTML edits, WP-CLI over SSH, existing framework-free CLI test suites (regression only), Chrome screenshots for the report.

**Spec:** `docs/superpowers/specs/2026-07-10-minimal-edits-design.md`. Copy is client-final — verbatim.

## Global Constraints

- Program name exactly **GV Elite Performance**; kicker exactly **Application Required · Limited Enrollment**; bullets exactly **Court Training / Strength & Conditioning / Recovery / Nutrition**; "aqua training" must not survive anywhere in `build/`.
- **Apply Now is a label-only change**: the anchors keep `href="#" role="button" data-gv-consultation` byte-identical — only the inner text changes. Every non-Elite "Book a Consultation" button stays completely untouched (including the header CTA).
- Pricing must no longer be discussed in site copy; the functional price-hiding CSS (`gv-members.css:602-613`) and LatePoint `price_min/price_max` config are NOT touched.
- No new PHP logic anywhere → no new test files; all existing suites must stay green: `for t in build/mu-plugins/tests/test-*.php; do php "$t" || break; done`
- Deploy specifics (SSH alias, remote paths, cache purge) come from `wiki/deployment-workflows.md` — read it before Task 4; never guess paths.
- Academy accent color is gold `#C9A24B`, not orange.

---

### Task 1: GV Elite Performance copy edits

**Files:**
- Modify: `build/pages/home.html:81-87`
- Modify: `build/pages/training-programs.html:43-53`, `:118-146`, `:154`
- Modify: `build/pages/faq.html:47` (program-difference answer)
- Modify: `build/scripts/setup-latepoint.php:50`

**Interfaces:**
- Produces: Elite CTAs labeled "Apply Now" still wired to the consultation wizard via `data-gv-consultation`.

- [ ] **Step 1: Home page card** — in `build/pages/home.html`, replace lines 81–87 with:

```html
        <div class="gv-program">
          <div class="gv-program__head"><div class="gv-program__ic"><svg viewBox="0 0 24 24"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg></div><h3 class="gv-program__name">GV Elite Performance</h3><div class="gv-program__for">Application Required · Limited Enrollment</div></div>
          <div class="gv-program__body">
            <p>The complete performance system for aspiring elite athletes — court training, strength &amp; conditioning, recovery, and nutrition.</p>
            <div class="gv-program__foot"><a class="gv-btn gv-btn--primary" href="#" role="button" data-gv-consultation>Apply Now</a></div>
          </div>
        </div>
```

- [ ] **Step 2: Training-programs card** — replace lines 43–53 with:

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
            <div class="gv-program__foot"><a class="gv-btn gv-btn--primary" href="#" role="button" data-gv-consultation>Apply Now</a></div>
          </div>
        </div>
```

- [ ] **Step 3: Training-programs detail section** — inside `<!-- ELITE PERFORMANCE DETAIL -->` (lines 118–146), keep the `<img>` media block and replace the text column (`<h2>` through the closing `.gv-btn-row` div) with:

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
          <p>Application required. Limited enrollment. Start with a consultation.</p>
          <div class="gv-btn-row" style="margin-top:28px;">
            <a class="gv-btn gv-btn--primary" href="#" role="button" data-gv-consultation>Apply Now</a>
          </div>
```

Then update line ~154's locations lead to:

```html
        <p class="gv-lead">Small-group sessions run across Metro Manila. Private training is scheduled by appointment; GV Elite Performance is by application.</p>
```

- [ ] **Step 4: FAQ program-difference answer** — in `build/pages/faq.html:47`, update the `<strong>Elite Performance:</strong>` sentence within the "What's the difference…" answer to:

```html
<strong>GV Elite Performance:</strong> the complete performance system for serious, competitive players — court training, strength &amp; conditioning, recovery, and nutrition. Application required.
```

Also on line 49 ("Which program should my child join?"), change "may benefit from Elite Performance" → "may benefit from GV Elite Performance".

- [ ] **Step 5: LatePoint service** — `build/scripts/setup-latepoint.php:50`:

```php
$elite   = gv_svc('GV Elite Performance', 90, 1, 6, 'The complete performance system: court training, strength & conditioning, recovery, and nutrition. Application required.');
```

- [ ] **Step 6: Verify**

```bash
grep -rni "aqua" build/; echo "aqua-exit=$?"
grep -c "data-gv-consultation" build/pages/home.html build/pages/training-programs.html
grep -c "Apply Now" build/pages/home.html build/pages/training-programs.html
for t in build/mu-plugins/tests/test-*.php; do echo "== $t"; php "$t" || break; done
```
Expected: `aqua-exit=1`; `data-gv-consultation` counts **unchanged from before the edit** (home: 3 — two cards + hero/CTA usage as currently present, programs: 3 — verify against `git show HEAD` counts, they must be equal); `Apply Now` count 1 in home, 2 in training-programs; all tests pass.

- [ ] **Step 7: Commit**

```bash
git add build/pages/home.html build/pages/training-programs.html build/pages/faq.html build/scripts/setup-latepoint.php
git commit -m "feat: GV Elite Performance rebrand copy with Apply Now label (consultation wizard unchanged)"
```

---

### Task 2: Remove pricing mentions from copy

**Files:**
- Modify: `build/pages/faq.html:12` (hero lead), `:72` ("Programs & Costs" heading), `:50` (cost accordion item)
- Modify: `build/scripts/build-functional.php:74`

- [ ] **Step 1: Delete the cost FAQ item** — in `build/pages/faq.html`, remove line 50 entirely (the `<details>` item "How much does training cost?" including its full answer).

- [ ] **Step 2: De-price the FAQ frame** —
  - Hero lead (line ~12): change to `…about training at GV Basketball — ages, schedules, programs, and how to get started.` (drop "costs,").
  - Section heading (line ~72): `<h2 class="gv-section-title">Programs &amp; Costs</h2>` → `<h2 class="gv-section-title">Programs</h2>`.

- [ ] **Step 3: Booking blurb** — in `build/scripts/build-functional.php:74`, change the lead sentence to end at confirmation:

```
…<p class="gv-lead">Send us a few details and your preferred days and times. Coach Gino's team will follow up to confirm.</p>…
```

(Remove exactly the ` — pricing is shared during your consultation` clause; everything else on the line stays.)

- [ ] **Step 4: Sweep**

```bash
grep -rni "pricing\|no hidden fees\|how much does training cost" build/pages/ build/scripts/build-functional.php; echo "exit=$?"
```
Expected: `exit=1` (no matches). (`gv-members.css` price-hiding rules and `setup-latepoint.php` `price_min/price_max` are intentionally out of scope — do not "fix" them.)

- [ ] **Step 5: Commit**

```bash
git add build/pages/faq.html build/scripts/build-functional.php
git commit -m "feat: remove pricing mentions from site copy per client request"
```

---

### Task 3: GV Elite Academy teaser page + navigation

**Files:**
- Create: `build/pages/elite-academy.html`
- Create: `build/scripts/deploy-minimal-edits.php`
- Modify: `build/templates/header.html` (nav menu), `build/templates/footer.html:17-19` (Explore list)
- Modify: `build/scripts/build-menu.php` (WP menu mirror)

**Interfaces:**
- Consumes: `gv_ensure_page()` / `gv_set_page_html()` (server-side, from `gv-build.php`).
- Produces: page slug `elite-academy`, title `GV Elite Academy`.

- [ ] **Step 1: Create `build/pages/elite-academy.html`**

First copy the current hero `background-image` URL from `build/pages/home.html`'s `.gv-hero__bg` (a generic no-people b-roll shot, per the hero convention) — call it `<HOME_HERO_URL>` below. Then create:

```html
<div class="gv-page">

  <!-- GV ELITE ACADEMY — COMING 2027 (single-screen teaser) -->
  <section class="gv-hero" style="min-height:92vh;display:flex;align-items:center;">
    <div class="gv-hero__bg" style="background-image:url('<HOME_HERO_URL>');"></div>
    <div class="gv-hero__overlay" style="background:linear-gradient(180deg,rgba(2,15,38,.88) 0%,rgba(2,15,38,.95) 100%);"></div>
    <div class="gv-wrap">
      <div class="gv-hero__inner" style="padding:110px 0;text-align:center;max-width:840px;margin:0 auto;">
        <span class="gv-eyebrow" style="color:#C9A24B;letter-spacing:.26em;">Coming 2027</span>
        <h1 class="gv-h1" style="letter-spacing:.05em;">GV Elite Academy</h1>
        <div class="gv-hero__rule" style="margin:26px auto;background:#C9A24B;"></div>
        <p class="gv-lead" style="font-size:1.3rem;color:#ffffff;font-weight:600;">Develop Better People. Not Just Better Basketball Players.</p>
        <p class="gv-lead" style="max-width:660px;margin:16px auto 0;">The Philippines&rsquo; future premier residential basketball academy for student-athletes committed to excellence in character, leadership, and performance.</p>
        <p style="margin:48px 0 0;color:#C9A24B;font-weight:700;letter-spacing:.16em;text-transform:uppercase;font-size:.82rem;">Full details and applications opening soon</p>
        <p style="margin:60px 0 0;font-style:italic;color:rgba(255,255,255,.72);line-height:1.7;">&ldquo;The next generation is already here.<br>The question is who will help shape it.&rdquo;</p>
      </div>
    </div>
  </section>

</div>
```

- [ ] **Step 2: Header nav** — in `build/templates/header.html`, insert after the FAQ link (before Contact):

```html
      <a href="/elite-academy/">GV Elite Academy</a>
```

- [ ] **Step 3: WP menu mirror** — in `build/scripts/build-menu.php`, insert after the `gv_item($menu_id, 'FAQ', '/faq/');` line:

```php
gv_item($menu_id, 'GV Elite Academy', '/elite-academy/');
```

- [ ] **Step 4: Footer Explore link** — in `build/templates/footer.html`, after the FAQ `<li>` (line ~18):

```html
        <li><a href="/elite-academy/">GV Elite Academy</a></li>
```

- [ ] **Step 5: Create `build/scripts/deploy-minimal-edits.php`** (self-contained round deploy — pages only; theme parts and menu have their own scripts):

```php
<?php
// Run on host: wp eval-file deploy-minimal-edits.php
// Requires home.html, training-programs.html, faq.html, elite-academy.html scp'd to $HOME/pages.
$dir = getenv('HOME') . '/pages';
$aid = gv_ensure_page('elite-academy', 'GV Elite Academy');
if (!$aid) { echo "FAILED to ensure elite-academy page\n"; return; }
$map = array(
  2887 => 'home.html',
  2981 => 'training-programs.html',
  2988 => 'faq.html',
  $aid => 'elite-academy.html',
);
foreach ($map as $id => $f) {
    $p = "$dir/$f";
    if (!file_exists($p)) { echo "MISSING $f\n"; continue; }
    echo "$f: " . gv_set_page_html($id, file_get_contents($p)) . "\n";
}
echo "elite-academy=$aid\n";
```

- [ ] **Step 6: Local checks**

```bash
php -l build/scripts/deploy-minimal-edits.php
grep -c "elite-academy" build/templates/header.html build/templates/footer.html build/scripts/build-menu.php
grep -c "gv_open_modal\|data-gv-consultation\|<form" build/pages/elite-academy.html; echo "no-forms-exit=$?"
```
Expected: `No syntax errors`; count `1` in each of the three nav files; the last grep finds nothing (`no-forms-exit=1`) — the teaser has no CTA plumbing by design.

- [ ] **Step 7: Commit**

```bash
git add build/pages/elite-academy.html build/scripts/deploy-minimal-edits.php build/templates/header.html build/templates/footer.html build/scripts/build-menu.php
git commit -m "feat: GV Elite Academy coming-2027 teaser page + nav tab and footer link"
```

---

### Task 4: Deploy to Hostinger + live verification

**Files:** none (server operation). **Read `wiki/deployment-workflows.md` first** for the SSH alias, remote paths, which eval-file applies theme parts (`deploy-members-theme-parts.php` needs `header.html` + `footer.html` in `$HOME`), how the booking page content script (`build-functional.php`) is applied, and the cache purge command. Use its values verbatim.

- [ ] **Step 1: Final regression run** — all CLI suites green; abort deploy on any FAIL.

- [ ] **Step 2: Upload** (paths per wiki):

```bash
scp build/pages/home.html build/pages/training-programs.html build/pages/faq.html build/pages/elite-academy.html <host>:~/pages/
scp build/templates/header.html build/templates/footer.html <host>:~/
scp build/scripts/deploy-minimal-edits.php build/scripts/build-menu.php build/scripts/build-functional.php <host>:~/
```

- [ ] **Step 3: Apply on host**

```bash
ssh <host> 'cd <wp-root> && wp eval-file ~/deploy-minimal-edits.php && wp eval-file ~/build-menu.php && wp eval-file ~/deploy-members-theme-parts.php && wp eval-file ~/build-functional.php'
```
Expected: four `updated`-style lines + `elite-academy=<id>` (record the ID for Task 6), `menu … created & assigned`, theme parts updated, booking page rebuilt. If the wiki shows a different invocation for any of these scripts, follow the wiki.

- [ ] **Step 4: Purge cache** per wiki.

- [ ] **Step 5: Live verification checklist**

1. Home + `/training-programs/`: **GV ELITE PERFORMANCE / APPLICATION REQUIRED · LIMITED ENROLLMENT**, four pillars, **APPLY NOW** button **opens the consultation wizard** (click it).
2. Private/Small Group/header CTAs unchanged and functional.
3. No "aqua training" on any page (Cmd-F home, programs, FAQ).
4. `/faq/`: cost question gone; hero lead has no "costs"; heading reads "Programs"; the program-difference answer says GV Elite Performance.
5. Booking/consultation section: blurb ends "…follow up to confirm."
6. `/elite-academy/`: one dark screen — COMING 2027 eyebrow, statement, subline, "opening soon" line, closing quote; **no buttons/forms**; gold accents render.
7. Nav shows **GV Elite Academy** between FAQ and Contact on desktop and in the mobile burger menu; footer Explore includes it.
8. 404 check: `/apply/` was never created — confirm nothing links to it (grep live HTML if unsure).

- [ ] **Step 6: Commit any deploy-time adjustments**

```bash
git add -A build/scripts wiki/ && git commit -m "chore: deploy-time adjustments for minimal edits round" || echo "nothing to commit"
```

---

### Task 5: JULY-v2 client report (this round's changes only)

**Files:**
- Create: `docs/CLIENT-REPORT-JULY-V2.html`
- Create: `docs/screenshots/v2-elite-card.png`, `v2-apply-now-wizard.png`, `v2-academy-page.png`, `v2-academy-nav.png`, `v2-faq-programs.png`

**Interfaces:**
- Consumes: the live site (deployed in Task 4); style/skeleton from `docs/report.template.html`; section markup patterns from `docs/CLIENT-REPORT-JULY.html` (figure/screen-frame markup, status chips).

- [ ] **Step 1: Capture live screenshots** (Chrome browser tools or headless, desktop viewport ~1440px; mobile shot for the nav):
  - `v2-elite-card.png` — the GV Elite Performance card on `/training-programs/`.
  - `v2-apply-now-wizard.png` — the consultation wizard open after clicking APPLY NOW.
  - `v2-academy-page.png` — full `/elite-academy/` screen.
  - `v2-academy-nav.png` — header nav (desktop) showing the GV Elite Academy tab.
  - `v2-faq-programs.png` — the FAQ "Programs" section with the cost question absent.

- [ ] **Step 2: Build `docs/CLIENT-REPORT-JULY-V2.html`** using `report.template.html`'s styles and `CLIENT-REPORT-JULY.html`'s section/figure markup. Content (this round ONLY — do not restate the July round-1 items):
  - **Cover:** eyebrow "Client Update — Round 2", title "July Updates, Part 2", meta chips `JULY 2026 · V2`, `gvbasketball.com`. Intro: this round applies your approved copy revisions and introduces the GV Elite Academy preview; new features from the creative-direction brief are scheduled for a future round, listed at the end.
  - **01 — GV Elite Performance:** new card copy (four pillars, Application Required · Limited Enrollment) with `v2-elite-card.png`; APPLY NOW button explained plainly: *"Apply Now starts the same consultation request you already receive — applications are reviewed through the consultation for now"* with `v2-apply-now-wizard.png`.
  - **02 — Pricing Removed from Copy:** FAQ cost question deleted, booking text trimmed; note the booking wizard already never displayed prices. `v2-faq-programs.png`.
  - **03 — GV Elite Academy — Coming 2027:** the new tab + one-screen teaser with `v2-academy-nav.png` and `v2-academy-page.png`; one line that the full cinematic page (video, experience icons, founding partners, priority list) is designed and ready to build when green-lit.
  - **04 — Scheduled for the Next Round:** plain-language deferred register mirroring spec §6 (application form & /apply/, full Academy page & priority list, Founding Partners display, Results page, new navigation, photography/design pass — the last two waiting on your photos/video). Frame as "designed and documented, awaiting your go".
  - **Tested & Live summary:** checklist of what was verified in Task 4 Step 5.
- [ ] **Step 3: Review the report in a browser locally** (open the file; check images load via relative `screenshots/…` paths exactly as CLIENT-REPORT-JULY.html references its shots — mirror whichever path convention that file uses).

- [ ] **Step 4: Upload** (mirroring the round-1 convention `docs/CLIENT-REPORT-JULY.html` → `public_html/july-updates.html`):

```bash
scp docs/CLIENT-REPORT-JULY-V2.html <host>:<public_html>/july-updates-v2.html
# plus the five v2-*.png screenshots to the same relative location the report references, per the round-1 convention
```
Verify `https://gvbasketball.com/july-updates-v2.html` renders with all images.

- [ ] **Step 5: Commit**

```bash
git add docs/CLIENT-REPORT-JULY-V2.html docs/screenshots/v2-*.png
git commit -m "docs: JULY-v2 client report covering the minimal edits round"
```

---

### Task 6: Wiki sync + changelog

**Files:**
- Modify: `wiki/pages.md`, `wiki/client-status.md`, `wiki/log.md`

- [ ] **Step 1: `wiki/pages.md`** — add `/elite-academy/` (ID from Task 4 Step 3 output): one-screen Coming-2027 teaser, in primary nav + footer Explore, no forms.

- [ ] **Step 2: `wiki/client-status.md`** — add a Recent Highlight:

```markdown
0a. **Minimal Edits Round (July 10, v2):** Elite Performance is now **GV Elite Performance** — "Application Required · Limited Enrollment" with the four pillars (Court Training, Strength & Conditioning, Recovery, Nutrition; "aqua training" retired) and an **Apply Now** button that opens the existing consultation wizard. Pricing talk was removed from the FAQ and booking copy. A one-screen **GV Elite Academy — Coming 2027** teaser page is live at `/elite-academy/` with its own nav tab. Round-2 report: `docs/CLIENT-REPORT-JULY-V2.html` (live at `/july-updates-v2.html`). **Deferred by agreement (new features, future round):** the Elite application form + `/apply/`, the full Academy page with priority list and Founding Partners, the Results page, the new navigation, and the photography/design-language pass (last two also awaiting client assets).
```

- [ ] **Step 3: `wiki/log.md`** — append:

```markdown
## [2026-07-10] task | Minimal edits round: GV Elite Performance copy, pricing removal, Academy teaser
- **Goal:** Ship only straightforward client revisions (new features deferred by agreement) plus a Coming-2027 GV Elite Academy teaser.
- **Changes:**
  - `build/pages/home.html`, `build/pages/training-programs.html`, `build/pages/faq.html`: GV Elite Performance rebrand (four pillars, Application Required · Limited Enrollment), Apply Now label on existing `data-gv-consultation` buttons, "aqua training" removed.
  - `build/pages/faq.html`, `build/scripts/build-functional.php`: pricing mentions removed from copy (cost FAQ deleted; booking blurb trimmed). Price-hiding CSS untouched.
  - New `build/pages/elite-academy.html` one-screen teaser (gold `#C9A24B` accents); nav tab added in `build/templates/header.html`, `build/scripts/build-menu.php`, footer Explore in `build/templates/footer.html`; deployed via new `build/scripts/deploy-minimal-edits.php`.
  - `build/scripts/setup-latepoint.php`: service renamed GV Elite Performance, description updated.
  - `docs/CLIENT-REPORT-JULY-V2.html` + `docs/screenshots/v2-*.png` uploaded to `public_html/july-updates-v2.html`.
  - Deferred register documented in spec `docs/superpowers/specs/2026-07-10-minimal-edits-design.md` §6; deferred specs/plans retained under `docs/superpowers/`.
```

- [ ] **Step 4: Commit**

```bash
git add wiki/
git commit -m "docs: wiki sync for minimal edits round + JULY-v2 report"
```

---

## Self-Review Notes

- **Spec coverage:** spec §3 (Elite copy incl. FAQ mentions) → Task 1; §4b (pricing) → Task 2; §4 (Academy teaser + nav) → Task 3; §5 constraints enforced by Task 1/2/3 verify steps; §7 testing → Task 1 Step 6, Task 4 Step 5; §8 deploy/docs → Tasks 4 & 6. User's report requirement → Task 5.
- **Judgment calls:** the FAQ hero-lead "costs," removal and "Programs & Costs"→"Programs" heading follow from §4b's sweep clause; the Apply Now report wording tells Coach Gino honestly that applications currently route through consultations.
- **Deliberately not done:** no `/apply/` page, no new mu-plugin, no test files (no new logic), no menu restructure beyond the single Academy item.
