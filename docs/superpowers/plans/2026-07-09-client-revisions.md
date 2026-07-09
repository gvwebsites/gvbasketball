# GV Basketball Client Revisions — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the client's 2026-07-09 revisions — training-program rewrite, new venue/schedule sweep + LatePoint reconfig, real photos, a scoped-gold premium CTA + minimalist footer, and an informational booking/payment flow graphic.

**Architecture:** The site is hand-written HTML + a shared CSS design system (`gv-brand.css`) mounted into Elementor by mu-plugins. There is **no test framework** — the "test" cycle for every task is **edit `build/` → `scp` to server → apply with a `gv_*` helper → flush caches → verify the live URL in a browser** (and confirm the deployed HTML with `wp eval`/`curl`). Work happens on branch `revisions-2026-07-09`.

**Tech Stack:** WordPress (Hostinger, PHP 8.2), WP-CLI over SSH (`ssh gvweb`), Elementor + Elementor Pro Theme Builder, LatePoint, LiteSpeed + Cloudflare, `cwebp`/ImageMagick for image optimization, `/codex-imagegen` for hero outpainting.

## Global Constraints

Copied verbatim from the spec (`docs/superpowers/specs/2026-07-09-client-revisions-design.md`) and `CLAUDE.md`. **Every task implicitly includes these:**

- SSH host: `ssh gvweb`. WP root: `/home/u907133977/domains/gvbasketball.com/public_html` — run all `wp` from there.
- After ANY change: `wp elementor flush-css && wp litespeed-purge all`. CSS is mtime-cache-busted; fresh `scp` + LiteSpeed purge suffices.
- Filter SSH noise with `2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"`.
- `wp db export` **fails on this host** (exit 255). Back up table-scoped: `wp db query "SELECT ..." --skip-column-names > backup.tsv`.
- Never print secret values; `.env` stays out of git.
- Cloudflare: keep `rocket_loader` **OFF**; never add a "Cache Everything" rule.
- **Pricing never shown publicly. No online payments. No bank details on the site.**
- WhatsApp / Facebook stay **off** the site. On-site contact = Instagram (`https://ig.me/m/gvbasketballl`, `@gvbasketballl`) + displayed email `gvbasketballcoaching@gmail.com`.
- Brand voice: disciplined, confident, developmental. Don't invent stats, athletes, or schools.
- SVG uploads: Safe SVG plugin + `wp media import file.svg --user=1`.
- Photo raster uploads: `wp media import <file> --user=1`.
- **Gold is scoped to `.gv-cta` + footer only.** Orange stays primary everywhere else.
- Exact venue names (client): **Dasma, Makati** (Mon/Wed/Thu) · **Urdaneta Village** (Fri/Sun) · **Corinthian Gardens** (Sun). Private & Elite = **by appointment**. Region shorthand where tight = "Metro Manila".
- Gold token: `--gv-gold:#C9A24B`, `--gv-gold-soft:#E4C77E`, `--gv-navy-black:#0A1B33`.
- Page IDs: Home **2887**, About **26**, Training Programs **2981**, Athlete Development **2984**, Book a Consultation **2982**, Booking portal **2983**, Contact **2989**, FAQ **2988**, Gallery **2987**. Footer Theme Builder part = **GV Footer (2991)**.
- Deploy helpers (`mu-plugins/gv-build.php`): `gv_set_page_html($id,$html)`, `gv_set_page_blocks($id,$blocks)`, `gv_set_theme_part($title,$type,$html)`, `gv_set_theme_part_blocks(...)`.
- Commit frequently. End commit messages with `Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>`. Never commit `.env`.

**Standard deploy snippets (referenced by tasks as "DEPLOY-CSS", "DEPLOY-PAGE", "DEPLOY-FOOTER"):**

```bash
# DEPLOY-CSS
DEST=/home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins
scp build/mu-plugins/gv-assets/gv-brand.css gvweb:$DEST/gv-assets/gv-brand.css
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && wp litespeed-purge all' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```

```bash
# DEPLOY-PAGE  (args: <build-file> <page-id>)
scp build/pages/<FILE>.html gvweb:~/<FILE>.html
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval "echo gv_set_page_html(<ID>, file_get_contents(getenv(\"HOME\").\"/<FILE>.html\"));" && \
  wp elementor flush-css && wp litespeed-purge all && rm ~/<FILE>.html' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```

```bash
# DEPLOY-FOOTER  (rebuild footer theme part; script keeps Theme Builder conditions)
scp build/scripts/build-extras.php gvweb:~/build-extras.php
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval-file ~/build-extras.php && wp elementor flush-css && wp litespeed-purge all && rm ~/build-extras.php' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```

**Verify snippet (referenced as "VERIFY-LIVE", args: <url> <grep-string>):**
```bash
curl -s "https://gvbasketball.com<PATH>" | grep -o "<GREP-STRING>" | head -1
# Expected: prints <GREP-STRING>  (empty output = not deployed / cache stale → re-purge)
```

---

## File Structure

| File | Responsibility |
|---|---|
| `build/mu-plugins/gv-assets/gv-brand.css` | Design tokens; restyle `.gv-cta` (gold), `.gv-footer` (minimalist); new `.gv-cta__trust`, `.gv-btn--gold`, `.gv-flow` |
| `build/pages/training-programs.html` | Hero, 3 program cards, 3 detail sections, venue/day schedule, pricing message, real photos, gold CTA + trust row |
| `build/pages/home.html` | Program-card mirror, gold CTA copy, real hero photo |
| `build/pages/about.html` | Coach portrait + method photos, location line, real hero photo, gold CTA copy |
| `build/pages/faq.html` | Location answer line only |
| `build/pages/gallery.html` | Location line only |
| `build/scripts/build-functional.php` | Contact page: 3-venue location cards + the booking/payment flow graphic on `/book-a-consultation/` |
| `build/templates/footer.html` | Minimalist premium footer + new locations + gold accents |
| `build/scripts/build-extras.php` | Footer rebuild script (already deploys footer + keeps conditions) — update to emit new `footer.html` |
| `build/mu-plugins/gv-otp-email.php` | Email footer location string |
| `build/mu-plugins/gv-request-form.php` | Email footer location string |
| `build/scripts/setup-latepoint.php` | 3 locations + work periods (backup first) |
| `build/assets/photos/` | Optimized WebP outputs (repo-tracked for reproducibility) |
| `PROJECT_LOG.md`, `PROGRESS_LOG.md`, `CLAUDE.md` | Changelog, client summary, locations/booking reconciliation |

---

## Task 1: Photo pipeline — optimize, rename, upload

**Files:**
- Create: `build/assets/photos/*.webp` (optimized outputs)
- Source: `revisions/gino.jpeg`, `revisions/basketball-photos/*.jpg|jpeg`
- Upload target: WP media library → `/wp-content/uploads/2026/07/`

