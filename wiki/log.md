# GV Basketball Wiki — Changelog

This is the chronological log of all tasks, updates, and releases completed on the GV Basketball website. It serves as the primary timeline of project modifications.

---

## [2026-06-27] task | Home Page Minimalist Revamp
- **Goal:** Redesign the Home page to be minimalist, following the style of `rootedheatstudio.rezerv.co` with light & airy design elements.
- **Changes:**
  - Full rewrite of `build/pages/home.html`.
  - Replaced split hero with editorial split (white, Bebas Neue headline, CTAs, right-side image).
  - Added "Mentored by NBA Skills Coaches" row with headshots of Phil Handy and Micah Lancaster.
  - Trimmed layout to a clean 5-section design (Hero, NBA Mentors, Programs, 6-Step System, Final CTA).
  - Deployed CSS modifiers for light heroes, fallback layouts, and mobile stacking rules.

## [2026-06-27] task | Full Frontend Revamp
- **Goal:** Clean up unicode symbols, add real brand SVG icons in the footer and contact pages, and upgrade form accessibility.
- **Changes:**
  - Swapped unicode icons (✆/✉/f) for SVG elements (Instagram, Facebook, WhatsApp).
  - Added standard `:focus-visible` focus rings, tap-targets ($\ge 48$px), and mobile input scaling to `gv-brand.css`.
  - Built custom icons lookup spreadsheet at `build/assets/icons.html`.

## [2026-06-27] task | New AI Image Library Deployed
- **Goal:** Replace legacy demo images with 16 WebP assets generated via OpenAI's image model (obscured faces, dark/moody navy-orange aesthetic).
- **Changes:**
  - Ran batch cwebp compression on 16 files, reducing size to ≈790 KB total.
  - Uploaded via `wp media import --user=1` (IDs 3023–3038).
  - Mapped specific WebPs to slots on Home, About, Programs, ADS, Success, FAQ, and Contact pages.

## [2026-06-27] task | Minimalist Refinement Pass
- **Goal:** Align with brand mood board, remove all traces of Facebook and WhatsApp CTA routes in favor of Instagram direct message links (`https://ig.me/m/gvbasketballl`).
- **Changes:**
  - Nav bar trimmed to: Home, About, Programs, FAQ, Contact, Member Login.
  - Set full-bleed width `.ast-container{max-width:100%}` to ensure edge-to-edge colors.
  - Removed footer container gaps (`gap: 0px`).

## [2026-06-29] task | Member Signup with Verified Email (Passwordless OTP)
- **Goal:** Enable LatePoint native customer authentication using passwordless email OTP on `/booking/` (Post ID: `2983`).
- **Changes:**
  - Ran `enable-member-auth.php` script to write database config options.
  - Set `notifications_email_processor=wp_mail` so LatePoint OTP emails route through WordPress and FluentSMTP.
  - Deployed `gv-otp-email.php` must-use plugin to intercept plain-text emails and send a branded HTML layout instead.

## [2026-06-29] task | PHP Currency, Card-text Fix, Client Handover Report
- **Goal:** Reconfigure LatePoint currency to Philippine Peso, fix unreadable text on cards inside navy blocks, and prepare handover report.
- **Changes:**
  - Set LatePoint currency to PHP (`₱` symbol).
  - Swapped Whatsapp contact in steps footer to Instagram link, removing support phone number.
  - Forced charcoal text overrides on `.gv-step`, `.gv-card`, and `.gv-program` blocks to fix legibility.
  - Created `docs/CLIENT-REPORT.html` capability walkthrough report.

## [2026-06-29] task | "Request Training" Form Replaces LatePoint Public Booking
- **Goal:** Disable public date-picker booking on LatePoint. Replace with modal request form on `/book-a-consultation/` to capture leads first.
- **Changes:**
  - Deployed `gv-request-form.php` must-use plugin to render structured form fields.
  - Configured Cloudflare Turnstile verification using account sitekey/secret.
  - Auto-configured admin emails (`gvbasketballcoaching@gmail.com`) and styled client auto-replies.

## [2026-06-29] task | Inbound Email & Displayed Address Relocation
- **Goal:** Set `gvbasketballcoaching@gmail.com` as the primary address for all inbound form notifications.
- **Changes:**
  - Updated constants inside `gv-request-form.php`, footer, contact scripts, and LatePoint configs.
  - FluentSMTP sender left as `info@gvbasketball.com`.

## [2026-06-29] task | Cloudflare Zone Optimized for WordPress
- **Goal:** Apply TLS policies and caching constraints to `gvbasketball.com` zone in Cloudflare.
- **Changes:**
  - Configured edge redirects for HTTPS, set TLS minimum to 1.2, set SSL mode to Strict.
  - Keep Rocket Loader OFF to avoid breaking OTP/Turnstile validation scripts.

## [2026-07-09] task | Client Revisions (Venues & Premium Elevation)
- **Goal:** Implement first batch of feedback from Coach Gino regarding schedules, pricing, and premium visuals.
- **Changes:**
  - Updated schedules to three actual venues: Dasma Makati (Mon/Wed/Thu), Urdaneta Village (Fri/Sun), Corinthian Gardens (Sun).
  - Set Small Group cap to 6 athletes, Elite Performance to include aqua training.
  - Updated CTA blocks to a gold gradient styling (`#C9A24B` gold, `#021F51` navy) with trust badges row.
  - Added the 5-step booking flow diagram on `/book-a-consultation/`.

