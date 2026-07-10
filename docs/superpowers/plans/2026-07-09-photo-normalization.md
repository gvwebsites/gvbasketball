# Photo Normalization — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make every still-visible content photo on gvbasketball.com share one consistent, on-brand look — the same exposure, contrast, saturation, and a cool-neutral (non-sepia) tone — so the mixed-source imagery (AI-generated webp + real headshots) stops clashing.

**Architecture:** The front end is hand-written HTML mounted into Elementor plus a shared CSS design system (`gv-brand.css`), deployed over SSH. Photos live in the WP media library under `wp-content/uploads/<yyyy>/<mm>/`; the HTML references full-size original URLs directly. There is no build step and no test framework — verification is `grep` / `curl` / `sips` / visual against the live site.

**Tech Stack:** WordPress (Hostinger, PHP 8.2), WP-CLI over SSH `gvweb`, Elementor, hand-written HTML/CSS, LiteSpeed + Cloudflare. Local image tooling: `sips` (built-in), ImageMagick (`magick`), `cwebp` (matches the established `cwebp -q 82` pipeline from PROJECT_LOG 2026-06-27 / 2026-07-09).

---

## 1. Context

### Why
A prior pass (`fc6b57b`, "Warm Hardwood") tried to unify the photos with a single **CSS** filter — `contrast(1.08) saturate(0.9) brightness(0.98) sepia(8%)`. Two problems:

1. **A CSS filter can't rescue individually over/under-exposed source images.** The photos are genuinely mixed-source: a "dark/moody" AI library (`wp-content/uploads/2026/06/gv-*.webp`, generated 2026-06-27), a later optimized-real / AI set (`2026/07/gv-*.webp`), and two **real external headshots** (`2025/07/PHIL_HANDY.webp`, `2025/07/MICAH_LANCASTER-scaled.jpg` — the latter is a **JPG**, everything else is WebP). They differ in exposure, saturation, and color temperature at the pixel level; one multiplicative filter shifts them all by the same amount but leaves the underlying mismatch intact.
2. **The `sepia(8%)` pushes every photo warm/yellow**, which fights the disciplined cool-navy brand. PROJECT_LOG notes the sepia tint (M2) was explicitly left "pending a client decision" — this plan resolves it by dropping sepia.

There is also a **CSS inconsistency** (see §3 in the analysis below): `.gv-person__img` is permanently filtered with **no hover reset**, while `.gv-gallery img` and `.gv-split__media img` reset to neutral on hover. So portraits stay warm while gallery/split photos "snap to true color" under the cursor — three classes, two different behaviors.

### Target "site style" (concrete, measurable)
The single look every content photo must land on:

