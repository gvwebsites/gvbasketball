# Home / Footer / CTA Post-Revision Fixes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship eight client-flagged polish fixes to the home page, footer, and shared CTA components of gvbasketball.com.

**Architecture:** The front end is hand-written HTML mounted into Elementor plus a shared CSS design system (`gv-brand.css`), deployed over SSH. Edit source in `build/`, `scp` to the server, apply with a `gv_*` helper, then flush caches. No build step, no test framework — verification is `grep`/`curl`/visual against the live site.

**Tech Stack:** WordPress (Hostinger, PHP 8.2), WP-CLI over SSH `gvweb`, Elementor, hand-written HTML/CSS, Safe SVG, WPForms, LiteSpeed + Cloudflare. Spec: [../specs/2026-07-09-home-footer-cta-fixes.md](../specs/2026-07-09-home-footer-cta-fixes.md).

## Global Constraints
- WordPress root: `/home/u907133977/domains/gvbasketball.com/public_html` (run all `wp` from here).
- SSH prints a harmless post-quantum warning to stderr; ignore/filter it.
- After **any** deploy: `wp elementor flush-css && wp litespeed-purge all`.
- Verify against live site with a cache-busting hard refresh (Cloudflare proxies; LiteSpeed caches).
- SVG uploads require Safe SVG **and** admin: `wp media import file.svg --user=1`.
- Brand tokens (verbatim): navy `#123B78`, deep navy `#021F51`, gold `var(--gv-gold)`, orange `var(--gv-orange)`, gold-soft `var(--gv-gold-soft)`, navy-black `var(--gv-navy-black)`.
- Contact is Instagram-only; no pricing; never re-add WhatsApp/Facebook.
- CSS is cache-busted by file mtime — a fresh `scp` + LiteSpeed purge is enough.
- Commit after each task; keep `.env` out of git. Update `PROJECT_LOG.md` after meaningful changes.
- DEST for CSS: `/home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins`.

---

### Task 1: Prepare & upload assets (SVGs + hero)

Produces the media URLs every later task consumes. Nothing user-facing changes yet.

**Files:**
- Create: `logo/gvbasketball-logo-white.svg`
- Create: `logo/gvbasketball-long-white.svg`
- Create: `build/assets/photos/gv-home-hero-v2.webp`

**Interfaces produced (URLs consumed by Tasks 3, 4, 6):**
- Monogram: `https://gvbasketball.com/wp-content/uploads/<YYYY>/<MM>/gvbasketball-logo-white.svg`
- Wordmark: `https://gvbasketball.com/wp-content/uploads/<YYYY>/<MM>/gvbasketball-long-white.svg`
- Hero: `https://gvbasketball.com/wp-content/uploads/<YYYY>/<MM>/gv-home-hero-v2.webp`

> Record the three exact URLs `wp media import` prints — later tasks reference them literally.

- [ ] **Step 1: Create white monogram SVG** — copy `logo/gvbasketball-logo.svg` and swap its fill.

```bash
sed 's/fill="#021F51"/fill="#ffffff"/g' logo/gvbasketball-logo.svg > logo/gvbasketball-logo-white.svg
sed 's/fill="#021F51"/fill="#ffffff"/g' logo/gvbasketball-long.svg > logo/gvbasketball-long-white.svg
grep -c 'fill="#ffffff"' logo/gvbasketball-logo-white.svg logo/gvbasketball-long-white.svg
```
Expected: each file reports `1` (one recolored `<g fill>`).

- [ ] **Step 2: Generate the premium hero** — invoke the **codex-imagegen** skill.

