# Design Spec — GV Basketball Client Revisions (2026-07-09)

**Source:** `revisions/2026-07-09-revision-client.md` + `revisions/basketball-photos/` + `revisions/gino.jpeg`
**Status:** Approved design — ready for implementation plan
**Author:** Claude (fable-mode + superpowers:brainstorming)

---

## 0. Context & guardrails

The site is hand-written HTML + a shared CSS design system (`gv-brand.css`) mounted into
Elementor via mu-plugins. Workflow: **edit `build/` → `scp` to server → apply with a `gv_*` helper →
flush caches** (`wp elementor flush-css && wp litespeed-purge all`). See `CLAUDE.md` for the golden
workflow, deploy commands, page IDs, and gotchas. This spec does **not** restate those — the
implementation plan will reference the exact commands.

**Hard guardrails (from CLAUDE.md, do not violate):**
- Never print secret values; `.env` stays out of git.
- `wp db export` fails on this host → snapshot the specific table with `wp db query ... > backup.tsv`
  before any LatePoint DB change.
- Keep Cloudflare `rocket_loader` OFF; never add a "Cache Everything" rule.
- Pricing is never shown publicly. No online payments. WhatsApp/Facebook stay off the site.
- Don't reintroduce demo content; media library holds GV brand assets only.
- Brand voice: disciplined, confident, developmental. Don't invent stats, named athletes, or schools.
- SVG uploads need Safe SVG + `--user=1`.

**Client's overriding intent:** elevate the brand toward a **premium, high-performance academy** feel —
"less like a basketball clinic, more like an elite athlete development academy."

---

## 1. Scope

### In scope (this round)
1. Training Programs content rewrite (Private / Small Group / Elite) — item 1 + Revisions 1–3.
2. Pricing messaging refresh (kept hidden) — item 5.
3. Real Coach Gino / training photos — audit, optimize, rename, place — item 3.
4. New locations & schedule sweep + LatePoint reconfiguration — item 1.
5. Premium elevation: scoped **gold** CTA + minimalist footer (Revision 4 / 4b).
6. Booking & payment **informational** graphic near booking areas — "Flow of Purchase".

### Out of scope (client sending separately / deferred)
- **FAQ answers** (item 2) — only the *location* line is corrected; answer edits wait for client.
- **Testimonials** (item 4) — page 2986 stays hidden until client sends real ones with photos.
- **Member login / scheduling / session tracking** (item 6) — stays as current simple OTP portal;
  no build-out this version.
- **Online payments** — the site never touches payments (see §7).

---

## 2. Decisions locked with client

| # | Decision |
|---|---|
| Gold branding | **Scoped**: gold accent applied to the shared `.gv-cta` block + footer only. Orange stays primary elsewhere. Reversible. |
| Purchase flow | **Informational only.** Website handles booking/consultation; payments handled by GV directly, off-site. Site shows **no bank details** — only what to expect. Present as a graphic near booking areas. |
| Photos | Audit all now, rename by descriptive label, map to placements. Use real photos in existing hero layouts; extend portrait shots to widescreen with `/codex-imagegen` where a crop won't work. |
| Locations | Update **everywhere** (site copy + email footers) **and reconfigure LatePoint**. |
| Heroes | Keep existing hero *layout/framing*; swap placeholder backgrounds for real photos. |

---

## 3. Content revisions (exact copy)

### 3.1 Training Programs page (post 2981) — `build/pages/training-programs.html`

**Hero (Revision 3):**
- Eyebrow: `Training Programs`
- H1: `Choose Your Path to Elite Performance`
- Lead: `Purpose-built programs for athletes committed to reaching their full potential.`

**Program overview grid (3 cards):**

*Private Training* (`gv-program__for`: `1-on-1 · By appointment`)
- 1-on-1 individualized coaching
- Available by appointment
- Ages 3 to professional athletes

*Small Group* (accent card; `gv-program__for`: `Maximum 6 athletes · Ages 5–20`) — **Revision 1 messaging:**
- Game-like, competitive drills
- All skill levels welcome — drills are adapted to each player
- Skill development + team concepts

*Elite Performance* (`gv-program__for`: `By appointment`) — **Revision 2 messaging:**
- Basketball, strength & conditioning, and aqua training — an integrated performance system built
  for next-level athletes.

**Detail sections (rewrite to match):**
- *Private Training* — "Who it's for": exclusive one-on-one coaching for athletes (ages 3 through
  professional) pursuing elite-level development through a customized performance plan. Frequency:
  **by appointment**. Photo: `gv-private-1on1.webp`.