| Attribute | Target |
|---|---|
| **Tone** | **Cool-neutral. Zero sepia.** A neutral-gray patch should read R≈B within ±3, with a slight cool bias (Blue ≥ Red). No warm/orange cast. |
| **Exposure / brightness** | Normalized so each image's **median luminance lands ~48–55%** (0–100 scale). Black point ≤ 6% (shadows not crushed), white point ≥ 92% (highlights not blown). |
| **Contrast** | Gentle global S-curve, **+5% to +10%** vs. linear (≈ `sigmoidal-contrast 3x50%`). Punchy but not harsh. |
| **Saturation** | **Restrained editorial, ~90% of source** (uniform across all images) — premium, not candy. |
| **Sharpness** | Light unsharp only on soft/upscaled sources; never over-sharpen. |
| **Format / encode** | WebP, `cwebp -q 82`, metadata stripped. (Micah's JPG is the one exception — see Task 6.) |

Brand tokens for reference: navy `#123B78`, deep navy `#021F51`, orange `#F47B20`, gold. The photo tone should sit comfortably beside navy sections, so lean the white balance neutral-to-cool, never warm.

---

## 2. Two candidate approaches + recommendation

### (a) Bake normalization into the image FILES — **RECOMMENDED**
Run a per-image correction pipeline (`magick` → `cwebp`) that levels exposure, applies the target contrast/saturation, and neutralizes white balance **per file**, then re-upload the corrected files in place (overwrite the same `wp-content/uploads/...` path — no URL changes needed) and purge caches.

- **Pros:** Only way to actually fix a photo that is individually too dark or too warm. The correction is tuned per image (an already-neutral photo gets a light touch; a dark/warm one gets more). Result is a genuine match, is CDN-cacheable, and is independent of CSS. Overwriting in place means **no HTML URL edits** for the WebP files.
- **Cons:** More upfront work; each output needs a quick visual check; the two real headshots must first be pulled down from the server (they aren't in the repo).

### (b) Uniform CSS-filter normalization
Replace the three ad-hoc per-class filters with **one** identical `filter:` treatment (and one consistent hover behavior) across `.gv-person__img`, `.gv-gallery img`, `.gv-split__media img`.

- **Pros:** One-line change, instant, reversible, no re-uploads.
- **Cons:** Cannot fix per-image exposure/white-balance mismatch — the core problem. A global multiply darkens an already-dark photo further and can't lift an underexposed one. At best it hides the clash slightly.

### Recommendation
**Do (a) as the real fix, and fold the good half of (b) in as cleanup.** Baking per-image correction is required because the sources differ too much for one filter to reconcile. But we should *also* strip the inconsistent CSS filters so the corrected files become the single source of truth — otherwise the leftover `sepia(8%)` would re-warm the freshly-neutralized files and the person/gallery hover mismatch would remain. So: **Task 1 removes the CSS filters (approach b, but reduced to a clean no-op + consistent hover), Tasks 2–7 bake the corrections (approach a).**

---

## 3. Analysis findings (verified)

### CSS filter rules in `build/mu-plugins/gv-assets/gv-brand.css`
| Line | Selector | Filter | Hover reset? |
|---|---|---|---|
| 119 | `.gv-split__media img` | `contrast(1.08) saturate(0.9) brightness(0.98) sepia(8%)` | **Yes** — line 120 `.gv-split__media:hover img` → `contrast(1) saturate(1) brightness(1) sepia(0%)` |
| 167 | `.gv-person__img` | `contrast(1.08) saturate(0.9) brightness(0.98) sepia(8%)` | **NO reset** — inconsistent |
| 214 | `.gv-gallery img` | `contrast(1.08) saturate(0.9) brightness(0.98) sepia(8%)` | **Yes** — line 215 `.gv-gallery a:hover img` → neutral (also keeps `transform:scale(1.04)`) |
| 93 | `.gv-hero__bg` | `grayscale(100%) contrast(1.15) brightness(0.9)` + `mix-blend-mode:luminosity` | **OUT OF SCOPE** (hero background — being removed by a parallel task; home overrides it to color at `.gv-hero--home .gv-hero__bg`) |
| 356 | reduced-motion block | disables `transform` on hover, **not** `filter` | — |

**Precise inconsistency:** all three content classes carry the identical `sepia(8%)` warm filter, but only `.gv-split__media img` and `.gv-gallery img` get a hover reset to neutral; `.gv-person__img` does not. So the two mentor portraits stay warm permanently while other photos flip to true color on hover.

### Photo usage map (in scope = still-visible content photos)
Hero backgrounds (`.gv-hero__bg`, e.g. `gv-home-hero-v3.webp` and the per-page `-hero` backgrounds) are **out of scope** — see §5.

| # | File (URL path) | Local repo copy | Dims | Used on / class |
|---|---|---|---|---|
| Mentor portraits — `.gv-person__img` | | | | |
| 1 | `2026/07/gv-coach-gino-portrait.webp` | `build/assets/photos/gv-coach-gino-portrait.webp` | 900×1200 | home.html:33 (also a split on about.html:33), about hero split |
| 2 | `2025/07/PHIL_HANDY.webp` | **server only** — pull down | ? (real photo) | home.html:41, about.html:76 |
| 3 | `2025/07/MICAH_LANCASTER-scaled.jpg` | **server only** — pull down | ? (real photo, **JPG**) | home.html:49, about.html:84 |
| Split / section media — `.gv-split__media img` | | | | |
| 4 | `2026/07/gv-coaching-athlete.webp` | `build/assets/photos/gv-coaching-athlete.webp` | 675×1200 | about.html:100 |
| 5 | `2026/07/gv-private-1on1.webp` | `build/assets/photos/gv-private-1on1.webp` | 685×1200 | training-programs.html:61 |
| 6 | `2026/07/gv-youth-group.webp` | `build/assets/photos/gv-youth-group.webp` | 976×1200 | training-programs.html:91 |
| 7 | `2026/07/gv-elite-competitive.webp` | `build/assets/photos/gv-elite-competitive.webp` | 691×1200 | training-programs.html:121 |
| 8 | `2026/06/gv-court.webp` | `build/assets/img/web/gv-court.webp` | 1600×1067 | success-stories.html:67 |
| 9 | `2026/06/gv-film.webp` | `build/assets/img/web/gv-film.webp` | 1100×734 | athlete-development.html:45 |
| 10 | `2026/06/gv-footwork.webp` | `build/assets/img/web/gv-footwork.webp` | 1100×734 | athlete-development.html:56, gallery.html:21 |
| 11 | `2026/06/gv-faq-hero.webp` | `build/assets/img/web/gv-faq-hero.webp` | 1600×901 | athlete-development.html:79 (used as content, not a hero here) |
| 12 | `2026/06/gv-ballhandling.webp` | `build/assets/img/web/gv-ballhandling.webp` | 1100×734 | athlete-development.html:90, gallery.html:20 |
| 13 | `2026/06/gv-net.webp` | `build/assets/img/web/gv-net.webp` | 1100×734 | athlete-development.html:113, gallery.html:22 |
| 14 | `2026/06/gv-elite.webp` | `build/assets/img/web/gv-elite.webp` | 1100×734 | athlete-development.html:124 |
| Gallery — `.gv-gallery img` | | | | |
| 15 | `2026/06/gv-home-hero.webp` | `build/assets/img/web/gv-home-hero.webp` | 1600×901 | gallery.html:19 (content thumb, not the live hero bg) |
| 16 | `2026/06/gv-sneaker.webp` | `build/assets/img/web/gv-sneaker.webp` | 1100×734 | gallery.html:23 |
| 17 | `2026/06/gv-programs-hero.webp` | `build/assets/img/web/gv-programs-hero.webp` | 1600×901 | gallery.html:24 (content thumb) |

**Per-photo "what's off" note:** every file needs an individual visual check, but expected issues by source:
- **`2026/06/gv-*` (files 8–17):** the "dark/moody" AI library — expect under-exposed shadows and low mid-tone luminance; lift exposure, gentle contrast.
- **`2026/07/gv-*` (files 1, 4–7):** later set, generally brighter but variable saturation/warmth — expect white-balance drift; neutralize, restrain saturation.
- **`PHIL_HANDY` / `MICAH` (files 2–3):** real external headshots with unknown exposure and a different camera/color profile from the AI set — the biggest clash risk; match exposure + neutralize WB to the AI set. Micah is a **JPG** (format outlier).
- Note: `gv-faq-hero`, `gv-home-hero`, `gv-programs-hero` are hero-*named* but are used here as **content/gallery** images, so they ARE in scope in these instances.

---

## Global Constraints
- WordPress root: `/home/u907133977/domains/gvbasketball.com/public_html` (run all `wp` from here).
- Uploads base on server: `<root>/wp-content/uploads/` — corrected files overwrite the exact `<yyyy>/<mm>/<file>` path they already occupy, so **no HTML URL changes** are needed for the WebP files.
- SSH prints a harmless post-quantum warning to stderr; filter it with `2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"`.
- After **any** deploy: `wp elementor flush-css && wp litespeed-purge all`. Cloudflare proxies but does not cache HTML; if a static asset looks stale, purge Cloudflare too (or the overwritten image keeps its old URL, so also `wp litespeed-purge all` + Cloudflare purge for that path).
- CSS is cache-busted by file mtime — a fresh `scp` + LiteSpeed purge is enough.
- Overwriting a media original does **not** regenerate WP's `-scaled`/thumbnail derivatives, but the pages reference the full-size originals directly, so overwriting the original is sufficient. (Do not rely on any `-scaled` derivative URL.)
- Commit after each task; keep `.env` out of git. Update `PROJECT_LOG.md` after the work.
- Do **not** touch hero background images (`.gv-hero__bg`) — see §5.

---

### Task 1: CSS cleanup — remove the ad-hoc sepia filters, unify behavior (approach b, reduced to a clean baseline)

Strip the inconsistent per-class filters so the baked corrections (Tasks 2–7) become the single source of truth. Keep only the transitions/hover *scale* that are purely presentational.

**Files:**
- Modify: `build/mu-plugins/gv-assets/gv-brand.css` (lines 119–120, 167, 214–215)

- [ ] **Step 1: Neutralize `.gv-split__media img`** — remove the `filter:` clause and delete the hover-filter reset. Line 119 becomes:
```css
.gv-split__media img{border-radius:var(--gv-radius-lg);box-shadow:var(--gv-shadow);width:100%;object-fit:cover;transition:transform 0.3s ease;}
```
Delete line 120 (`.gv-split__media:hover img{filter:...}`) entirely.

- [ ] **Step 2: Neutralize `.gv-person__img`** — line 167 becomes:
```css
.gv-person__img{aspect-ratio:4/3;object-fit:cover;width:100%;}
```

- [ ] **Step 3: Neutralize `.gv-gallery img`** — keep the zoom-on-hover, drop the filter. Lines 214–215 become:
```css
.gv-gallery img{border-radius:12px;aspect-ratio:1/1;object-fit:cover;width:100%;transition:transform .3s;}
.gv-gallery a:hover img{transform:scale(1.04);}
```

> Rationale: with correction baked into the files, an extra CSS filter would double-apply. Removing it also fixes the person-vs-gallery hover inconsistency (there is now nothing to reset). Leave `.gv-hero__bg` (line 93) and `.gv-hero--home .gv-hero__bg` untouched — hero scope is handled separately.

- [ ] **Step 4: Verify no content-photo `filter:` / `sepia` remains** (hero lines are the only allowed matches):
```bash
grep -n "sepia\|filter:" build/mu-plugins/gv-assets/gv-brand.css
```
Expected: matches only on the `.gv-hero__bg` / `.gv-hero--home .gv-hero__bg` lines; none on `.gv-person__img`, `.gv-gallery img`, or `.gv-split__media img`.

- [ ] **Step 5: Deploy CSS + flush**
```bash
scp build/mu-plugins/gv-assets/gv-brand.css \
  gvweb:/home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins/gv-assets/gv-brand.css
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && wp elementor flush-css && wp litespeed-purge all' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Expected: `Success:` from purge. (At this moment photos briefly show their *raw* uncorrected look — that's expected until Tasks 2–7 land; do the whole plan in one sitting.)

- [ ] **Step 6: Commit**
```bash
git add build/mu-plugins/gv-assets/gv-brand.css
git commit -m "style: remove ad-hoc sepia photo filters (correction now baked into files)"
```

---

### Task 2: Pull the two real mentor headshots into the repo

`PHIL_HANDY.webp` and `MICAH_LANCASTER-scaled.jpg` live only on the server. Get local copies so they can be corrected and version-controlled.

**Files:**
- Create: `build/assets/photos/real/PHIL_HANDY.webp`, `build/assets/photos/real/MICAH_LANCASTER-scaled.jpg`

- [ ] **Step 1: Copy them down from the uploads dir**
```bash
mkdir -p build/assets/photos/real
scp gvweb:/home/u907133977/domains/gvbasketball.com/public_html/wp-content/uploads/2025/07/PHIL_HANDY.webp \
    build/assets/photos/real/PHIL_HANDY.webp
scp gvweb:/home/u907133977/domains/gvbasketball.com/public_html/wp-content/uploads/2025/07/MICAH_LANCASTER-scaled.jpg \
    build/assets/photos/real/MICAH_LANCASTER-scaled.jpg
```

- [ ] **Step 2: Record their dimensions (fills the "?" in the §3 table)**
```bash
for f in build/assets/photos/real/PHIL_HANDY.webp build/assets/photos/real/MICAH_LANCASTER-scaled.jpg; do
  echo -n "$f  "; sips -g pixelWidth -g pixelHeight "$f" 2>/dev/null | awk '/pixelWidth/{w=$2}/pixelHeight/{h=$2}END{print w"x"h}'
done
```
Expected: two `WxH` lines (non-empty). If a file is 0 bytes, the scp failed — re-run.

- [ ] **Step 3: Commit the originals** (so the pre-correction state is captured)
```bash
git add build/assets/photos/real/PHIL_HANDY.webp build/assets/photos/real/MICAH_LANCASTER-scaled.jpg
git commit -m "assets: vendor real mentor headshots (Phil Handy, Micah Lancaster) for normalization"
```

---

### Task 3: Establish the normalization pipeline + tune on a sample

Define one reusable correction command, prove it on the worst-case pair (a dark AI image + a real headshot), and lock the parameters before batching.

**Files:**
- Create: `build/scripts/normalize-photo.sh` (reusable local correction helper — not deployed, dev tool only)

- [ ] **Step 1: Confirm tooling exists**
```bash
which magick cwebp sips
```
Expected: paths for all three. If `magick`/`cwebp` are missing: `brew install imagemagick webp`.

- [ ] **Step 2: Write the correction helper.** Create `build/scripts/normalize-photo.sh`:
```bash
#!/usr/bin/env bash
# Normalize one photo to the GV "cool-neutral premium" look.
# Usage: normalize-photo.sh <input> <output.webp>
# Tunables land the image on: median luma ~48-55%, +~8% contrast,
# ~90% saturation, neutral/slightly-cool white balance, no sepia.
set -euo pipefail
IN="$1"; OUT="$2"
TMP="$(mktemp -t gvnorm).png"
magick "$IN" \
  -colorspace sRGB \
  -auto-level \
  -modulate 100,90,100 \
  -sigmoidal-contrast 3x50% \
  -channel R -evaluate multiply 0.985 +channel \
  -channel B -evaluate multiply 1.02  +channel \
  -strip \
  "$TMP"
cwebp -q 82 -metadata none "$TMP" -o "$OUT" >/dev/null 2>&1
rm -f "$TMP"
echo "wrote $OUT"
```
```bash
chmod +x build/scripts/normalize-photo.sh
```
> `-auto-level` normalizes exposure per image (this is the part CSS can't do). `-modulate 100,90,100` sets saturation to 90%. `-sigmoidal-contrast 3x50%` is the gentle S-curve. The R×0.985 / B×1.02 nudge removes warm cast and adds the slight cool bias. **These are a starting baseline — inspect Step 4 and adjust before batching.**

- [ ] **Step 3: Run the sample pair into a scratch dir**
```bash
mkdir -p /tmp/gvnorm
build/scripts/normalize-photo.sh build/assets/img/web/gv-net.webp /tmp/gvnorm/gv-net.webp
build/scripts/normalize-photo.sh build/assets/photos/real/PHIL_HANDY.webp /tmp/gvnorm/PHIL_HANDY.webp
```

- [ ] **Step 4: Measure + eyeball.** Confirm both outputs land in the target band:
```bash
for f in /tmp/gvnorm/gv-net.webp /tmp/gvnorm/PHIL_HANDY.webp; do
  echo "=== $f ==="
  # mean luminance (0-1) — expect ~0.45-0.58
  magick "$f" -colorspace Gray -format "mean-luma=%[fx:mean]\n" info:
  # per-channel means — R and B should be close (neutral); B >= R (cool)
  magick "$f" -format "R=%[fx:mean.r] G=%[fx:mean.g] B=%[fx:mean.b]\n" info:
done
```
Then open both in Preview (`open /tmp/gvnorm/*.webp`) side by side. Expected: comparable brightness, no yellow cast, natural skin tone on Phil, hardwood reads neutral-brown not orange. If Phil looks too dark/light relative to `gv-net`, adjust `-auto-level` → an explicit `-level 3%,97%` or a small `-modulate <bright>,90,100`, re-run Step 3, re-measure. Lock the final command in the script.

- [ ] **Step 5: Commit the tuned helper**
```bash
git add build/scripts/normalize-photo.sh
git commit -m "tooling: reusable photo-normalization pipeline (cool-neutral premium look)"
```

---

### Task 4: Batch-correct the 2026/06 AI library (`build/assets/img/web/`, in-scope subset)

Correct in place inside the repo (overwriting the local copies), keeping filenames identical so the server overwrite in Task 7 needs no URL edits. Only the in-scope files (§3 files 8–17).

**Files (modify in place):** `build/assets/img/web/{gv-court,gv-film,gv-footwork,gv-faq-hero,gv-ballhandling,gv-net,gv-elite,gv-home-hero,gv-sneaker,gv-programs-hero}.webp`

- [ ] **Step 1: Correct each into a staging dir, then swap in** (staging avoids reading a half-written file):
```bash
mkdir -p /tmp/gvnorm/0626
for f in gv-court gv-film gv-footwork gv-faq-hero gv-ballhandling gv-net gv-elite gv-home-hero gv-sneaker gv-programs-hero; do
  build/scripts/normalize-photo.sh "build/assets/img/web/$f.webp" "/tmp/gvnorm/0626/$f.webp"
done
```

- [ ] **Step 2: Sanity-check the batch means** (all should cluster, not scatter):
```bash
for f in /tmp/gvnorm/0626/*.webp; do
  printf "%-40s " "$(basename $f)"
  magick "$f" -colorspace Gray -format "luma=%[fx:mean]\n" info:
done
```
Expected: every `luma` in ~0.42–0.60. Any outlier → re-run that one file with a tweaked brightness, or note it for the visual check.

- [ ] **Step 3: Eyeball the whole set**, then swap into the repo:
```bash
open /tmp/gvnorm/0626/*.webp   # visually confirm consistency + no sepia
cp /tmp/gvnorm/0626/*.webp build/assets/img/web/
```

- [ ] **Step 4: Commit**
```bash
git add build/assets/img/web/*.webp
git commit -m "assets: normalize 2026/06 AI content photos to cool-neutral look"
```

---

### Task 5: Batch-correct the 2026/07 photo set + Gino portrait (`build/assets/photos/`)

In-scope §3 files 1, 4–7. (Do **not** touch `gv-home-hero-*.webp`, `gv-about-hero-real.webp`, `gv-programs-hero-real.webp` here — those are hero backgrounds, out of scope.)

**Files (modify in place):** `build/assets/photos/{gv-coach-gino-portrait,gv-coaching-athlete,gv-private-1on1,gv-youth-group,gv-elite-competitive}.webp`

- [ ] **Step 1: Correct into staging**
```bash
mkdir -p /tmp/gvnorm/0726
for f in gv-coach-gino-portrait gv-coaching-athlete gv-private-1on1 gv-youth-group gv-elite-competitive; do
  build/scripts/normalize-photo.sh "build/assets/photos/$f.webp" "/tmp/gvnorm/0726/$f.webp"
done
```

- [ ] **Step 2: Measure + eyeball** (same commands as Task 4 Step 2/3, pointed at `/tmp/gvnorm/0726/`). Pay attention to skin tones on the Gino portrait — it must look natural, not grey. Then swap in:
```bash
cp /tmp/gvnorm/0726/*.webp build/assets/photos/
```

- [ ] **Step 3: Commit**
```bash
git add build/assets/photos/gv-coach-gino-portrait.webp build/assets/photos/gv-coaching-athlete.webp \
        build/assets/photos/gv-private-1on1.webp build/assets/photos/gv-youth-group.webp \
        build/assets/photos/gv-elite-competitive.webp
git commit -m "assets: normalize 2026/07 content photos + Gino portrait"
```

---

### Task 6: Correct the two real mentor headshots

The highest clash risk — match their exposure/WB to the corrected AI set. Micah is a JPG; keep it a JPG so its URL (`.jpg`, referenced in home.html + about.html) stays valid and no HTML edit is needed.

**Files (modify in place):** `build/assets/photos/real/PHIL_HANDY.webp`, `build/assets/photos/real/MICAH_LANCASTER-scaled.jpg`

- [ ] **Step 1: Correct Phil (webp path)**
```bash
mkdir -p /tmp/gvnorm/real
build/scripts/normalize-photo.sh build/assets/photos/real/PHIL_HANDY.webp /tmp/gvnorm/real/PHIL_HANDY.webp
```

- [ ] **Step 2: Correct Micah, re-encoding as JPG** (same tone pipeline, JPG out):
```bash
magick build/assets/photos/real/MICAH_LANCASTER-scaled.jpg \
  -colorspace sRGB -auto-level -modulate 100,90,100 -sigmoidal-contrast 3x50% \
  -channel R -evaluate multiply 0.985 +channel -channel B -evaluate multiply 1.02 +channel \
  -strip -quality 86 /tmp/gvnorm/real/MICAH_LANCASTER-scaled.jpg
```

- [ ] **Step 3: Compare the two mentors against a corrected AI reference** — they must look like the same shoot:
```bash
open /tmp/gvnorm/real/PHIL_HANDY.webp /tmp/gvnorm/real/MICAH_LANCASTER-scaled.jpg build/assets/photos/gv-coach-gino-portrait.webp
for f in /tmp/gvnorm/real/PHIL_HANDY.webp /tmp/gvnorm/real/MICAH_LANCASTER-scaled.jpg build/assets/photos/gv-coach-gino-portrait.webp; do
  printf "%-45s " "$(basename $f)"; magick "$f" -colorspace Gray -format "luma=%[fx:mean]\n" info:
done
```
Expected: the three portraits' `luma` within ~0.08 of each other, all neutral-toned. Adjust if a headshot is off. Then swap in:
```bash
cp /tmp/gvnorm/real/PHIL_HANDY.webp build/assets/photos/real/PHIL_HANDY.webp
cp /tmp/gvnorm/real/MICAH_LANCASTER-scaled.jpg build/assets/photos/real/MICAH_LANCASTER-scaled.jpg
```

- [ ] **Step 4: Commit**
```bash
git add build/assets/photos/real/PHIL_HANDY.webp build/assets/photos/real/MICAH_LANCASTER-scaled.jpg
git commit -m "assets: normalize real mentor headshots to match content set"
```

---

### Task 7: Deploy all corrected files in place + purge

Overwrite each server media original at its existing `wp-content/uploads/<yyyy>/<mm>/` path. No URL changes, no `wp media import` (that would create `-1` duplicates).

**Interfaces consumed:** the corrected local files from Tasks 4–6.

- [ ] **Step 1: Push the 2026/06 set** (server path `.../uploads/2026/06/`)
```bash
UP=/home/u907133977/domains/gvbasketball.com/public_html/wp-content/uploads
for f in gv-court gv-film gv-footwork gv-faq-hero gv-ballhandling gv-net gv-elite gv-home-hero gv-sneaker gv-programs-hero; do
  scp "build/assets/img/web/$f.webp" "gvweb:$UP/2026/06/$f.webp"
done
```

- [ ] **Step 2: Push the 2026/07 set** (server path `.../uploads/2026/07/`)
```bash
UP=/home/u907133977/domains/gvbasketball.com/public_html/wp-content/uploads
for f in gv-coach-gino-portrait gv-coaching-athlete gv-private-1on1 gv-youth-group gv-elite-competitive; do
  scp "build/assets/photos/$f.webp" "gvweb:$UP/2026/07/$f.webp"
done
```

- [ ] **Step 3: Push the two mentor headshots** (server path `.../uploads/2025/07/`)
```bash
UP=/home/u907133977/domains/gvbasketball.com/public_html/wp-content/uploads
scp build/assets/photos/real/PHIL_HANDY.webp            "gvweb:$UP/2025/07/PHIL_HANDY.webp"
scp build/assets/photos/real/MICAH_LANCASTER-scaled.jpg "gvweb:$UP/2025/07/MICAH_LANCASTER-scaled.jpg"
```

- [ ] **Step 4: Purge caches** (LiteSpeed + Cloudflare, since the URLs are unchanged the CDN may hold old bytes)
```bash
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && wp litespeed-purge all' \
  2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"
```
Then purge the changed image URLs in Cloudflare (dashboard, or a user-owned token — the `.env` account token can purge cache: `POST /zones/4efc307b.../purge_cache` with `{"purge_everything":true}` is simplest for a one-off).

- [ ] **Step 5: Confirm the server now serves the corrected bytes** (size/hash changed vs. a cached copy):
```bash
for u in "2026/06/gv-net.webp" "2026/07/gv-coach-gino-portrait.webp" "2025/07/PHIL_HANDY.webp" "2025/07/MICAH_LANCASTER-scaled.jpg"; do
  echo -n "$u  "; curl -sI "https://gvbasketball.com/wp-content/uploads/$u?v=$(date +%s)" | grep -i "^content-length\|^HTTP"
done
```
Expected: `HTTP/2 200` for each, with `content-length` matching the corrected local file sizes (compare with `ls -la`).

---

### Task 8: Visual QA + project log

- [ ] **Step 1: Hard-refresh each page with photos and confirm one consistent look** (incognito / cache-bust):
  - `/` (home) — Gino / Phil / Micah portraits match each other; split/section photos match.
  - `/about/` — Gino split, Phil + Micah portraits, coaching-athlete split all cohere.
  - `/training-programs/` — three split photos (private / youth / elite) match.
  - `/athlete-development/` — six step photos match.
  - `/success-stories/` — court split.
  - `/gallery/` — six thumbnails read as one set; hover zoom still works, no color flip.
  Check: no yellow/sepia cast anywhere; portraits no longer "snap to color" on hover (Task 1 removed that); brightness is even across the grid.

- [ ] **Step 2: Confirm no stray filter regressed**
```bash
curl -s "https://gvbasketball.com/wp-content/mu-plugins/gv-assets/gv-brand.css?v=$(date +%s)" | grep -o "sepia([0-9]*%)" | sort -u
```
Expected: only `sepia(0%)`... actually **empty** output (all sepia removed; hero uses grayscale, not sepia). If any `sepia(8%)` appears, Task 1 didn't deploy.

- [ ] **Step 3: Update `PROJECT_LOG.md`** — append a dated entry summarizing: dropped the `sepia(8%)` CSS filters (resolving the deferred M2 tint question), baked a per-image cool-neutral correction into all in-scope content photos (2026/06 + 2026/07 sets + Phil/Micah), deployed in place, hero backgrounds untouched (removed by the parallel hero task). Note the reusable `build/scripts/normalize-photo.sh`.
```bash
git add PROJECT_LOG.md
git commit -m "docs: log site-wide photo normalization"
```

---

## 5. Out of scope — hero background images

Hero background images (`.gv-hero__bg`, e.g. `gv-home-hero-v3.webp`, `gv-about-hero-real.webp`, `gv-programs-hero-real.webp`, and the per-page `-hero` backgrounds rendered behind headlines) are **deliberately excluded**. A parallel task is removing all hero background images and turning heroes into solid navy. Do not correct, re-upload, or re-style those files or the `.gv-hero__bg` / `.gv-hero--home .gv-hero__bg` CSS rules — leave them entirely to the hero task to avoid a merge collision. Note the naming overlap: `gv-home-hero.webp`, `gv-faq-hero.webp`, and `gv-programs-hero.webp` are *also used as content/gallery images* (§3 files 11, 15, 17) — those content instances ARE in scope; only their use as an actual hero background is out.

---

## Self-Review
- **Approach chosen:** bake per-image correction (a) — required because sources differ in exposure/WB at the pixel level — plus the cleanup half of (b) in Task 1 (strip the sepia filters so files are the single source of truth and the person/gallery hover inconsistency disappears).
- **CSS inconsistency fixed:** Task 1 removes the `sepia(8%)` from all three content classes and deletes the two hover-resets, so `.gv-person__img` (previously no reset) and `.gv-gallery`/`.gv-split` (had resets) now behave identically. Hero rules untouched.
- **Coverage:** all 17 in-scope photos are corrected (Tasks 4–6) and redeployed in place (Task 7); the two server-only real headshots are pulled first (Task 2). Micah's JPG format preserved to avoid an HTML URL edit.
- **No URL churn:** in-place overwrite means zero HTML changes; caches purged (LiteSpeed + Cloudflare) since URLs are stable.
- **Hero scope respected:** §5 + explicit "do not touch `.gv-hero__bg`" notes prevent collision with the parallel hero-removal task.
- **No test framework** exists; TDD's cycle is adapted to correct → `magick`/`sips` measure → `curl`/visual verify per task, with a commit closing each.