**Interfaces:**
- Produces (URLs later tasks reference, all under `https://gvbasketball.com/wp-content/uploads/2026/07/`):
  `gv-coach-gino-portrait.webp`, `gv-private-1on1.webp`, `gv-elite-competitive.webp`,
  `gv-skills-session.webp`, `gv-youth-group.webp`, `gv-group-training.webp`,
  `gv-coaching-athlete.webp`, `gv-home-hero-real.webp`, `gv-about-hero-real.webp`,
  `gv-programs-hero-real.webp`.

- [ ] **Step 1: Confirm an image tool is available**

Run: `which cwebp || which magick || which convert`
Expected: a path prints. If none, `brew install webp imagemagick`.

- [ ] **Step 2: Create output dir and optimize media crops (portrait-friendly, ~1200px long edge, q82, strip EXIF)**

```bash
cd /Users/rico/Git/gvbasketball
mkdir -p build/assets/photos
# helper: convert <src> <out> — auto-orient, resize long edge 1200, webp q82
conv(){ magick "$1" -auto-orient -strip -resize '1200x1200>' -quality 82 "build/assets/photos/$2"; }
conv revisions/gino.jpeg                       gv-coach-gino-portrait.webp
conv revisions/basketball-photos/IMG_7536.jpg  gv-private-1on1.webp
conv revisions/basketball-photos/IMG_7535.jpg  gv-elite-competitive.webp
conv revisions/basketball-photos/IMG_7534.jpg  gv-skills-session.webp
conv revisions/basketball-photos/IMG_5504.jpeg gv-youth-group.webp
conv revisions/basketball-photos/IMG_7533.jpg  gv-group-training.webp
conv revisions/basketball-photos/IMG_4720.jpg  gv-coaching-athlete.webp
ls -la build/assets/photos/
```
Expected: 7 `.webp` files, each well under 400 KB.
(If `magick` absent, use `cwebp -q 82 -resize 1200 0 <src> -o <out>` after `mogrify -auto-orient`.)

- [ ] **Step 3: Build the three widescreen HERO images (16:9). Try a smart crop first; outpaint only if the subject is lost**

```bash
cd /Users/rico/Git/gvbasketball
# Attempt widescreen crops (gravity center, 1920x1080) for the three heroes:
hero(){ magick "$1" -auto-orient -strip -resize '1920x1080^' -gravity "$3" -extent 1920x1080 -quality 82 "build/assets/photos/$2"; }
hero revisions/basketball-photos/IMG_7532.jpg gv-home-hero-real.webp    North   # dusk driveway
hero revisions/gino.jpeg                       gv-about-hero-real.webp   North   # coach portrait — wide
hero revisions/basketball-photos/IMG_7534.jpg gv-programs-hero-real.webp Center # cone dribble action
```
Then **open each hero webp and judge it** (Read the file). Acceptance: subject visible, not awkwardly cropped, works behind a dark overlay + left-aligned hero text.

- [ ] **Step 4: For any hero that fails Step 3's judgment, outpaint to 16:9 with `/codex-imagegen`**

Invoke the `codex-imagegen` skill with a prompt like:
> "Extend this basketball training photo to a 16:9 widescreen composition. Keep it photographic and realistic, preserve the existing subjects and gym lighting, extend only the background/court. Do not add faces, logos, or text."
Save the result over the corresponding `build/assets/photos/gv-*-hero-real.webp` (re-encode to webp q82 if needed). Re-judge.

- [ ] **Step 5: Upload all optimized images to the media library as admin**

```bash
cd /Users/rico/Git/gvbasketball
for f in build/assets/photos/*.webp; do scp "$f" "gvweb:~/$(basename "$f")"; done
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  for f in ~/gv-*.webp; do wp media import "$f" --user=1; rm "$f"; done' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: each import prints `Success: Imported file ... as attachment ID N`.

- [ ] **Step 6: Verify the canonical URLs resolve (200)**

```bash
for u in gv-coach-gino-portrait gv-private-1on1 gv-elite-competitive gv-skills-session \
         gv-youth-group gv-group-training gv-coaching-athlete gv-home-hero-real \
         gv-about-hero-real gv-programs-hero-real; do
  echo -n "$u: "; curl -s -o /dev/null -w "%{http_code}\n" \
    "https://gvbasketball.com/wp-content/uploads/2026/07/$u.webp"; done
```
Expected: `200` for each. If a filename got a `-1` suffix (dupe), note the actual URL and use it in later tasks.

- [ ] **Step 7: Commit**

```bash
git add build/assets/photos/
git commit -m "assets: optimize + add real coach/training photos (webp)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: Design tokens + gold CTA + trust row + `.gv-flow` (CSS)

**Files:**
- Modify: `build/mu-plugins/gv-assets/gv-brand.css` (tokens block ~L10-12; `.gv-cta` ~L190-193; add new rules)

**Interfaces:**
- Produces CSS classes later tasks use: `.gv-btn--gold`, `.gv-cta__divider`, `.gv-cta__watermark`,
  `.gv-cta__trust`, `.gv-cta__trust-item`, `.gv-flow`, `.gv-flow__step`, `.gv-flow__num`.

- [ ] **Step 1: Add tokens** — in the `:root{...}` block, after `--gv-orange-dark:#d9690f;`:

```css
  --gv-gold:#C9A24B;
  --gv-gold-soft:#E4C77E;
  --gv-navy-black:#0A1B33;
```

- [ ] **Step 2: Replace the `.gv-cta` rules** (the block at ~L190-193) with the gold treatment:

```css
.gv-cta{background:linear-gradient(150deg,var(--gv-navy-black) 0%,var(--gv-navy-deep) 60%,#0d2649 100%);color:#fff;border-radius:var(--gv-radius-lg);padding:60px 48px;text-align:center;position:relative;overflow:hidden;}
.gv-cta::after{content:"";position:absolute;right:-70px;bottom:-70px;width:260px;height:260px;background:radial-gradient(circle,rgba(201,162,75,.28),transparent 70%);pointer-events:none;}
.gv-cta .gv-eyebrow{color:var(--gv-gold);}
.gv-cta .gv-section-title{color:#fff;position:relative;}
.gv-cta .gv-lead{color:#c6d2e8;margin:0 auto 8px;position:relative;max-width:46ch;}
.gv-cta__watermark{position:absolute;top:-30px;right:24px;font-family:var(--gv-font-display);font-size:15rem;line-height:1;color:rgba(255,255,255,.04);pointer-events:none;user-select:none;z-index:0;}
.gv-cta__divider{display:flex;align-items:center;justify-content:center;gap:14px;margin:2px 0 18px;position:relative;}
.gv-cta__divider::before,.gv-cta__divider::after{content:"";height:1px;width:54px;background:linear-gradient(90deg,transparent,var(--gv-gold));}
.gv-cta__divider::after{background:linear-gradient(90deg,var(--gv-gold),transparent);}
.gv-cta__divider svg{width:26px;height:26px;color:var(--gv-gold);flex:none;}
.gv-cta .gv-btn-row{position:relative;z-index:1;}
.gv-btn--gold{background:var(--gv-gold);color:var(--gv-navy-black);box-shadow:0 10px 24px -10px rgba(201,162,75,.8);}
.gv-btn--gold:hover{background:var(--gv-gold-soft);color:var(--gv-navy-black);transform:translateY(-2px);}
.gv-cta .gv-btn--ghost{border:1.5px solid var(--gv-gold);color:var(--gv-gold);background:transparent;}
.gv-cta .gv-btn--ghost:hover{background:rgba(201,162,75,.12);color:var(--gv-gold-soft);border-color:var(--gv-gold-soft);}
.gv-cta__trust{display:flex;flex-wrap:wrap;justify-content:center;gap:14px 34px;margin-top:34px;padding-top:28px;border-top:1px solid rgba(201,162,75,.22);position:relative;z-index:1;}
.gv-cta__trust-item{display:flex;flex-direction:column;align-items:center;gap:8px;min-width:120px;}
.gv-cta__trust-item svg{width:26px;height:26px;color:var(--gv-gold);stroke:var(--gv-gold);fill:none;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;}
.gv-cta__trust-item span{font-family:var(--gv-font-head);font-weight:700;font-size:.74rem;letter-spacing:.12em;text-transform:uppercase;color:#e7edf7;}
@media(max-width:560px){.gv-cta{padding:44px 22px;}.gv-cta__watermark{font-size:9rem;}.gv-cta__trust{gap:14px 20px;}.gv-cta__trust-item{min-width:44%;}}
```

- [ ] **Step 3: Add the `.gv-flow` component** (booking/payment graphic) at end of file:

```css
/* Booking flow (informational) */
.gv-flow{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;counter-reset:gvflow;margin-top:8px;}
.gv-flow__step{position:relative;background:#fff;border:1px solid var(--gv-light);border-radius:14px;padding:26px 18px 22px;text-align:center;box-shadow:0 12px 30px -22px rgba(2,31,81,.5);}
.gv-flow__num{counter-increment:gvflow;font-family:var(--gv-font-display);font-size:2rem;line-height:1;color:var(--gv-orange);display:block;margin-bottom:10px;}
.gv-flow__num::before{content:counter(gvflow,decimal-leading-zero);}
.gv-flow__step h4{font-family:var(--gv-font-head);font-weight:700;font-size:.92rem;text-transform:uppercase;letter-spacing:.04em;color:var(--gv-navy);margin:0 0 8px;}
.gv-flow__step p{font-size:.86rem;color:var(--gv-steel);margin:0;line-height:1.5;}
.gv-flow__note{margin-top:18px;text-align:center;font-size:.9rem;color:var(--gv-steel);}
@media(max-width:820px){.gv-flow{grid-template-columns:1fr 1fr;}}
@media(max-width:480px){.gv-flow{grid-template-columns:1fr;}}
```

- [ ] **Step 4: Deploy CSS** — run DEPLOY-CSS.

- [ ] **Step 5: Verify tokens are live**

Run VERIFY-LIVE with PATH=`/wp-content/mu-plugins/gv-assets/gv-brand.css` and GREP-STRING=`--gv-gold:#C9A24B`.
Expected: prints `--gv-gold:#C9A24B`.

- [ ] **Step 6: Commit**

```bash
git add build/mu-plugins/gv-assets/gv-brand.css
git commit -m "style: add gold tokens, premium CTA treatment, booking-flow component

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Reusable CTA block (define once, reuse verbatim)

**This is the exact CTA markup used identically in Tasks 4, 5, 6.** Copy it verbatim into each page's CTA `<section>`, replacing the existing `.gv-cta` inner markup.

```html
<div class="gv-cta">
  <span class="gv-cta__watermark" aria-hidden="true">GV</span>
  <span class="gv-eyebrow">Start Your Development Journey</span>
  <div class="gv-cta__divider" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="10"/><path d="M12 2a15 15 0 0 0 0 20M12 2a15 15 0 0 1 0 20M2 12h20"/></svg>
  </div>
  <h2 class="gv-section-title">Ready To Become Your Best?</h2>
  <p class="gv-lead">Every athlete has a next level. Let's build your path.</p>
  <div class="gv-btn-row" style="justify-content:center;">
    <a class="gv-btn gv-btn--gold" href="/book-a-consultation/">Request Training</a>
    <a class="gv-btn gv-btn--ghost" href="https://ig.me/m/gvbasketballl" target="_blank" rel="noopener">Message on Instagram</a>
  </div>
  <div class="gv-cta__trust">
    <div class="gv-cta__trust-item"><svg viewBox="0 0 24 24"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg><span>Personalized Plan</span></div>
    <div class="gv-cta__trust-item"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg><span>Measurable Progress</span></div>
    <div class="gv-cta__trust-item"><svg viewBox="0 0 24 24"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1Z"/></svg><span>Elite Standards</span></div>
    <div class="gv-cta__trust-item"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.5 12.5 17 22l-5-3-5 3 1.5-9.5"/></svg><span>Results That Last</span></div>
  </div>
</div>
```

- [ ] No deploy step — this is a shared snippet. Proceed to Task 4.

---

## Task 4: Training Programs page rewrite

**Files:**
- Modify: `build/pages/training-programs.html` (full rewrite of hero, 3 cards, 3 detail sections, locations section, pricing line, CTA)
- Consumes: photo URLs (Task 1), CSS classes (Task 2), CTA snippet (Task 3)
- Deploy: DEPLOY-PAGE with FILE=`training-programs`, ID=`2981`

- [ ] **Step 1: Hero** — replace lines 4-15 `<section class="gv-hero">…` so the hero uses the real photo and new copy:

```html
  <section class="gv-hero">
    <div class="gv-hero__bg" style="background-image:url('https://gvbasketball.com/wp-content/uploads/2026/07/gv-programs-hero-real.webp');"></div>
    <div class="gv-hero__overlay"></div>
    <div class="gv-wrap">
      <div class="gv-hero__inner" style="padding:88px 0 72px;">
        <span class="gv-eyebrow">Training Programs</span>
        <h1 class="gv-h1">Choose Your Path to Elite Performance</h1>
        <div class="gv-hero__rule" style="margin-top:24px;"></div>
        <p class="gv-lead">Purpose-built programs for athletes committed to reaching their full potential.</p>
      </div>
    </div>
  </section>
