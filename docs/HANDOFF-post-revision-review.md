# Handoff — Post-Revision Review (2026-07-09)

Review of the implemented fixes for the eight client-flagged items (home / footer / CTA).
Verified against the **live site** (`https://gvbasketball.com/`) and the committed source.
Spec: [superpowers/specs/2026-07-09-home-footer-cta-fixes.md](superpowers/specs/2026-07-09-home-footer-cta-fixes.md).

## Verdict
All 8 requirements are deployed and functioning. **Two need rework** (hero resolution + an
unscoped global grayscale/sepia photo treatment applied on top), and a few smaller items need a
visual check. Nothing is broken — these are quality/scope revisions.

## Requirement status

| # | Requirement | Status | Live evidence |
|---|---|---|---|
| R1 | Gino side-by-side with Phil & Micah | ✅ Done | `gv-grid--3`, Coach Gino card first, "Founder & Head Coach", section eyebrow "Coach & Mentors" |
| R2 | Premium, non-pixelated hero | ⚠️ **Rework** | New `gv-home-hero-v2.webp` is live but only **1024×1024** and rendered **grayscale** — see C1/C2 |
| R3 | Real GV logo watermark (not letters) | ✅ Done (confirm) | `gvbasketball-logo-white.svg` `<img>` at 6% opacity — verify it still reads as the emblem, see L1 |
| R4 | Gold CTA keeps white text on hover | ✅ Done | Live CSS: `.gv-page a.gv-btn--gold,…:hover{color:#fff}` now out-specifies the generic anchor hover |
| R5 | Subscribe button gold | ✅ Done | Live CSS: newsletter submit `background:var(--gv-gold)!important`, `:hover` gold-soft |
| R6 | Footer real logo (not text) | ✅ Done (confirm) | `gvbasketball-long-white.svg` `<img>` — confirm legibility on navy, see L2 |
| R7 | Move locations Contact → Home | ✅ Done | "Our Locations" present on `/`, **0** occurrences on `/contact/` |
| R8 | "Book a Consultation" not "Request Training" | ✅ Done | Live home: **0** "Request Training", **11** "Book a Consultation"; contact clean. Two internal identifiers intentionally retained (plugin name, Turnstile widget) |

---

## Revision comments (prioritized)

### C1 — HIGH: New hero is only 1024×1024 → still soft/pixelated (defeats R2)
- **Where:** `gv-home-hero-v2.webp` (1024×1024 square, ~54 KB), wired at [build/pages/home.html:5](../build/pages/home.html#L5).
- **Problem:** The client's complaint was pixelation on the landing hero. The replacement is a **square, low-resolution** AI image. The hero renders full-bleed with `background-size:cover` over `min-height:82vh`, so a 1024px-wide square is **upscaled and heavily cropped** on any desktop viewport — it will read as soft/pixelated again, i.e. R2 is not actually resolved.
- **Fix:** Regenerate/source a **landscape ≥2400px-wide (16:9)** hero and re-upload as a new file (mtime/name change to bust cache). Target ~2560×1440. Keep the real-photo look. Then update the URL at home.html:5.

### C2 — HIGH (decision): Hero forced to grayscale + luminosity blend, globally
- **Where:** commit `fc6b57b`, [build/mu-plugins/gv-assets/gv-brand.css](../build/mu-plugins/gv-assets/gv-brand.css) `.gv-hero__bg` — `filter:grayscale(100%) contrast(1.15) brightness(0.9); mix-blend-mode:luminosity; opacity:.62 (home)`.
- **Problem:** A "branded photo filter" pass landed **after** the hero swap and forces **every** hero to grayscale, then blends it into the navy background. Net effect: the brand-new hero shows as a faded monochrome wash — the effort to get a premium *color* hero is not visible, and this is a site-wide visual change the client did not request in this round.
- **Decision needed:** Confirm the grayscale/editorial treatment is intended. If not, drop/soften the filter on `.gv-hero--home .gv-hero__bg` (e.g. remove `grayscale(100%)` and `mix-blend-mode:luminosity`, keep a subtle contrast bump) so the new hero shows in color. Applies to all page heroes if changed globally.

### M1 — MEDIUM: Gino portrait aspect-ratio crop
- **Where:** `gv-coach-gino-portrait.webp` is **900×1200 (portrait 3:4)**; `.gv-person__img` uses `aspect-ratio:4/3; object-fit:cover`.
- **Problem:** A tall portrait forced into a 4:3 landscape box gets center-cropped — likely cutting off top-of-head or lower framing. Phil/Micah are landscape sources so they're fine; Gino may look awkwardly cropped.
- **Fix:** Visually check Gino's card. If cropped poorly, either supply a landscape-framed Gino photo or set `object-position` on that image so the crop favors the face.

### M2 — MEDIUM (decision): Mentor photos permanently sepia-tinted, no hover reset
- **Where:** commit `fc6b57b`, `.gv-person__img{…filter:contrast(1.08) saturate(0.9) brightness(0.98) sepia(8%)}`.
- **Problem:** All three coach/mentor photos (incl. the new Gino portrait) are permanently desaturated + 8% sepia. Unlike `.gv-split__media` and `.gv-gallery` — which got a **hover reset to natural color** in the same commit — `.gv-person__img` has **no** hover reset, so mentor faces never show true color. This is an inconsistency and slightly dulls the new portrait.
- **Decision needed:** Confirm the tint is wanted on people photos. It does help unify Phil (.webp) and Micah (.jpg) which are different sources; if kept, consider adding the same hover-reset for consistency, or reduce sepia to keep skin tones natural.

### L1 — LOW: Confirm watermark reads as the GV emblem
- The CTA watermark is now the real monogram SVG but at `opacity:.06`. That's an appropriately subtle watermark, but the client's ask was that it "reflect the actual GV logo." Confirm it's recognizably the emblem at that opacity; nudge to ~.08–.10 if they want it more present.

### L2 — LOW: Footer logo legibility + height nit
- Confirm `gvbasketball-long-white.svg` reads cleanly on the navy footer. Minor: the inline `style="height:40px"` on the `<img>` overrides `.gv-footer__logo{height:38px}` (CSS line ~298) — harmless, but pick one for tidiness.

### L3 — LOW (awareness): Contact page is now lighter
- With locations moved to Home, `/contact/` is down to Instagram + email + form. Intended per R7, but flag for the client in case they want a short "we train across Metro Manila" line to remain there.

---

## Verified working (no action)
- Gold "Book a Consultation" CTA: text stays **white** on hover (specificity bug fixed). ✅
- Subscribe button: gold, gold-soft on hover. ✅
- CTA copy: zero site-facing "Request Training" remaining; header, program cards, footer all read "Book a Consultation". ✅
- Locations removed from Contact, present on Home. ✅
- 3-up mentor grid stacks correctly (existing `.gv-grid--3` responsive rules). ✅

## Suggested next actions (in order)
1. **C1** — regenerate a ≥2400px-wide 16:9 hero, re-upload, repoint home.html:5, flush caches.
2. **C2 & M2** — confirm the grayscale/sepia photo treatment with the client; adjust `.gv-hero__bg` / `.gv-person__img` filters per their call.
3. **M1** — eyeball Gino's crop; fix `object-position` or reframe if needed.
4. **L1/L2/L3** — quick visual confirms.

**Deploy reminder for any change:** edit in `build/` → `scp` → apply with the matching `gv_*` helper/script → `wp elementor flush-css && wp litespeed-purge all` (hard-refresh past Cloudflare/LiteSpeed to verify).