## [2026-07-09] task | Client Revisions Polish
- **Goal:** Refine mentor grid layout, update SVG assets, and perform site-wide copy sweep.
- **Changes:**
  - Added Coach Gino as Founder & Head Coach in the mentor grid, side-by-side with NBA coaches.
  - Watermark swapped from text "GV" to white SVG monogram logo.
  - Fixed gold hover buttons to prevent text turning orange.
  - Replaced text "GVBASKETBALL" in footer with the white long logo SVG.
  - Moved locations grid from Contact to Home page.
  - Site-wide copy sweep: replaced all occurrences of "Request Training" with "Book a Consultation".

## [2026-07-09] task | Home Hero Background Video
- **Goal:** Add loopable background training video to home hero.
- **Changes:**
  - Encoded `gvbasketball.MOV` to H.264 MP4 (900×1600, 2.0 MB) and generated poster image.
  - Set desktop hero layout to copy on left and video in a portrait frame on right; mobile layout uses full-bleed video cover.

## [2026-07-09] task | Header: Replace Member Login with Instagram Link
- **Goal:** Swap Member Login link in header menu for Instagram brand icon and link.
- **Changes:**
  - Updated `build/templates/header.html` and menu scripts.
  - Added pink hover styles to brand icon.

## [2026-07-09] task | Branded Photo Filters Application
- **Goal:** Implement site-wide filters to normalize visual contrast across all images.
- **Changes:**
  - Applied "Warm Hardwood" filter (`sepia(8%)` contrast/brightness shifts) on action photos.
  - Applied "Luminosity Blend" on hero background images.

## [2026-07-09] task | Post-revision Fixes: Hero re-shoot, color, Gino crop, footer nit
- **Goal:** Address layout issues with hero filters and photo positions.
- **Changes:**
  - Removed "Luminosity Blend" from interior page heroes.
  - Set Coach Gino's portrait crop to `object-position: center top`.
  - Synced white/navy brand logos in footer markup.

## [2026-07-09] task | Final Revisions: Remove all hero images, fix footer logo carve, keep Gino crop
- **Goal:** Clean up hero overlays and resolve logo styling issue in footer.
- **Changes:**
  - Completely removed background images from all page heroes, defaulting to solid deep navy backgrounds.
  - Overwrote footer SVG logo with corrected paths (`gvbasketball-wordmark-footer.svg`).

## [2026-07-09] task | Site-wide Content Photo Normalization
- **Goal:** Revert CSS filters (sepia) and process images directly with ImageMagick tool to achieve a clean cool-neutral grade.
- **Changes:**
  - Created `normalize-photo.sh` CLI tool.
  - Processed and normalized all uploaded images in place.

## [2026-07-09] task | Deeper Photo Audit: Training Programs + Real Session Crops
- **Goal:** Align programs layout with real-session photography.
- **Changes:**
  - Swapped tall phone photos on programs page for landscape crops of actual sessions.

## [2026-07-09] task | Gallery Real Photos and Menu Navigation Revamp
- **Goal:** Revamp Gallery page with real photos and integrate the page into menus.
- **Changes:**
  - Populated `/gallery/` with 3x3 grid of 9 real action photos.
  - Updated header/footer templates to include Gallery link.

## [2026-07-09] task | Consultation Modal Fix + Location/Day Selection
- **Goal:** Add dropdown venues and dynamic day checklist to popup modal on programs page.
- **Changes:**
  - Added conditional day script. Selecting location shows only valid training days.
  - Made preferred time notes optional.

## [2026-07-09] task | Legacy Consultation Links Now Open the Modal
- **Goal:** Route `/book-a-consultation/` target links directly into the modal popup.
- **Changes:**
  - Redirect target uses `?gv_open_modal=1` parameters.
  - Modal script checks parameters and fires modal on load.

## [2026-07-09] task | Footer Newsletter Band Hidden Site-wide
- **Goal:** Remove newsletter signup block from footer template.
- **Changes:**
  - Updated templates and deploy scripts; preserved underlying WPForms definitions.

## [2026-07-09] task | Training Programs Images Regenerated from Real Gallery Sources
- **Goal:** Refresh the three training programs images using real session sources and neutral lighting.
- **Changes:**
  - Overwrote `gv-private.webp`, `gv-group.webp`, and `gv-elite.webp` at 1536×1024.
  - Updated `PROMPTS.md` to reflect neutral lighting direction.

## [2026-07-10] task | Reconcile FAQ Ages, Program Model & Schedule
- **Goal:** Reconcile FAQ values with the rest of the site (ages 3 to professional, Private/Small Group/Elite programs, new venues, and cap sizes).
- **Changes:**
  - Updated `build/pages/faq.html` HTML accordions.
  - Updated client-facing schedule logs.
  - Deployed to production: `gv_set_page_html(2988, faq.html)` via SSH/WP-CLI (backed up prior `_elementor_data` first), then `wp elementor flush-css` + `wp litespeed-purge all`. Verified stale strings gone and corrected facts render live at `/faq/`.

## [2026-07-10] task | Create LLM Wiki and Update AGENTS.md
- **Goal:** Deprecate `PROJECT_LOG.md` and `PROGRESS_LOG.md` and set up the structured `wiki/` directory. Update `AGENTS.md` to act as schema.
- **Changes:**
  - Deployed `wiki/` containing `index.md`, `log.md`, `access-and-hosting.md`, `architecture.md`, `design-system.md`, `pages.md`, `booking-latepoint.md`, `forms-and-emails.md`, `deployment-workflows.md`, and `client-status.md`.
  - Modified `AGENTS.md` to point to the wiki, define schemas, and list recommended cleaner directory layouts.
  - Deleted root-level `PROJECT_LOG.md` and `PROGRESS_LOG.md` files.