```

- [ ] **Step 2: Program overview grid** — replace the three `.gv-program` cards (lines 20-54 inner) with:

```html
      <div class="gv-grid gv-grid--3">
        <div class="gv-program">
          <div class="gv-program__head"><div class="gv-program__ic"><svg viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><h3 class="gv-program__name">Private Training</h3><div class="gv-program__for">1-on-1 · By appointment</div></div>
          <div class="gv-program__body">
            <ul class="gv-list">
              <li>1-on-1 individualized coaching</li>
              <li>Available by appointment</li>
              <li>Ages 3 to professional athletes</li>
            </ul>
            <div class="gv-program__foot"><a class="gv-btn gv-btn--outline" href="/book-a-consultation/">Request Training</a></div>
          </div>
        </div>
        <div class="gv-program">
          <div class="gv-program__head gv-program__head--accent"><div class="gv-program__ic"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><h3 class="gv-program__name">Small Group</h3><div class="gv-program__for">Maximum 6 athletes · Ages 5–20</div></div>
          <div class="gv-program__body">
            <ul class="gv-list">
              <li>Game-like, competitive drills</li>
              <li>All skill levels welcome — drills are adapted to each player</li>
              <li>Skill development + team concepts</li>
            </ul>
            <div class="gv-program__foot"><a class="gv-btn gv-btn--outline" href="/book-a-consultation/">Request Training</a></div>
          </div>
        </div>
        <div class="gv-program">
          <div class="gv-program__head"><div class="gv-program__ic"><svg viewBox="0 0 24 24"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg></div><h3 class="gv-program__name">Elite Performance</h3><div class="gv-program__for">By appointment</div></div>
          <div class="gv-program__body">
            <ul class="gv-list">
              <li>Basketball, strength &amp; conditioning, and aqua training</li>
              <li>An integrated performance system</li>
              <li>Built for next-level athletes</li>
            </ul>
            <div class="gv-program__foot"><a class="gv-btn gv-btn--outline" href="/book-a-consultation/">Request Training</a></div>
          </div>
        </div>
      </div>
```

- [ ] **Step 3: Private Training detail** — set the img and rewrite the copy (lines 58-87 region):

```html
  <section class="gv-section">
    <div class="gv-wrap">
      <div class="gv-split gv-split--media-right">
        <div class="gv-split__media">
          <img src="https://gvbasketball.com/wp-content/uploads/2026/07/gv-private-1on1.webp" alt="Coach Gino working one-on-one with an athlete" loading="lazy">
        </div>
        <div>
          <h2 class="gv-section-title">Private Training</h2>
          <div class="gv-rule"></div>
          <h3 class="gv-subhead">Who It's For</h3>
          <p>Exclusive one-on-one coaching for athletes — from ages 3 to professional — pursuing elite-level development through a fully customized performance plan. Every rep, every cue, every session is built around one athlete.</p>
          <h3 class="gv-subhead">What's Included</h3>
          <ul class="gv-list">
            <li>Player evaluation (movement, skills, mindset)</li>
            <li>Fully personalized development plan</li>
            <li>1-on-1 individualized coaching each session</li>
            <li>Maximum coach attention and reps</li>
            <li>Progress tracking and regular feedback</li>
          </ul>
          <h3 class="gv-subhead">Scheduling</h3>
          <p>Available by appointment. We'll match frequency and timing to your goals during your consultation.</p>
          <div class="gv-btn-row" style="margin-top:28px;">
            <a class="gv-btn gv-btn--primary" href="/book-a-consultation/">Request Training</a>
          </div>
        </div>
      </div>
    </div>
  </section>
```

- [ ] **Step 4: Small Group detail** — img + copy (lines 89-118 region):

```html
  <section class="gv-section gv-section--light">
    <div class="gv-wrap">
      <div class="gv-split">
        <div class="gv-split__media">
          <img src="https://gvbasketball.com/wp-content/uploads/2026/07/gv-youth-group.webp" alt="Coach Gino with a small group of young athletes" loading="lazy">
        </div>
        <div>
          <h2 class="gv-section-title">Small Group Training</h2>
          <div class="gv-rule"></div>
          <h3 class="gv-subhead">Who It's For</h3>
          <p>Athletes ages 5–20 who thrive with peer energy and competition. Groups are capped at a maximum of 6 so every player gets quality reps. All skill levels are welcome — drills are adapted to each player.</p>
          <h3 class="gv-subhead">What's Included</h3>
          <ul class="gv-list">
            <li>Game-like, competitive drills</li>
            <li>Maximum of 6 athletes per group</li>
            <li>Drills adapted to each player's skill level</li>
            <li>Skill development + team concepts</li>
            <li>Coach feedback on every player</li>
          </ul>
          <h3 class="gv-subhead">Locations &amp; Days</h3>
          <p>Dasma, Makati — Mon, Wed &amp; Thu · Urdaneta Village — Fri &amp; Sun · Corinthian Gardens — Sun.</p>
          <div class="gv-btn-row" style="margin-top:28px;">
            <a class="gv-btn gv-btn--primary" href="/book-a-consultation/">Request Training</a>
          </div>
        </div>
      </div>
    </div>
  </section>
```

- [ ] **Step 5: Elite Performance detail** — img + copy (lines 120-149 region):

```html
  <section class="gv-section">
    <div class="gv-wrap">
      <div class="gv-split gv-split--media-right">
        <div class="gv-split__media">
          <img src="https://gvbasketball.com/wp-content/uploads/2026/07/gv-elite-competitive.webp" alt="Athlete driving past the coach in a competitive drill" loading="lazy">
        </div>
        <div>
          <h2 class="gv-section-title">Elite Performance</h2>
          <div class="gv-rule"></div>
          <h3 class="gv-subhead">Who It's For</h3>
          <p>Serious athletes preparing for the next level of competition. Basketball, strength &amp; conditioning, and aqua training combine into one integrated performance system.</p>
          <h3 class="gv-subhead">What's Included</h3>
          <ul class="gv-list">
            <li>Basketball skills training (offensive &amp; defensive)</li>
            <li>Strength &amp; conditioning</li>
            <li>Aqua training</li>
            <li>Recovery, mobility, and injury prevention</li>
            <li>Structured, periodized progression</li>
          </ul>
          <h3 class="gv-subhead">Scheduling</h3>
          <p>Available by appointment, designed for athletes ready to commit to next-level development.</p>
          <div class="gv-btn-row" style="margin-top:28px;">
            <a class="gv-btn gv-btn--primary" href="/book-a-consultation/">Request Training</a>
          </div>
        </div>
      </div>
    </div>
  </section>
