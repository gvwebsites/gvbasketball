# Spec — Post-Revision Home / Footer / CTA Fixes

**Date:** 2026-07-09
**Site:** https://gvbasketball.com (WordPress on Hostinger; hand-written HTML + `gv-brand.css`, deployed over SSH per [CLAUDE.md](../../../CLAUDE.md))

## Problem
After the latest revision round, the client flagged eight polish issues on the home page, footer, and shared CTA components. All are quality/copy fixes to the existing design system — no booking/auth/functional logic changes.

## Requirements (confirmed with client)

| # | Requirement | Acceptance |
|---|---|---|
| R1 | Coach Gino appears on the home page **side by side** with Phil Handy and Micah Lancaster | Mentor grid is 3-up; Gino first, framed as *Founder & Head Coach*; Phil/Micah remain his NBA mentors; stacks on mobile |
| R2 | Home hero is premium, not pixelated | A new **AI-generated** high-res hero replaces `gv-home-hero-real.webp`; crisp at 2× DPR; brand overlay preserved |
| R3 | "Ready To Become Your Best?" watermark uses the **real** GV logo (not the letters "GV") | Watermark renders the GV **monogram** emblem, muted/faint, on the navy CTA panel |
| R4 | Gold CTA button keeps **white** text on hover (currently turns orange) | Hovering any `gv-btn--gold` → text stays white, never orange |
| R5 | Newsletter **Subscribe** button is gold (it is a CTA) | Subscribe submit button background = brand gold, gold-soft on hover |
| R6 | Footer "GVBASKETBALL" wordmark uses the **real** logo, not text | Footer brand column shows the GV wordmark logo image (white variant on dark footer) |
| R7 | Locations block **moves** from Contact → Home | 3-venue "Our Locations" section present on Home; **removed** from `/contact/` |
| R8 | CTA copy prefers "Book a Consultation" over "Request Training" — **everywhere** site-facing (buttons, eyebrows, headings, page titles, menu, prose) | Zero **site-facing** occurrences of "Request Training" remain; replaced with "Book a Consultation". Two **internal identifiers** are intentionally retained: the plugin name in `gv-request-form.php:3` and the Turnstile widget name in `setup-turnstile.sh:22` (renaming the latter would orphan the live sitekey) |

## Constraints
- Edit source in `build/`, deploy over SSH; flush with `wp elementor flush-css && wp litespeed-purge all`.
- Brand tokens: navy `#123B78`/`#021F51`, gold `--gv-gold`, orange `--gv-orange`. Fonts Bebas/Montserrat/Inter.
- SVG uploads require **Safe SVG** plugin + `--user=1` (admin).
- Instagram-only contact; no pricing; do not re-add WhatsApp/Facebook.
- No automated test suite exists — verification is visual + `grep`/`curl` against the live site (hard refresh past Cloudflare + LiteSpeed).
- Reuse existing components: `gv-person`, `gv-grid--3`, `gv-card`, `gv-cta`, `gv-btn--gold`, `gv-newsletter-band`.

## Assets required (source of truth: [logo/](../../../logo/), [build/assets/photos/](../../../build/assets/photos/))
- Gino portrait: `gv-coach-gino-portrait.webp` (exists, live).
- New hero: AI-generated → `gv-home-hero-v2.webp` (to create).
- GV monogram white: recolor of `logo/gvbasketball-logo.svg` → `gvbasketball-logo-white.svg` (to create).
- Wordmark white: recolor of `logo/gvbasketball-long.svg` → `gvbasketball-long-white.svg` (to create).

## Out of scope
- Booking/LatePoint, OTP auth, email, forms schema, header nav structure (except R8 label text).