Prompt intent: cinematic, professional indoor basketball training moment; dramatic side light; navy/charcoal tones that sit under a `#021F51` gradient overlay; wide 16:9, ≥2400px, photoreal (matches the site's real-photo look — not illustrated). Save/optimize the result to `build/assets/photos/gv-home-hero-v2.webp`.

Verify local file resolution:
```bash
file build/assets/photos/gv-home-hero-v2.webp    # expect: WebP, width >= 2400
```

- [ ] **Step 3: Upload all three assets to the media library as admin**

```bash
scp logo/gvbasketball-logo-white.svg logo/gvbasketball-long-white.svg \
    build/assets/photos/gv-home-hero-v2.webp gvweb:~
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp media import ~/gvbasketball-logo-white.svg --user=1 && \
  wp media import ~/gvbasketball-long-white.svg --user=1 && \
  wp media import ~/gv-home-hero-v2.webp --user=1 && \
  rm ~/gvbasketball-logo-white.svg ~/gvbasketball-long-white.svg ~/gv-home-hero-v2.webp' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: three `Success: Imported file … as attachment ID …` lines. **Copy the three printed URLs.**

- [ ] **Step 4: Confirm the SVGs and hero are reachable**

```bash
for u in gvbasketball-logo-white.svg gvbasketball-long-white.svg gv-home-hero-v2.webp; do \
  curl -sI "https://gvbasketball.com/wp-content/uploads/$(date +%Y)/$(date +%m)/$u" | head -1; done
```
Expected: `HTTP/2 200` for each (adjust the `YYYY/MM` path to match the URLs Step 3 printed).

- [ ] **Step 5: Commit**

```bash
git add logo/gvbasketball-logo-white.svg logo/gvbasketball-long-white.svg build/assets/photos/gv-home-hero-v2.webp
git commit -m "assets: white GV logo variants + new premium home hero"
```

---

### Task 2: Site-wide CTA copy sweep — "Request Training" → "Book a Consultation" (R8)

**Files (site-facing — modify all):**
- `build/pages/home.html`, `about.html`, `faq.html`, `gallery.html`, `success-stories.html`, `testimonials.html`, `athlete-development.html`, `training-programs.html`
- `build/templates/header.html`, `build/templates/footer.html`
- `build/scripts/build-functional.php`, `build-menu.php`, `ensure-pages.php`

**Files intentionally NOT changed (internal identifiers):**
- `build/mu-plugins/gv-request-form.php:3` (WP plugin name)
- `build/scripts/setup-turnstile.sh:22` (live Turnstile widget name — renaming orphans the sitekey)

- [ ] **Step 1: Replace across the site-facing files only**

```bash
grep -rl "Request Training" build/pages build/templates \
  build/scripts/build-functional.php build/scripts/build-menu.php build/scripts/ensure-pages.php \
  | xargs sed -i '' 's/Request Training/Book a Consultation/g'
```

- [ ] **Step 2: Verify — no site-facing occurrences remain, only the two internal ones**

```bash
grep -rn "Request Training" build/
```
Expected: exactly two lines — `build/mu-plugins/gv-request-form.php:3` and `build/scripts/setup-turnstile.sh:22`. Nothing else.

- [ ] **Step 3: Eyeball grammar on the non-button hits** (headings/eyebrows/prose now read naturally):

```bash
grep -rn "Book a Consultation" build/pages/faq.html build/pages/training-programs.html build/scripts/build-functional.php build/scripts/ensure-pages.php
```
Confirm: faq.html:22 prose ("send a request from the \"Book a Consultation\" page"), training-programs.html:188 step title, build-functional.php:59/74 eyebrow+H2, ensure-pages.php:5 page title all read correctly. (No deploy in this task — deploys happen per-surface in Tasks 4–7.)

- [ ] **Step 4: Commit**

```bash
git add -A build/
git commit -m "content: prefer 'Book a Consultation' over 'Request Training' site-wide"
```

---

### Task 3: CSS fixes — gold-CTA hover, Subscribe button, watermark-as-image (R3, R4, R5)

**Files:**
- Modify: `build/mu-plugins/gv-assets/gv-brand.css` (lines ~85–89, ~198, ~204–205, ~212, ~277–278)

- [ ] **Step 1: R4 — gold CTA keeps white text (fix the orange-on-hover bug)**

Root cause: `.gv-page a:hover{color:var(--gv-orange)}` (line 45) out-specifies `.gv-btn--gold:hover`. Change the gold button to white text in both states and add it to the anchor-override block.

Edit lines 204–205 to:
```css
.gv-btn--gold{background:var(--gv-gold);color:#fff;box-shadow:0 10px 24px -10px rgba(201,162,75,.8);}
.gv-btn--gold:hover{background:var(--gv-gold-soft);color:#fff;transform:translateY(-2px);}
```
Then in the `.gv-page a.gv-btn…` override block (lines 85–89), add a line so it wins over the generic anchor hover:
```css
.gv-page a.gv-btn--gold,.gv-page a.gv-btn--gold:hover{color:#fff;}
```

- [ ] **Step 2: R5 — Subscribe button gold** — edit lines 277–278:

```css
.gv-newsletter-band .wpforms-form button[type=submit]{background:var(--gv-gold)!important;color:#fff!important;border:0!important;border-radius:8px!important;font-family:var(--gv-font-head)!important;font-weight:700!important;text-transform:uppercase;letter-spacing:.03em;padding:13px 26px!important;cursor:pointer;}
.gv-newsletter-band .wpforms-form button[type=submit]:hover{background:var(--gv-gold-soft)!important;}
```

- [ ] **Step 3: R3 — watermark becomes an image mark** — replace the text-based rule at line 198:

```css
.gv-cta__watermark{position:absolute;top:-30px;right:24px;width:230px;height:auto;opacity:.06;pointer-events:none;user-select:none;z-index:0;}
```
And in the `@media(max-width:560px)` rule (line 212) change the watermark clause from `font-size:9rem` to `width:130px`.

- [ ] **Step 4: Verify the edits locally**

```bash
grep -n "gv-btn--gold\|a.gv-btn--gold\|gv-cta__watermark\|button\[type=submit\]{background:var(--gv-gold)" build/mu-plugins/gv-assets/gv-brand.css
```
Expected: gold button/hover both show `color:#fff`; the anchor override line exists; watermark rule shows `width:230px;...opacity:.06`; subscribe submit shows `background:var(--gv-gold)`.

- [ ] **Step 5: Deploy CSS + flush**

```bash
scp build/mu-plugins/gv-assets/gv-brand.css \
  gvweb:/home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins/gv-assets/gv-brand.css
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && wp elementor flush-css && wp litespeed-purge all' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: `Success:` from purge. (Visual check of hover/subscribe/watermark happens in Task 7 after the HTML lands.)

- [ ] **Step 6: Commit**

```bash
git add build/mu-plugins/gv-assets/gv-brand.css
git commit -m "style: gold CTA keeps white text, gold Subscribe button, image-based CTA watermark"
```

---

### Task 4: Home page — Gino 3-up, new hero, watermark image, locations moved in (R1, R2, R3, R7)

**Files:**
- Modify: `build/pages/home.html` (line 5 hero, lines 29–51 mentor grid, line 113 watermark, add locations section before the Final CTA)

**Interfaces consumed:** the hero + monogram URLs from Task 1.

- [ ] **Step 1: R2 — swap the hero background URL** at [home.html:5](../../../build/pages/home.html) — replace `…/uploads/2026/07/gv-home-hero-real.webp` with the `gv-home-hero-v2.webp` URL from Task 1.

- [ ] **Step 2: R1 — make the mentor grid 3-up with Gino first.** Change `gv-grid--2` → `gv-grid--3` (line 33) and reword the head block (lines 24–31) so the eyebrow reads `Coach & Mentors` and the copy frames Gino as trained under his NBA mentors. Prepend this card as the first child of the grid:

```html
<div class="gv-person">
  <img class="gv-person__img" src="https://gvbasketball.com/wp-content/uploads/2026/07/gv-coach-gino-portrait.webp" alt="Coach Gino">
  <div class="gv-person__body">
    <h3 class="gv-person__name">Coach Gino</h3>
    <div class="gv-person__role">Founder &amp; Head Coach</div>
    <p>Certified skills trainer developing players across Metro Manila — trained directly under the NBA skills coaches below.</p>
  </div>
</div>
```

- [ ] **Step 3: R3 — swap the text watermark for the monogram image** at line 113:

```html
<img class="gv-cta__watermark" src="https://gvbasketball.com/wp-content/uploads/2026/07/gvbasketball-logo-white.svg" alt="" aria-hidden="true">
```
(Use the exact monogram URL from Task 1.)

- [ ] **Step 4: R7 — add the locations section** (copied from the Contact source in `build-functional.php` lines 156–163) immediately **before** the Final CTA section (before `<!-- FINAL CTA -->`, line 109):

```html
  <!-- LOCATIONS -->
  <section class="gv-section gv-section--light">
    <div class="gv-wrap">
      <div class="gv-head-block gv-center"><span class="gv-eyebrow">Where We Train</span><h2 class="gv-section-title">Our Locations</h2><p class="gv-lead">Small-group sessions run across Metro Manila. Your exact venue is confirmed at your consultation.</p></div>
      <div class="gv-grid gv-grid--3">
        <div class="gv-card"><div class="gv-card__icon"><svg viewBox="0 0 24 24"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></div><h3 class="gv-card__title">Dasma, Makati</h3><p>Small-group sessions — Mon, Wed &amp; Thu.</p><div style="margin-top:14px;"><a class="gv-btn gv-btn--outline" href="https://www.google.com/maps/search/?api=1&query=Dasmari%C3%B1as%20Village%2C%20Makati" target="_blank" rel="noopener">View on Google Maps</a></div></div>
        <div class="gv-card"><div class="gv-card__icon"><svg viewBox="0 0 24 24"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></div><h3 class="gv-card__title">Urdaneta Village</h3><p>Small-group sessions — Fri &amp; Sun.</p><div style="margin-top:14px;"><a class="gv-btn gv-btn--outline" href="https://www.google.com/maps/search/?api=1&query=Urdaneta%20Village%2C%20Makati" target="_blank" rel="noopener">View on Google Maps</a></div></div>
        <div class="gv-card"><div class="gv-card__icon"><svg viewBox="0 0 24 24"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></div><h3 class="gv-card__title">Corinthian Gardens</h3><p>Small-group sessions — Sun.</p><div style="margin-top:14px;"><a class="gv-btn gv-btn--outline" href="https://www.google.com/maps/search/?api=1&query=Corinthian%20Gardens%2C%20Quezon%20City" target="_blank" rel="noopener">View on Google Maps</a></div></div>
      </div>
    </div>
  </section>
```

- [ ] **Step 5: Deploy home (post 2887) + flush**

```bash
scp build/pages/home.html gvweb:~/home.html
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval "echo gv_set_page_html(2887, file_get_contents(getenv(\"HOME\").\"/home.html\"));" && \
  wp elementor flush-css && wp litespeed-purge all && rm ~/home.html' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: a numeric return from `gv_set_page_html` + `Success:` from purge.

- [ ] **Step 6: Verify home end-to-end**

```bash
curl -s "https://gvbasketball.com/?nocache=$(date +%s)" | grep -o "gv-grid--3\|gv-home-hero-v2\|gvbasketball-logo-white\|Our Locations\|Coach Gino\|Book a Consultation" | sort -u
```
Expected set includes: `Book a Consultation`, `Coach Gino`, `Our Locations`, `gv-grid--3`, `gv-home-hero-v2`, `gvbasketball-logo-white`. Then load the page in a browser (hard refresh): 3 mentor cards, crisp hero, faint GV emblem watermark, gold CTA text stays white on hover.

- [ ] **Step 7: Commit**

```bash
git add build/pages/home.html
git commit -m "feat(home): 3-up coach+mentors, premium hero, logo watermark, locations section"
```

---

### Task 5: Contact page — remove the locations section (R7)

**Files:**
- Modify: `build/scripts/build-functional.php` (delete the "Where We Train / Our Locations" `<section>`, lines 156–163 of the `$contact_a` heredoc)

- [ ] **Step 1: Delete the locations section** from the `$contact_a` heredoc — remove the entire `<section class="gv-section gv-section--light">…Our Locations…</section>` block, leaving the hero, the Instagram/email contact grid, and the "Send a Message" form intact.

- [ ] **Step 2: Verify it's gone from the source**

```bash
grep -c "Our Locations" build/scripts/build-functional.php
```
Expected: `0`.

- [ ] **Step 3: Deploy the functional pages script (rebuilds Contact 2989) + flush**

```bash
scp build/scripts/build-functional.php gvweb:~/build-functional.php
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval-file ~/build-functional.php && wp elementor flush-css && wp litespeed-purge all && rm ~/build-functional.php' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: `wpforms_id=…`, page-id echoes, `accent_color set`.

- [ ] **Step 4: Verify Contact no longer shows locations**

```bash
curl -s "https://gvbasketball.com/contact/?nocache=$(date +%s)" | grep -c "Our Locations"
```
Expected: `0`.

- [ ] **Step 5: Commit**

```bash
git add build/scripts/build-functional.php
git commit -m "content(contact): move locations to home (remove from contact)"
```

---

### Task 6: Footer — real wordmark logo (R6) + rebuild footer with gold Subscribe band

**Files:**
- Modify: `build/templates/footer.html` (line 4 brand wordmark)

**Interfaces consumed:** the white wordmark URL from Task 1. (Subscribe gold already shipped in Task 3's CSS; the band is re-rendered by `build-extras.php`.)

- [ ] **Step 1: Replace the text wordmark** at [footer.html:4](../../../build/templates/footer.html) — swap the `<div style="font-family:var(--gv-font-display)…">GV<span…>BASKETBALL</span></div>` for:

```html
<a href="/" aria-label="GV Basketball"><img class="gv-footer__logo" src="https://gvbasketball.com/wp-content/uploads/2026/07/gvbasketball-long-white.svg" alt="GV Basketball" style="height:40px;width:auto;display:block;"></a>
```
(Use the exact white-wordmark URL from Task 1.)

- [ ] **Step 2: Deploy footer via build-extras.php (rebuilds footer part + newsletter band; re-registers conditions) + flush**

```bash
scp build/templates/footer.html gvweb:~/footer.html
scp build/scripts/build-extras.php gvweb:~/build-extras.php
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval-file ~/build-extras.php && wp elementor flush-css && wp litespeed-purge all && rm ~/footer.html ~/build-extras.php' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: `newsletter_id=…`, `waiver_id=…`, `footer_id=… conditions ok`.

- [ ] **Step 3: Verify footer logo + gold subscribe render**

```bash
curl -s "https://gvbasketball.com/?nocache=$(date +%s)" | grep -o "gvbasketball-long-white\|gv-footer__logo" | sort -u
```
Expected: both strings present. Browser hard-refresh: footer shows the white wordmark image; Subscribe button is gold (gold-soft on hover).

- [ ] **Step 4: Commit**

```bash
git add build/templates/footer.html
git commit -m "style(footer): real GV wordmark logo (white) replacing text"
```

---

### Task 7: Deploy remaining pages + header/menu (R8 copy) & full verification

**Files:** no new edits — deploys the Task 2 copy sweep for surfaces not yet pushed (marketing pages, header, Astra menu, page titles).

- [ ] **Step 1: Deploy all marketing pages** (about, training-programs, athlete-development, success-stories, testimonials, gallery, faq — maps to IDs in `apply-pages.php`):

```bash
ssh gvweb 'mkdir -p ~/pages'
scp build/pages/about.html build/pages/training-programs.html build/pages/athlete-development.html \
    build/pages/success-stories.html build/pages/testimonials.html build/pages/gallery.html build/pages/faq.html \
    gvweb:~/pages/
scp build/scripts/apply-pages.php gvweb:~/apply-pages.php
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval-file ~/apply-pages.php && wp elementor flush-css && wp litespeed-purge all && rm -rf ~/pages ~/apply-pages.php' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: seven page-id echoes, no `MISSING`.

- [ ] **Step 2: Redeploy header (CTA label) + Astra menu + page title.** Header is deployed by the existing refine script pattern; the menu and the `book-a-consultation` page title come from `build-menu.php` / `ensure-pages.php`:

```bash
scp build/scripts/build-menu.php build/scripts/ensure-pages.php gvweb:~
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval-file ~/build-menu.php && wp eval-file ~/ensure-pages.php && \
  wp elementor flush-css && wp litespeed-purge all && rm ~/build-menu.php ~/ensure-pages.php' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
> Header template (`header.html`, post 3002) is deployed via `gv_set_theme_part`. If a standalone header deploy script isn't present, apply it directly:
```bash
scp build/templates/header.html gvweb:~/header.html
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval "echo gv_set_theme_part(\"GV Header\",\"header\",file_get_contents(getenv(\"HOME\").\"/header.html\"));" && \
  wp elementor flush-css && wp litespeed-purge all && rm ~/header.html' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```

- [ ] **Step 3: Full site verification (all 8 requirements)**

```bash
# R8 — no site-facing "Request Training" on key surfaces
for p in "" "about/" "training-programs/" "faq/" "gallery/" "success-stories/" "athlete-development/"; do \
  echo -n "$p: "; curl -s "https://gvbasketball.com/$p?nocache=$(date +%s)" | grep -c "Request Training"; done
```
Expected: `0` for every surface. Then in a browser (hard refresh / incognito):
- **R1** Home mentor grid = 3 cards, Coach Gino first; stacks at ≤782px.
- **R2** Hero is crisp on a 2× display; navy overlay intact.
- **R3** CTA watermark is the GV **emblem** (not letters), faint.
- **R4** Hover gold "Book a Consultation" → text stays **white**.
- **R5** Footer Subscribe button is **gold** (gold-soft on hover).
- **R6** Footer wordmark is the **logo image**.
- **R7** Locations present on Home; **absent** on `/contact/`.
- **R8** Header CTA, program cards, all page CTAs read "Book a Consultation".
- Mobile pass at 390px: hero, 3-up grid, footer logo, watermark scale cleanly.

- [ ] **Step 4: Update the project log & commit**

```bash
# Append a dated entry to PROJECT_LOG.md summarizing R1–R8, then:
git add PROJECT_LOG.md
git commit -m "docs: log post-revision home/footer/CTA fixes"
```

---

## Self-Review

- **Spec coverage:** R1→Task 4·S2; R2→Task 1·S2 + Task 4·S1; R3→Task 1·S1 + Task 3·S3 + Task 4·S3; R4→Task 3·S1; R5→Task 3·S2; R6→Task 1·S1 + Task 6·S1; R7→Task 4·S4 (add) + Task 5 (remove); R8→Task 2 (edit) + Tasks 4–7 (deploy). All eight covered.
- **Internal identifiers:** `gv-request-form.php` plugin name and `setup-turnstile.sh` widget name are explicitly excluded from the R8 sweep (Task 2) — matches the spec's acceptance note.
- **Asset URLs:** Tasks 4 and 6 consume the exact URLs recorded in Task 1 Step 3 (the `2026/07` paths shown are the expected upload month — substitute the actual printed URLs).
- **No test framework** exists; TDD's test-first cycle is adapted to change→`grep`/`curl`/visual verify per task, with a commit closing each.