- *Small Group* — max **6** athletes (was 4–5), ages **5–20**, all skill levels welcome (drills
  adapted per player), game-like competitive reps + team concepts. Photo: `gv-youth-group.webp`.
- *Elite Performance* — Basketball + Strength & Conditioning + **Aqua Training** as one integrated
  system for serious athletes preparing for the next level. Frequency: **by appointment**. Photo:
  `gv-elite-competitive.webp`. (Add "Aqua training" to the What's Included list.)

**Locations & Schedule section — replace the Makati/Ortigas beginner/intermediate block with:**

| Venue | Days |
|---|---|
| Dasma, Makati | Mon, Wed & Thu |
| Urdaneta Village | Fri & Sun |
| Corinthian Gardens | Sun |

Note under the grid: *Private & Elite Performance sessions are scheduled by appointment.* Use the
client's exact venue names. Keep the existing `gv-card` + map-pin layout (3 cards, or 2-col + note).

**Pricing line (replace existing):**
> Each athlete receives a personalized recommendation based on age, skill level, goals, and training
> frequency. Program options and investment are discussed during your consultation to ensure the
> best fit.

### 3.2 Home page (post 2887) — `build/pages/home.html`
Mirror the three program-preview cards (same copy as 3.1 overview grid): "Maximum 6 athletes",
Small-Group inclusivity line, Elite aqua line. Hero background → real photo (§5). Optionally add a
Coach Gino portrait accent.

### 3.3 About page (post 26) — `build/pages/about.html`
- Story portrait `img` → `gv-coach-gino-portrait.webp`.
- Training-method media → `gv-coaching-athlete.webp` (or `gv-group-training.webp`).
- Line 27 "young athlete in Makati and Ortigas" → new locations phrasing (see §4).
- Hero background → real photo (§5).

### 3.4 Locations copy — see §4.

---

## 4. Locations & schedule sweep

**New model:** three Small-Group venues (Dasma Makati · Urdaneta Village · Corinthian Gardens);
Private & Elite by appointment. Region shorthand where space is tight = **"Metro Manila"** (do not
invent city names beyond the client's exact venue labels).

**Files/strings to update** (found via grep — all "Makati & Ortigas" / two-city references):
- `build/templates/footer.html` (2 refs: tagline L6, address L34)
- `build/scripts/build-functional.php` (contact page: L71 schedule blurb, L144 locations lead,
  L146–147 the two Makati/Ortigas cards → three venue cards)
- `build/pages/about.html` (L27)
- `build/pages/faq.html` (L37 "Where are you located?" answer — location fact only)
- `build/pages/gallery.html` (L37)
- `build/pages/training-programs.html` (L157, 162, 170 — the schedule section, §3.1)
- `build/mu-plugins/gv-otp-email.php` (L49 email footer)
- `build/mu-plugins/gv-request-form.php` (L39 email footer)
- **Skip** `build/pages/testimonials.html` (hidden page).

**LatePoint reconfiguration** — `build/scripts/setup-latepoint.php`:
- Current: locations Makati (1) / Ortigas (2). New: **Dasma Makati, Urdaneta Village,
  Corinthian Gardens**.
- Work periods must reflect the new day pattern where the LatePoint model supports it (weekday
  1=Mon…7=Sun). Small-group availability by venue: Dasma = Mon/Wed/Thu, Urdaneta = Fri/Sun,
  Corinthian = Sun. Private/Elite = by appointment (broad availability or consultation-driven).
- **Backup first:** `wp db query "SELECT * FROM wp_latepoint_settings ..."` and dump the
  `wp_latepoint_agents/locations/services/work_periods` tables to `.tsv` before writing.
- Reconcile the CLAUDE.md "Booking (LatePoint)" section afterward.

> ⚠️ **Open reconciliation:** the old page copy tied schedules to age bands (Beginners 5–12,
> Intermediate 8–30) at fixed times (3–4 PM / 4–6 PM). The new client model is venue+day based with
> no times. The plan will **drop the age-band time grid** in favor of the venue/day table, and keep
> ages as program attributes (Private 3→pro, Small Group 5–20). Flag any leftover time references.

---

## 5. Photos — audit, optimization, placement

**Audit (10 assets, all portrait phone photos, high-res):**

| Source | New filename (`/wp-content/uploads/2026/07/`) | Content | Placement |
|---|---|---|---|
| `gino.jpeg` | `gv-coach-gino-portrait.webp` | Studio-grade portrait, ball in hands | About story portrait; Home coach accent |
| `IMG_7536.jpg` | `gv-private-1on1.webp` | 1-on-1 outdoor defense | Private Training detail |
| `IMG_7535.jpg` | `gv-elite-competitive.webp` | Teen drives past coach (motion) | Elite Performance detail; hero candidate |
| `IMG_7534.jpg` | `gv-skills-session.webp` | Close cone-dribble, goggles | Training Programs intro / Athlete Dev |
| `IMG_5504.jpeg` | `gv-youth-group.webp` | Coach + young kids group | Small Group detail |
| `IMG_7533.jpg` | `gv-group-training.webp` | Warmup w/ kids group | Small Group / About method |
| `IMG_4720/4721/4722.jpg` | `gv-coaching-athlete.webp` (best 1–2) | Roller-dribble coaching series | About "Training Method"; coaching accents |
| `IMG_7532.jpg` | `gv-training-atmosphere.webp` | Dusk driveway session | Home hero background |

**Optimization requirements:**
- Convert to `.webp`, quality ~82, strip EXIF. Two sizes where used both as media and hero:
  a media crop (~1200px long edge) and, for heroes, a widescreen version.
- **Heroes:** portrait → widescreen. Where a crop (via `object-position`) preserves the subject, use
  CSS. Where it doesn't, use `/codex-imagegen` to outpaint/extend the photo to a 16:9 hero-safe
  canvas (keep it photographic, no fabricated faces/logos). Existing hero **layout** stays; only the
  background image changes.
- Upload as admin: `wp media import <file> --user=1` (or scp + reference by URL, matching existing
  pattern `gvbasketball.com/wp-content/uploads/...`).
- Keep source files in `revisions/`; write optimized outputs to a build assets folder (e.g.
  `build/assets/photos/`) for reproducibility.

**Faces / consent note:** photos show minors. Use them as the client supplied (client owns/consented).
Don't caption with names.

---

## 6. Premium elevation — scoped gold (Revision 4 / 4b)

### 6.1 Design tokens (`gv-brand.css`)
Add:
```
--gv-gold:#C9A24B;         /* premium accent (match 4b) */
--gv-gold-soft:#E4C77E;
--gv-navy-black:#0A1B33;   /* near-black CTA base */
```
Do **not** replace `--gv-orange`; orange remains the primary action color site-wide.

### 6.2 CTA block (`.gv-cta`) — restyle to match Revision 4b
The shared `.gv-cta` renders on Home, About, and Training Programs — restyling it rolls out to all
three consistently. Target look:
- Background: deep near-black navy gradient (`--gv-navy-black` → `--gv-navy-deep`).
- Eyebrow + rule: **gold**; small basketball-icon divider (SVG) under the eyebrow.
- Faint oversized **GV watermark** in a corner (low-opacity SVG/text).
- Heading (`.gv-section-title`) white; copy muted.
- Primary button → **gold** fill, dark text (new modifier `gv-btn--gold`, or override inside
  `.gv-cta`). Secondary "Message on Instagram" → **gold-outline** ghost.
- New **trust-badge row** (4 items, gold line-icons): **Personalized Plan · Measurable Progress ·
  Elite Standards · Results That Last**. Add a `.gv-cta__trust` sub-component + markup in each of the
  three CTA blocks.

**Copy update (all three CTA blocks):**
- Eyebrow: `Start Your Development Journey`
- H1: `Ready To Become Your Best?`
- Lead: `Every athlete has a next level. Let's build your path.`
- Buttons: `Request Training` (gold) + `Message on Instagram` (gold-outline, `https://ig.me/m/gvbasketballl`).

### 6.3 Footer (`build/templates/footer.html` + `.gv-footer` CSS) — minimalist premium
Current footer (footer1/footer2) is busy: newsletter band + 4 columns. Target = cleaner, more
premium:
- Simplify to a tighter layout (logo + tagline + one-line description, a compact link set, contact,
  single Instagram). Reduce visual noise.
- Introduce **gold** hairline/accent details (e.g., a thin gold rule, gold hover on links or the
  "BASKETBALL" wordmark) consistent with the CTA. Keep newsletter band but streamline it.
- Update location strings per §4.
- Rebuild via `gv_set_theme_part('GV Footer','footer',$html)` and keep the Theme Builder conditions
  option (deploy script handles it).

### 6.4 Explicitly NOT changed
Nav CTA, hero rules, eyebrows, program accent card, buttons on content pages — all stay orange.

---

## 7. Booking & payment informational graphic ("Flow of Purchase")

**Principle:** the website only **books/initiates**; **GV handles payment directly, off-site**; the
site shows **no bank details** — only a friendly explanation of what to expect.

**Placement:** on `/book-a-consultation/` (post 2982), near the booking form (above or below); and a
compact restatement near the `/booking/` portal (post 2983) if it fits cleanly.

**Component:** a styled, premium horizontal/vertical step flow (reuse `gv-steps` or a new
`gv-flow` component) with 5 steps and icons:

1. **Book online** — Choose your session or consultation and submit your details.
2. **We confirm the details** — GV Basketball reaches out to finalize your slot and answer questions.
3. **Reserve your spot** — Payment is arranged directly with GV Basketball (handled personally,
   not on this site).
4. **Booking confirmed** — Once payment is received, your session is locked in.
5. **Train** — Show up and get to work. Your development starts.

Micro-note beneath: *Payments are handled directly with GV Basketball — no payment or bank details
are collected on this website.*

Tone: reassuring, premium, matches brand voice. Reword the client's raw flow (do not expose
"bank-to-bank" specifics or bank details).

---

## 8. Component/interface inventory (what gets built or touched)

| Unit | File | Change |
|---|---|---|
| Design tokens | `build/mu-plugins/gv-assets/gv-brand.css` | +gold/navy-black tokens; restyle `.gv-cta`, `.gv-footer`; add `.gv-cta__trust`, `gv-btn--gold`, optional `.gv-flow` |
| Training Programs page | `build/pages/training-programs.html` | Hero, 3 cards, 3 detail sections, locations table, pricing line, photos |
| Home page | `build/pages/home.html` | 3 cards mirror, CTA copy, hero photo |
| About page | `build/pages/about.html` | Portrait + method photos, location line, hero photo, CTA copy |
| Contact page builder | `build/scripts/build-functional.php` | Location cards/leads → 3 venues; add booking flow graphic on book page |
| FAQ page | `build/pages/faq.html` | Location answer only |
| Gallery page | `build/pages/gallery.html` | Location line |
| Footer template | `build/templates/footer.html` | Minimalist premium rebuild + locations + gold |
| OTP email | `build/mu-plugins/gv-otp-email.php` | Footer location string |
| Request-form email | `build/mu-plugins/gv-request-form.php` | Footer location string |
| LatePoint setup | `build/scripts/setup-latepoint.php` | 3 locations + work periods (backup first) |
| Photo assets | `build/assets/photos/` + uploads | Optimize/rename/upload 8 images |
| Docs | `PROJECT_LOG.md`, `PROGRESS_LOG.md`, `CLAUDE.md` | Log changes; reconcile locations & booking notes |

---

## 9. Success criteria

- [ ] Training Programs shows new hero headline, three revised cards (max 6, inclusivity line, aqua),
      new venue/day schedule, and the new pricing message. No stale "4–5 athletes" or old time grid.
- [ ] Home program cards match. All CTAs show the gold Revision-4b treatment + trust badges +
      "Ready To Become Your Best?".
- [ ] Footer is visibly more minimalist/premium with gold accents; no WhatsApp/Facebook.
- [ ] Real photos appear on Home hero, About (portrait + method), and all three program details;
      images are WebP, correctly oriented, fast.
- [ ] "Makati & Ortigas" no longer appears anywhere live except intentionally-hidden pages; new
      venues appear in footer, contact, about, FAQ, gallery, and both email footers.
- [ ] LatePoint offers the three new locations; a table backup exists; booking still works end-to-end
      (OTP login, consultation booking).
- [ ] Booking/payment info graphic is live near booking areas and shows **no bank details**.
- [ ] Orange remains primary on nav/content; gold is confined to CTA + footer.
- [ ] `wp elementor flush-css && wp litespeed-purge all` run; pages verified in browser.
- [ ] `PROJECT_LOG.md` / `PROGRESS_LOG.md` / `CLAUDE.md` updated; committed (no `.env`).

---

## 10. Risks & mitigations

- **LatePoint DB change** — snapshot tables first; verify booking + OTP after; document rollback.
- **Portrait→widescreen heroes** — prefer CSS crop; use `/codex-imagegen` outpaint only when needed;
  never fabricate faces/logos.
- **Gold creep** — keep gold strictly in `.gv-cta` + footer; audit that content buttons stay orange.
- **Cache** — always flush Elementor CSS + LiteSpeed; purge Cloudflare only for stale static assets.
- **Scope leakage** — FAQ answers, testimonials, member-login build-out, payments all stay deferred.