```

- [ ] **Step 6: Locations & Schedule section** — replace the whole section (lines 151-181) with the venue/day table (drops the old age-band time grid; keeps pricing message):

```html
  <section class="gv-section gv-section--light">
    <div class="gv-wrap">
      <div class="gv-head-block gv-center">
        <span class="gv-eyebrow">Locations &amp; Schedule</span>
        <h2 class="gv-section-title">Where We Train</h2>
        <p class="gv-lead">Small-group sessions run across Metro Manila. Private &amp; Elite Performance are scheduled by appointment.</p>
      </div>
      <div class="gv-grid gv-grid--3">
        <div class="gv-card">
          <div class="gv-card__icon"><svg viewBox="0 0 24 24"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></div>
          <h3 class="gv-card__title">Dasma, Makati</h3>
          <p style="color:var(--gv-steel);">Mon, Wed &amp; Thu</p>
        </div>
        <div class="gv-card">
          <div class="gv-card__icon"><svg viewBox="0 0 24 24"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></div>
          <h3 class="gv-card__title">Urdaneta Village</h3>
          <p style="color:var(--gv-steel);">Fri &amp; Sun</p>
        </div>
        <div class="gv-card">
          <div class="gv-card__icon"><svg viewBox="0 0 24 24"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></div>
          <h3 class="gv-card__title">Corinthian Gardens</h3>
          <p style="color:var(--gv-steel);">Sun</p>
        </div>
      </div>
      <div class="gv-center" style="margin-top:32px;">
        <p style="color:var(--gv-steel);font-size:.95rem;max-width:60ch;margin:0 auto;">Each athlete receives a personalized recommendation based on age, skill level, goals, and training frequency. Program options and investment are discussed during your consultation to ensure the best fit.</p>
      </div>
    </div>
  </section>
```

- [ ] **Step 7: CTA** — replace the inner of the final `<section class="gv-section gv-section--tight">` (lines 200-212) with the **Task 3 CTA snippet**.

- [ ] **Step 8: Deploy** — DEPLOY-PAGE, FILE=`training-programs`, ID=`2981`.

- [ ] **Step 9: Verify** — VERIFY-LIVE PATH=`/training-programs/` GREP-STRING=`Choose Your Path to Elite Performance`; then GREP-STRING=`Maximum of 6 athletes`; then GREP-STRING=`aqua training`; then GREP-STRING=`Corinthian Gardens`. Each must print. Then open `https://gvbasketball.com/training-programs/` in a browser: confirm hero photo shows, three cards, venue table, gold CTA with 4 trust badges, no "4–5 athletes" or "3–4 PM" anywhere.

- [ ] **Step 10: Commit**

```bash
git add build/pages/training-programs.html
git commit -m "content: rewrite Training Programs (new copy, venues, photos, gold CTA)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 5: Home page — cards mirror, hero photo, gold CTA

**Files:**
- Modify: `build/pages/home.html` (hero bg L5; program preview cards L64-84; CTA L112-120)
- Deploy: DEPLOY-PAGE FILE=`home` ID=`2887`

- [ ] **Step 1: Hero background** — change L5 `background-image` URL to `https://gvbasketball.com/wp-content/uploads/2026/07/gv-home-hero-real.webp` (keep everything else in the hero).

- [ ] **Step 2: Program preview cards** — replace the three `.gv-program` card bodies (L64-84) so copy matches Training Programs:

```html
        <div class="gv-program">
          <div class="gv-program__head"><div class="gv-program__ic"><svg viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><h3 class="gv-program__name">Private Training</h3><div class="gv-program__for">1-on-1 · By appointment</div></div>
          <div class="gv-program__body">
            <p>Exclusive one-on-one coaching for athletes ages 3 to professional — a fully customized performance plan.</p>
            <div class="gv-program__foot"><a class="gv-btn gv-btn--outline" href="/book-a-consultation/">Request Training</a></div>
          </div>
        </div>
        <div class="gv-program">
          <div class="gv-program__head gv-program__head--accent"><div class="gv-program__ic"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><h3 class="gv-program__name">Small Group</h3><div class="gv-program__for">Maximum 6 athletes · Ages 5–20</div></div>
          <div class="gv-program__body">
            <p>Game-like, competitive drills — all skill levels welcome, with drills adapted to each player, plus team concepts.</p>
            <div class="gv-program__foot"><a class="gv-btn gv-btn--outline" href="/book-a-consultation/">Request Training</a></div>
          </div>
        </div>
        <div class="gv-program">
          <div class="gv-program__head"><div class="gv-program__ic"><svg viewBox="0 0 24 24"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg></div><h3 class="gv-program__name">Elite Performance</h3><div class="gv-program__for">By appointment</div></div>
          <div class="gv-program__body">
            <p>Basketball, strength &amp; conditioning, and aqua training — an integrated performance system built for next-level athletes.</p>
            <div class="gv-program__foot"><a class="gv-btn gv-btn--outline" href="/book-a-consultation/">Request Training</a></div>
          </div>
        </div>
```

- [ ] **Step 3: CTA** — replace inner of the final CTA section (L112-120) with the **Task 3 CTA snippet**.

- [ ] **Step 4: Deploy** — DEPLOY-PAGE FILE=`home` ID=`2887`.

- [ ] **Step 5: Verify** — VERIFY-LIVE PATH=`/` GREP-STRING=`Maximum 6 athletes`; then `aqua training`; then `Ready To Become Your Best?`. Browser-check the home hero shows the real photo and the gold CTA renders.

- [ ] **Step 6: Commit**

```bash
git add build/pages/home.html
git commit -m "content: home program cards + real hero + gold CTA

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 6: About page — portrait, method photo, hero, location line, CTA

**Files:**
- Modify: `build/pages/about.html` (hero bg L5; story portrait img L35; location line L27; method img L102; CTA L155-163)
- Deploy: DEPLOY-PAGE FILE=`about` ID=`26`

- [ ] **Step 1: Hero background** — L5 URL → `https://gvbasketball.com/wp-content/uploads/2026/07/gv-about-hero-real.webp`.

- [ ] **Step 2: Story portrait** — L35 img → `src="https://gvbasketball.com/wp-content/uploads/2026/07/gv-coach-gino-portrait.webp" alt="Coach Gino Victorino"`.

- [ ] **Step 3: Location line** — replace L27 sentence:

```html
          <p>He wanted to bring NBA-level systems to every young athlete across Metro Manila — training in Dasma Makati, Urdaneta Village, and Corinthian Gardens. Not showboating. Not shortcuts. Real development.</p>
```

- [ ] **Step 4: Method photo** — L102 img → `src="https://gvbasketball.com/wp-content/uploads/2026/07/gv-coaching-athlete.webp" alt="Coach Gino guiding an athlete through a ball-handling drill"`.

- [ ] **Step 5: CTA** — replace inner of the final CTA section (L155-163) with the **Task 3 CTA snippet**.

- [ ] **Step 6: Deploy** — DEPLOY-PAGE FILE=`about` ID=`26`.

- [ ] **Step 7: Verify** — VERIFY-LIVE PATH=`/about/` GREP-STRING=`gv-coach-gino-portrait`; then `Corinthian Gardens`; then `Ready To Become Your Best?`. Browser-check portrait + method photos load and hero shows the real photo. Confirm no "Makati and Ortigas".

- [ ] **Step 8: Commit**

```bash
git add build/pages/about.html
git commit -m "content: about real photos, new locations, gold CTA

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 7: FAQ + Gallery location strings

**Files:**
- Modify: `build/pages/faq.html:37`; `build/pages/gallery.html:37`
- Deploy: DEPLOY-PAGE FILE=`faq` ID=`2988`; DEPLOY-PAGE FILE=`gallery` ID=`2987`

- [ ] **Step 1: FAQ location answer** — replace the L37 answer text (only this answer; leave all other FAQ answers untouched — client is revising those separately):

```html
        <details class="gv-acc__item"><summary>Where are you located?</summary><div class="gv-acc__a">We train across Metro Manila — Dasma Makati (Mon, Wed &amp; Thu), Urdaneta Village (Fri &amp; Sun), and Corinthian Gardens (Sun) for small groups. Private and Elite Performance sessions are by appointment. We'll confirm the most convenient venue during your consultation.</div></details>
```

- [ ] **Step 2: Gallery location line** — replace L37 lead:

```html
        <p class="gv-lead">See daily training highlights, athlete wins, and behind-the-scenes moments from our sessions across Metro Manila. New content posted regularly.</p>
```

- [ ] **Step 3: Deploy both** — DEPLOY-PAGE FILE=`faq` ID=`2988`; then DEPLOY-PAGE FILE=`gallery` ID=`2987`.

- [ ] **Step 4: Verify** — VERIFY-LIVE PATH=`/faq/` GREP-STRING=`Corinthian Gardens`; VERIFY-LIVE PATH=`/gallery/` GREP-STRING=`across Metro Manila`. Both print. Confirm no "Makati and Ortigas" on either.

- [ ] **Step 5: Commit**

```bash
git add build/pages/faq.html build/pages/gallery.html
git commit -m "content: update FAQ + gallery location references

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 8: Contact page — 3-venue location cards + booking/payment flow graphic

**Files:**
- Modify: `build/scripts/build-functional.php` (L71 schedule blurb; L144 lead; L146-147 location cards → 3 venue cards; add `.gv-flow` block to the book-a-consultation page section)
- Deploy: `wp eval-file ~/build-functional.php`

- [ ] **Step 1: Read the script** to locate the book-a-consultation page section and the contact/locations section.

Run: `grep -n "book\|Locations\|Our Locations\|latepoint_book_form\|gv_set_page" build/scripts/build-functional.php`
Expected: line numbers for the booking-page builder and the locations block.

- [ ] **Step 2: Location lead + schedule blurb** — replace L71 and L144 text:
  - L71 blurb → `<div class="gv-card"><h3 class="gv-card__title">Schedule Options</h3><p>Small-group days across Metro Manila (Dasma Makati, Urdaneta Village, Corinthian Gardens); Private &amp; Elite by appointment.</p></div>`
  - L144 lead → `<div class="gv-head-block gv-center"><span class="gv-eyebrow">Where We Train</span><h2 class="gv-section-title">Our Locations</h2><p class="gv-lead">Small-group sessions run across Metro Manila. Your exact venue is confirmed at your consultation.</p></div>`

- [ ] **Step 3: Location cards** — replace the two Makati/Ortigas cards (L146-147) with three venue cards:

```php
<div class="gv-card"><div class="gv-card__icon"><svg viewBox="0 0 24 24"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></div><h3 class="gv-card__title">Dasma, Makati</h3><p>Small-group sessions — Mon, Wed &amp; Thu.</p></div>
<div class="gv-card"><div class="gv-card__icon"><svg viewBox="0 0 24 24"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></div><h3 class="gv-card__title">Urdaneta Village</h3><p>Small-group sessions — Fri &amp; Sun.</p></div>
<div class="gv-card"><div class="gv-card__icon"><svg viewBox="0 0 24 24"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg></div><h3 class="gv-card__title">Corinthian Gardens</h3><p>Small-group sessions — Sun.</p></div>
```
(If the grid was `gv-grid--2`, change it to `gv-grid--3` for three cards.)

- [ ] **Step 4: Booking/payment flow graphic** — in the book-a-consultation page builder (the section that outputs `[latepoint_book_form]`), add this block **above** the booking form shortcode:

```php
$booking_flow = '<section class="gv-section gv-section--light"><div class="gv-wrap">
<div class="gv-head-block gv-center"><span class="gv-eyebrow">How Booking Works</span><h2 class="gv-section-title">Simple, Personal, Secure</h2><p class="gv-lead">Booking starts here on the site — everything else we handle with you directly.</p></div>
<div class="gv-flow">
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Book Online</h4><p>Choose your session or consultation and submit your details.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>We Confirm</h4><p>GV Basketball reaches out to finalize your slot and answer questions.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Reserve Your Spot</h4><p>Payment is arranged directly with GV Basketball — handled personally, not on this site.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Booking Confirmed</h4><p>Once payment is received, your session is locked in.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Train</h4><p>Show up and get to work — your development starts.</p></div>
</div>
<p class="gv-flow__note">Payments are handled directly with GV Basketball — no payment or bank details are collected on this website.</p>
</div></section>';
```
Then concatenate `$booking_flow` before the existing booking-form widget/HTML in that page's `gv_set_page_html`/`gv_set_page_blocks` call. Match the script's existing string-building style.

- [ ] **Step 5: Deploy**

```bash
scp build/scripts/build-functional.php gvweb:~/build-functional.php
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval-file ~/build-functional.php && wp elementor flush-css && wp litespeed-purge all && rm ~/build-functional.php' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: script prints its success/echo lines, no PHP errors.

- [ ] **Step 6: Verify** — VERIFY-LIVE PATH=`/book-a-consultation/` GREP-STRING=`How Booking Works`; then `no payment or bank details`. VERIFY-LIVE PATH=`/contact/` GREP-STRING=`Corinthian Gardens`. Browser-check the flow graphic renders as 5 numbered steps and the booking form still loads below it.

- [ ] **Step 7: Commit**

```bash
git add build/scripts/build-functional.php
git commit -m "feat: 3-venue contact locations + informational booking-flow graphic

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 9: Footer — minimalist premium rebuild + locations + gold

**Files:**
- Modify: `build/templates/footer.html` (full rebuild); `build/scripts/build-extras.php` (ensure it reads/emits the new footer.html)
- Deploy: DEPLOY-FOOTER (rebuilds GV Footer 2991, keeps conditions)

- [ ] **Step 1: Inspect the footer build script** to confirm how it injects `footer.html`.

Run: `grep -n "footer\|gv_set_theme_part\|file_get_contents\|conditions" build/scripts/build-extras.php`
Expected: the line that calls `gv_set_theme_part('GV Footer','footer', <html>)`. Note whether the HTML is inline in the PHP or read from a file. **If inline**, the new footer markup must be pasted into `build-extras.php` (not just `footer.html`).

- [ ] **Step 2: Rewrite `build/templates/footer.html`** to the minimalist premium layout (fewer columns, gold hairline, updated locations):

```html
<div class="gv-footer">
  <div class="gv-footer__top gv-footer__top--minimal">
    <div class="gv-footer__brandcol">
      <div style="font-family:var(--gv-font-display);font-size:2rem;color:#fff;letter-spacing:1px;line-height:1;">GV<span style="color:var(--gv-gold);">BASKETBALL</span></div>
      <p class="gv-footer__tag" style="margin-top:14px;">Develop Elite Players.<br>Shape Exceptional Individuals.</p>
      <p style="color:#aeb9cf;max-width:38ch;">Basketball skills training across Metro Manila — Dasma Makati, Urdaneta Village &amp; Corinthian Gardens.</p>
      <div class="gv-footer__socials">
        <a href="https://instagram.com/gvbasketballl" target="_blank" rel="noopener" title="Instagram" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.43.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.43.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.72 3.72 0 0 1-1.38-.9 3.72 3.72 0 0 1-.9-1.38c-.16-.43-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.43-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63a5.88 5.88 0 0 0-2.13 1.38A5.88 5.88 0 0 0 .63 4.14C.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.31.79.72 1.46 1.38 2.13a5.88 5.88 0 0 0 2.13 1.38c.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56a5.88 5.88 0 0 0 2.13-1.38 5.88 5.88 0 0 0 1.38-2.13c.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91a5.88 5.88 0 0 0-1.38-2.13A5.88 5.88 0 0 0 19.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0Zm0 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84ZM12 16a4 4 0 1 1 4-4 4 4 0 0 1-4 4Zm6.41-10.85a1.44 1.44 0 1 0 1.44 1.44 1.44 1.44 0 0 0-1.44-1.44Z"/></svg></a>
      </div>
    </div>
    <div class="gv-footer__linkcol">
      <h4>Explore</h4>
      <ul>
        <li><a href="/">Home</a></li>
        <li><a href="/about/">About Coach Gino</a></li>
        <li><a href="/training-programs/">Training Programs</a></li>
        <li><a href="/faq/">FAQ</a></li>
        <li><a href="/contact/">Contact</a></li>
      </ul>
    </div>
    <div class="gv-footer__linkcol">
      <h4>Connect</h4>
      <address class="gv-footer__contact">
        <strong style="color:#fff;">Metro Manila</strong><br>
        Dasma Makati · Urdaneta Village · Corinthian Gardens<br><br>
        <a href="mailto:gvbasketballcoaching@gmail.com">gvbasketballcoaching@gmail.com</a><br>
        <a href="https://ig.me/m/gvbasketballl" target="_blank" rel="noopener">Message @gvbasketballl</a>
      </address>
      <a class="gv-footer__cta" href="/book-a-consultation/">Request Training</a>
    </div>
  </div>
  <div class="gv-footer__bar">
    <div class="gv-footer__bar-inner">
      <span>© 2026 GV Basketball. All rights reserved.</span>
      <span><a href="/booking/">Member Login</a> · <a href="/waiver/">Waiver</a> · <a href="/contact/">Contact</a></span>
    </div>
  </div>
</div>
```

- [ ] **Step 3: Add footer minimalist CSS** to `build/mu-plugins/gv-assets/gv-brand.css` (after the existing `.gv-footer__contact` rules) so the 3-column premium layout + gold hairline apply:

```css
.gv-footer__top--minimal{grid-template-columns:1.6fr 1fr 1.3fr;border-top:1px solid rgba(201,162,75,.28);}
@media(max-width:820px){.gv-footer__top--minimal{grid-template-columns:1fr 1fr;}}
@media(max-width:560px){.gv-footer__top--minimal{grid-template-columns:1fr;}}
.gv-footer__top--minimal .gv-footer__socials a:hover{background:var(--gv-gold);color:var(--gv-navy-black);}
.gv-footer__top--minimal a:hover{color:var(--gv-gold);}
.gv-footer__cta{display:inline-block;margin-top:16px;background:var(--gv-gold);color:var(--gv-navy-black);font-family:var(--gv-font-head);font-weight:700;text-transform:uppercase;letter-spacing:.03em;font-size:.82rem;padding:11px 20px;border-radius:8px;}
.gv-footer__cta:hover{background:var(--gv-gold-soft);color:var(--gv-navy-black);}
```

- [ ] **Step 4: If Step 1 showed the footer HTML is inline in `build-extras.php`, paste the new markup there too** (so `wp eval-file` emits it). Keep the conditions-preserving code intact.

- [ ] **Step 5: Deploy CSS then footer** — run DEPLOY-CSS, then DEPLOY-FOOTER.

- [ ] **Step 6: Verify** — VERIFY-LIVE PATH=`/` GREP-STRING=`gv-footer__top--minimal`; then `Dasma Makati · Urdaneta Village · Corinthian Gardens`. Browser-check footer looks cleaner/premium with gold accents, Instagram-only social, no WhatsApp/Facebook, no "Makati & Ortigas".

- [ ] **Step 7: Commit**

```bash
git add build/templates/footer.html build/scripts/build-extras.php build/mu-plugins/gv-assets/gv-brand.css
git commit -m "style: minimalist premium footer with gold accents + new locations

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 10: Email footers (OTP + request-form)

**Files:**
- Modify: `build/mu-plugins/gv-otp-email.php:49`; `build/mu-plugins/gv-request-form.php:39`
- Deploy: `scp` both to `wp-content/mu-plugins/`

- [ ] **Step 1: Update both location strings** — replace `Makati &amp; Ortigas, Metro Manila` with `Metro Manila` in each file's footer line (keep the surrounding `@gvbasketballl` link markup unchanged).

- [ ] **Step 2: Deploy**

```bash
DEST=/home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins
scp build/mu-plugins/gv-otp-email.php build/mu-plugins/gv-request-form.php gvweb:$DEST/
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && wp litespeed-purge all' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```

- [ ] **Step 3: Verify (no send needed)** — confirm the deployed files contain the new string:

```bash
ssh gvweb 'grep -c "Makati" /home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins/gv-otp-email.php /home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins/gv-request-form.php' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: `:0` for both (no more "Makati" reference). Optionally test OTP send per CLAUDE.md.

- [ ] **Step 4: Commit**

```bash
git add build/mu-plugins/gv-otp-email.php build/mu-plugins/gv-request-form.php
git commit -m "content: update email footer location strings to Metro Manila

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 11: LatePoint locations + work periods reconfiguration

**Files:**
- Modify: `build/scripts/setup-latepoint.php` (locations L26-27 → 3 venues; work periods per venue)
- Backup: `~/latepoint-backup-<date>/*.tsv` on server
- Deploy: `wp eval-file ~/setup-latepoint.php`

**Interfaces:**
- Consumes: nothing from prior tasks. Produces: LatePoint locations Dasma Makati / Urdaneta Village / Corinthian Gardens with day-appropriate work periods; Private/Elite bookable by appointment.

- [ ] **Step 1: BACK UP LatePoint tables first** (⚠️ `wp db export` is broken on this host — table-scoped backup only):

```bash
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  mkdir -p ~/latepoint-backup && \
  for t in latepoint_settings latepoint_agents latepoint_locations latepoint_services latepoint_work_periods; do \
    wp db query "SELECT * FROM wp_$t;" > ~/latepoint-backup/$t.tsv; done && \
  ls -la ~/latepoint-backup/' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: 5 non-empty `.tsv` files. **Do not proceed if any table dump is empty/errored.**

- [ ] **Step 2: Read the current setup script** to learn its `gv_loc()` helper and work-period model.

Run: `sed -n '1,80p' build/scripts/setup-latepoint.php`
Expected: see `gv_loc(...)`, service definitions, work-period assignment (weekday 1=Mon…7=Sun, minutes-of-day).

- [ ] **Step 3: Replace the locations (L26-27)** with three venues:

```php
$dasma   = gv_loc('Dasma, Makati', 'Dasmariñas Village, Makati, Metro Manila');
$urdaneta= gv_loc('Urdaneta Village', 'Urdaneta Village, Makati, Metro Manila');
$corinth = gv_loc('Corinthian Gardens', 'Corinthian Gardens, Quezon City, Metro Manila');
```
Update every downstream reference to `$makati`/`$ortigas` (search the file) to the new variables. Assign services to locations so all four services (Consultation/Private/Small Group/Elite) are bookable; Small Group is the venue/day-driven one.

- [ ] **Step 4: Set work periods to the client's day pattern** — for the Small Group service (and general availability), configure per-venue days (weekday: Mon=1, Wed=3, Thu=4, Fri=5, Sun=7):
  - Dasma → days 1,3,4
  - Urdaneta → days 5,7
  - Corinthian → day 7
  Use a sensible daytime window (e.g. 900–1080 mins = 3:00–6:00 PM, matching the prior config) unless the script models per-service times differently. Private & Elite ("by appointment"): keep broad weekday availability so consultations can be booked; exact timing is confirmed off-platform.

  > If the LatePoint model in this script can't cleanly express per-venue day restrictions, set a single reasonable availability and record the limitation in the commit + `PROJECT_LOG.md` (venue/day nuance is communicated in page copy, which is authoritative).

- [ ] **Step 5: Deploy**

```bash
scp build/scripts/setup-latepoint.php gvweb:~/setup-latepoint.php
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval-file ~/setup-latepoint.php && wp litespeed-purge all && rm ~/setup-latepoint.php' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: script echoes location/service IDs, no fatal errors.

- [ ] **Step 6: Verify locations**

```bash
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp db query "SELECT id,name FROM wp_latepoint_locations;" ' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: rows for Dasma, Makati / Urdaneta Village / Corinthian Gardens. Then open `https://gvbasketball.com/book-a-consultation/` and confirm the booking form lists the three venues and completes a test selection (don't submit a real booking). Confirm OTP login still works per CLAUDE.md if the portal is touched.

- [ ] **Step 7: Commit**

```bash
git add build/scripts/setup-latepoint.php
git commit -m "feat: reconfigure LatePoint to Dasma/Urdaneta/Corinthian venues

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 12: Docs, final sweep, and handoff

**Files:**
- Modify: `PROJECT_LOG.md`, `PROGRESS_LOG.md`, `CLAUDE.md`

- [ ] **Step 1: Global "Makati & Ortigas" sweep** — confirm nothing live still shows the old two-city story:

```bash
cd /Users/rico/Git/gvbasketball && grep -rn "Ortigas" build/ | grep -v "testimonials.html"
```
Expected: **no results** (testimonials is the only allowed remaining ref, and it's a hidden page). Fix any stragglers, redeploy that file, re-verify.

- [ ] **Step 2: Cross-page CTA + gold audit** — confirm gold is confined to CTA + footer and content buttons stayed orange:

```bash
for p in / /about/ /training-programs/; do echo "== $p =="; \
  curl -s "https://gvbasketball.com$p" | grep -o "gv-btn--gold\|gv-cta__trust" | sort -u; done
```
Expected: each of `/`, `/about/`, `/training-programs/` shows `gv-btn--gold` and `gv-cta__trust`.

- [ ] **Step 3: Update `CLAUDE.md`** — reconcile: (a) §4 Site structure "Booking (LatePoint)" locations → Dasma/Urdaneta/Corinthian; (b) §6 Conventions "Locations Makati & Ortigas" → the three venues / Metro Manila; (c) add a one-line note that a booking/payment **informational** flow graphic lives on `/book-a-consultation/` and the site collects **no** payment/bank details.

- [ ] **Step 4: Update `PROJECT_LOG.md`** — add a dated entry summarizing: gold CTA + trust row, minimalist footer, program rewrite, venue/schedule sweep, LatePoint reconfig (with backup path), real photo set, booking-flow graphic. Note the LatePoint per-venue-day limitation if one was hit in Task 11.

- [ ] **Step 5: Update `PROGRESS_LOG.md`** — plain-language client summary of what changed this round and what's still pending from them (FAQ answers, testimonials + photos).

- [ ] **Step 6: Commit docs**

```bash
git add CLAUDE.md PROJECT_LOG.md PROGRESS_LOG.md
git commit -m "docs: log 2026-07-09 revisions (programs, venues, gold CTA, footer, booking flow)

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

- [ ] **Step 7: Final browser QA pass** — load `/`, `/about/`, `/training-programs/`, `/book-a-consultation/`, `/contact/`, `/faq/`, `/gallery/` and the footer on each. Confirm against spec §9 success criteria: new copy, real photos, gold CTA + badges, premium footer, venues everywhere, booking flow graphic, booking form works. Note anything for the client (e.g. a hero that would benefit from a future landscape reshoot).

---

## Self-Review (author's check against spec)

**Spec coverage:**
- §3.1 Training Programs → Task 4 ✓ · §3.2 Home → Task 5 ✓ · §3.3 About → Task 6 ✓
- §4 Locations sweep → Tasks 4,6,7,8,9,10 ✓ · LatePoint → Task 11 ✓
- §5 Photos → Task 1 ✓ (URLs consumed in 4,5,6) · §6 Gold CTA/footer → Tasks 2,3,4,5,6,9 ✓
- §5 pricing message → Task 4 Step 6 ✓ · §7 Booking flow → Task 2 (CSS) + Task 8 ✓
- §8 inventory → all files have a task ✓ · §9 success criteria → Task 12 QA ✓
- Out-of-scope (FAQ answers, testimonials, member-login, payments) → untouched by design ✓

**Placeholder scan:** No TBD/TODO. Two conditional judgment points (Task 1 Step 4 hero outpaint; Task 9 Step 1/4 inline-vs-file footer; Task 11 Step 4 LatePoint day model) each give explicit criteria + fallback — not placeholders.

**Type/name consistency:** CSS classes defined in Task 2 (`gv-btn--gold`, `gv-cta__trust`, `gv-cta__watermark`, `gv-cta__divider`, `gv-flow*`) are the exact classes used in Tasks 3, 8, 9. Photo filenames defined in Task 1 match the `src`/`background-image` URLs in Tasks 4–6. Venue names identical across all tasks.
