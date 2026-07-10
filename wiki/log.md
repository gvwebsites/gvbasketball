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

## [2026-07-10] task | Member Login — Activate consultation portal (view-only)
- **Goal:** Deliver the deferred "member login" as a view-only consultation portal, re-surface the login entry, and give the coach a one-click calendar action — without the paid LatePoint reschedule feature.
- **Discovery (Stage 0, verified on live server):** LatePoint 5.6.6 **free only**. Login/OTP/portal already live at `/booking/`; **viewing works**, but **customer reschedule is hard-gated behind a paid add-on** (`latepoint_is_feature_reschedule_available` never enabled) and bookings are order-linked. Client chose: view-only now + coach creates bookings at confirmation.
- **Changes:**
  - `build/templates/header.html`: added `.gv-nav__account` Member Login icon (→ `/booking/`) before the Instagram link. Deployed to GV Header theme part (3002).
  - `build/mu-plugins/gv-assets/gv-brand.css`: `.gv-nav__account` styling (mirrors `.gv-nav__instagram`).
  - `build/scripts/build-functional.php`: portal page **2983** lead copy → view-only ("view your consultation schedule… Need to change a day? Just message us"). Re-ran; 2982/2983/2989 rebuilt (2982 redirects, 2989 unchanged).
  - `build/mu-plugins/gv-request-form.php`: new `gv_rf_next_weekday_date()` (pinned to **Asia/Manila** — host WP is on UTC) + `gv_rf_gcal_url()`; admin/coach email now includes an **"Add to Google Calendar"** button (all-day event on soonest preferred weekday, client prefilled as guest via `add=`, full details + venue location).
  - `build/mu-plugins/tests/test-gv-request-form.php`: +16 assertions (weekday derivation, gcal URL). 35/35 pass; PHP lint clean.
  - Wiki synced: `booking-latepoint.md` (view-only + reschedule-gated + coach flow), `pages.md`, `design-system.md`, `client-status.md`, `forms-and-emails.md`.
  - Deploy: per-target backups to `~/backups/member-portal-2026-07-10-0514/`; scp templates/css/mu-plugin; `gv_set_theme_part_blocks` + `wp eval-file build-functional.php`; `wp elementor flush-css && wp litespeed-purge all`. Verified live: nav icon → `/booking/`, portal copy updated, redirect intact, contact unregressed, `gv_rf_gcal_url()` returns correct Manila-dated URL server-side.
- **Not code:** Coach adds each confirmed consultation in the LatePoint admin under the client's email; the self-registered member (same email) then sees it.

## [2026-07-10] task | Restore hero background images site-wide
- **Goal:** Bring back the photographic hero backgrounds removed on 2026-07-09 (commit `6d641e9`), which the July client report showed flattened to solid navy.
- **Changes:**
  - `build/mu-plugins/gv-assets/gv-brand.css`: re-added `.gv-hero__bg` (opacity .38, grayscale/luminosity blend) and `.gv-hero__overlay` (navy→orange gradient) rules, plus `.gv-hero--home` overrides (opacity .62, normal blend, even navy overlay). **Image layers only** — kept the later-tightened `.gv-hero__inner` padding (`88px 0 72px`) and `.gv-hero--home` `min-height:64vh` untouched (did NOT `git revert`, which would have clobbered those).
  - Added `.gv-hero__bg` + `.gv-hero__overlay` divs to all 12 heroes: 8 marketing pages (`build/pages/*.html`) and 4 utility/form heredocs (`build-functional.php`: Book 2982, Portal 2983, Contact 2989; `build-extras.php`: Waiver 3009).
  - **Client photo preference (mid-task):** swapped home/about/programs heroes from the `-real` people photos to **generic no-people b-roll** already in the media library — home → `gv-net.webp`, about → `gv-about-hero.webp`, programs → `gv-programs-hero.webp`. No image regeneration needed. `gv-sneaker.webp` rejected (shows a player's legs).
  - Wiki synced: `design-system.md` (new §2A Hero Background Treatment + per-page image map), `client-status.md` (item 11), `log.md`.
- **Deploy:** Golden Workflow — scp `gv-brand.css`; `gv_set_page_html(2887,…)` for home + `wp eval-file apply-pages.php` for the 7 interior pages; `wp eval-file build-functional.php` + `build-extras.php`; `wp elementor flush-css && wp litespeed-purge all`.
- **Gotcha caught:** `build-extras.php` rebuilds the GV Footer (2991) from `~/footer.html`; first run omitted that upload, blanking `_elementor_data`. Re-ran with `build/templates/footer.html` uploaded → footer `_elementor_data` back to 3906 bytes, renders live. Remember to scp `footer.html` alongside `build-extras.php`.
- **Verified live:** all 9 image URLs return 200; `gv-hero__bg` markup + correct image present on all 12 pages; `.gv-hero__bg{` rule live in served CSS; footer intact. (`/book-a-consultation/` 301-redirects to `/training-programs/` — pre-existing, ignored per client.)

## [2026-07-10] fix | Restore real program-card photos on /training-programs/ (deploy regression)
- **Goal:** Undo an unintended side-effect of the hero restore: deploying the full `training-programs.html` via `apply-pages.php` (`gv_set_page_html`, whole-page replace) reverted the three program detail-section photos from real → AI.
- **Root cause (source drift):** commit `2c0fb74` had committed AI images (`gv-private/group/elite.webp`) into the build file, but that change was never deployed — **live kept the real photos** (`gv-private-1on1`, `gv-youth-group`, `gv-elite-competitive`, 2026/07). Confirmed against the July client-report screenshot. My whole-page deploy finally pushed the stale build content over the live reals.
- **Fix:** restored the three `<img>` in `build/pages/training-programs.html` to the real photos + original alt text (from the `2c0fb74` `-` side); redeployed page 2981 individually; flushed caches. Verified live: 3 real photos present, 3 AI gone, hero unchanged (`gv-programs-hero.webp`). Build file now matches live and is correct.
- **Audit:** other 7 deployed pages checked — only their hero `__bg` divs changed; no content-image drift (git shows `2c0fb74` was the sole real→AI page edit; home/about/gallery reals preserved and match screenshots).
- **Lesson:** `apply-pages.php` / `gv_set_page_html` replace the ENTIRE page. Before deploying a whole page for a small edit, confirm the local build file matches live (esp. content photos), or edit the live `_elementor_data` surgically. Source-of-truth drift between `build/pages/` and live is a live risk.

## [2026-07-10] task | Swap program-card photos to AI-derived, brand-mark-free versions
- **Goal:** On /training-programs/, replace the three program detail-section photos with AI-generated versions derived from the real shots (cleaner backgrounds, no apparel brand marks), per client preference.
- **Source:** local AI generations in `output/imagegen/training-programs-20260709/` — used `gv-private-clean-retouched.png` (NIKE logo retouched out; the non-retouched `gv-private-clean.png` still showed it), `gv-group-clean.png`, `gv-elite-clean.png`. These were **not** previously uploaded to the site (verified: live real-filename webps were byte-identical to the local real copies; media library had only the real + generic-AI images).
- **Changes:**
  - Normalized the 3 PNGs → WebP via `build/scripts/normalize-photo.sh` (cool-neutral premium, q82) → `build/assets/photos/gv-{private-1on1,youth-group,elite-competitive}-ai.webp` (158–245 KB).
  - Uploaded to `wp-content/uploads/2026/07/` under **new `-ai` filenames** (scp direct; not registered as WP attachments, so no thumbnail sizes — fine for the hand-built `<img src>`). New names chosen deliberately so the **Gallery**, which reuses the real `gv-private-1on1/youth-group/elite-competitive.webp`, is left untouched.
  - `build/pages/training-programs.html`: pointed the 3 program-card `<img>` at the `-ai` URLs (alt text unchanged — same composition). Redeployed page 2981; flushed caches.
  - Wiki: `design-system.md` §2A programs row annotated; this log entry.
- **Verified live:** 3 `-ai` images present on /training-programs/ (real refs gone), hero unchanged, Gallery still on the real files.

## [2026-07-10] task | Branded emails: new crest logo + Google Calendar line-break fix
- **Goal:** Swap the retired `GV_Logo_Main.png` for the new GV crest in both branded emails, fix the "Add to Google Calendar" description running together, and add the member-login/branded-email section to the July client report.
- **Changes:**
  - Rasterized `logo/gvbasketball-logo.svg` → `gv-logo-crest.png` (298×320, transparent), uploaded to `wp-content/uploads/2026/07/` (attachment 3096). Email clients don't render SVG, so a PNG is required.
  - `gv-otp-email.php` + `gv-request-form.php` (`gv_rf_email_shell`): logo URL → the crest; removed the now-redundant "GV BASKETBALL" text line under the logo (the crest already reads the name); img sized to 80×86.
  - `gv-request-form.php`: `$gcal_details` now joins with `<br>` instead of `\n` — Google Calendar renders the description as HTML, so raw newlines collapsed. Deployed both mu-plugins; re-sent a real test email (verified by client).
  - `docs/CLIENT-REPORT-JULY.html`: item #6 Member Login → **Done** (was Evolved) with view-only portal copy; new Part-2 section "05 — Member Login & Branded Emails" featuring `docs/screenshots/member-email.png` (Chrome-headless render of the real email); refreshed the "Tested & Live" summary.
  - Wiki: `forms-and-emails.md` (gcal `<br>` + crest logo), `booking-latepoint.md` (OTP email crest logo).

## [2026-07-10] task | Design GV Members Portal and Consultation History
- **Goal:** Specify a full `/members/` experience with open email-OTP signup, a minimal consultation request form, member request history, and read-only confirmed LatePoint sessions.
- **Changes:**
  - Added `docs/superpowers/specs/2026-07-10-gv-members-portal-design.md` after repository, live-page, SSH, database, and LatePoint source investigation.
  - Chose a hybrid architecture: GV-owned request persistence and timeline UI with LatePoint-owned customer sessions and confirmed bookings.
  - Defined the Request Timeline visual direction, two-state Submitted/Confirmed workflow, email-based coach confirmation, URL migration, cache exclusions, security requirements, and acceptance tests.
  - Reduced anonymous request capture to parent/email, player/age, program, location, days, and one optional note; signed-in requests hide verified account identity.

## [2026-07-10] task | Add missing screenshots to July Client Report
- **Goal:** In Part 1 (items 1-8) of `CLIENT-REPORT-JULY.html`, capture live screenshots for items that were missing visual assets and refine them per user instructions.
- **Changes:**
  - Used Playwright in the `tmp/` environment to capture screenshots from the live `gvbasketball.com` site.
  - Captured `faq.png` (live FAQ page layout), `pricing.png` (expanded pricing accordion on FAQ page), and `footer.png` (targeting the `.gv-footer` class selector to capture the actual navy blue footer).
  - Modified `docs/CLIENT-REPORT-JULY.html` to inject `<figure>` markup with captions and file paths for items 2 (FAQ), 5 (Pricing), and 7 (Footer).
  - Removed/excluded figure elements and screenshots for Testimonials (item 4), Member Login (item 6), and Flow of Purchase (item 8) as requested.

## [2026-07-10] task | Merge Members Portal and Consultation Booking Designs
- **Goal:** Reconcile the approved member-portal and LatePoint self-service consultation directions into one implementation-ready product design.
- **Changes:**
  - Added `docs/superpowers/specs/2026-07-10-members-self-service-consultation-merged-design.md` as the single design source of truth.
  - Standardized on one pending-to-approved LatePoint booking record, a themed day-request wizard, optional member promotion, any-email OTP access, and a read-only member request timeline.
  - Defined Coach Gino's secure exact-time finalization flow and six-step email instructions, with personal parent confirmation instead of an automated final email.
  - Marked both earlier design specs and their implementation plan as superseded.

## [2026-07-10] task | Plan Merged Members and Consultation Implementation
- **Goal:** Translate the approved merged design into a test-driven, deployment-safe implementation plan.
- **Changes:**
  - Added `docs/superpowers/plans/2026-07-10-members-self-service-consultation-merged.md` with focused modules, exact LatePoint hooks, security boundaries, phased deployment, rollback, and production acceptance.
  - Explicitly prohibited the destructive fresh-install LatePoint setup script and preserved the 45-minute duration with a 180-minute public request interval.
  - Folded live portal/wizard/finalizer captures plus OTP, parent-receipt, and Coach Gino email screenshots into the July client-report task.

## [2026-07-10] task | Pure domain helpers with TDD (Task 1)
- **Goal:** Implement pure domain helpers and validations for the GV Members system using TDD.
- **Changes:**
  - Created `build/mu-plugins/gv-members/core.php` containing payload validation, interest option labels, status labels, start time range helper, secure token hash comparison, and change mailto builders.
  - Created `build/mu-plugins/tests/test-gv-members-core.php` to perform red-green TDD testing of all validation boundaries and domain helpers.

## [2026-07-10] task | Non-destructive LatePoint and page configuration (Task 2)
- **Goal:** Add idempotent WordPress configuration scripts and contract tests to align LatePoint, Members page (2983), and Consultation page (2982).
- **Changes:**
  - Created `build/scripts/configure-members-consultation.php` to set up Player Consultation service duration (45 mins), interval (180 mins), default status (pending), customer fields, and hide paid services.
  - Created `build/scripts/configure-members-page.php` to configure the members page (2983) with Elementor blocks and `[gv_members_portal]` shortcode.
  - Created `build/scripts/configure-consultation-page.php` to configure the consultation landing fallback page (2982) with the native wizard shortcode.
  - Modified `build/scripts/enable-member-auth.php` and `build/scripts/build-functional.php` to point LatePoint options to `/members/` and match the new portal shortcode conventions.
  - Created `build/mu-plugins/tests/test-gv-members-contracts.php` static contract test to assert configuration scripts are non-destructive and contain necessary configuration directives.

## [2026-07-10] task | Bootstrap, cache protection, redirects, and CTA bridge (Task 3)
- **Goal:** Bootstrap the GV Members integration and implement cache-protection headers, old dashboard redirects, assets versioning, and the consultation wizard CTA bridge.
- **Changes:**
  - Updated `build/mu-plugins/gv-members.php` to load core module immediately and load LatePoint-dependent modules on `plugins_loaded` priority 20.
  - Implemented template cache protection `gv_members_private_response` (nocache_headers, DONOTCACHEPAGE, litespeed_control_set_nocache, private no-store) for the members page, AJAX actions, and finalization screens.
  - Configured 301 legacy redirects for `/booking/` and `/customer-cabinet/` front-end GET requests while bypassing AJAX, REST, admin, and WP-CLI.
  - Enqueued custom scoped portal/wizard scripts and stylesheets using `filemtime` dynamic versioning.
  - Added hidden `[latepoint_book_button]` markup resolving Player Consultation dynamically by name and enqueued Javascript to intercept bookings CTAs.
  - Extended contract test harness in `build/mu-plugins/tests/test-gv-members-contracts.php` to assert correct loading, redirects, headers, and asset versions.

## [2026-07-10] task | Native wizard fields, Turnstile, OTP, and cart payload (Task 4)
- **Goal:** Integrate custom player, training interest, honeypot, and Turnstile fields into the native LatePoint booking wizard and persist validated payloads to cart metadata.
- **Changes:**
  - Implemented custom wizard fields rendering inside the contact step (`latepoint_booking_steps_contact_after`) with fields: player name, age (3-99), training interest, optional contact/note details, and members promotion checkbox.
  - Added priority 1 validation handler `gv_members_process_step_validation` rejecting invalid payloads, honeypot (`gv_website`) submissions, or missing/failed Cloudflare Turnstile verification.
  - Added priority 20 persistence handler `gv_members_process_step_persistence` encoding validated fields as JSON and saving to `gv_consult_payload` cart metadata.
  - Formatted responsive input fields and coordination notes explaining the day-only selection process in `assets/gv-members.css` and `assets/gv-members.js`.
  - Added client-side double-submission prevention by disabling the forward/verify controls during active AJAX requests.
  - Updated contract tests in `test-gv-members-contracts.php` to assert field rendering, validation error shapes, Turnstile verification, and cart payload persistence.

## [2026-07-10] task | Branded emails and receipts (Task 5)
- **Goal:** Design and send branded HTML receipt and coach workflow emails when a consultation booking is created.
- **Changes:**
  - Created `build/mu-plugins/gv-members/emails.php` containing HTML layout shells with the new GV crest logo, timing copy with timezone conversions, and clear parent/coach calls to action.
  - Set up `latepoint_booking_created` action handler to trigger sending the emails only if a validated wizard payload exists, protecting against double-fires.
  - Added email unit tests to assert correct recipient addresses, booking code references, and timing copy formatting.

## [2026-07-10] task | Secure timing finalization (Task 6)
- **Goal:** Allow Coach Gino to securely select and finalize exact 45-minute slots via signed links, updating LatePoint bookings to approved.
- **Changes:**
  - Created `build/mu-plugins/gv-members/finalize.php` implementing secure hash-nonced GET/POST endpoints at `/members/?gv_finalize_consultation=token`.
  - Added token validation, expiry (30 days), slot availability validation, and atomic approval updating the booking status and start/end UTC times.
  - Implemented read-only status and feedback screens for confirmed links.
  - Added contract tests checking token validation, slot availability gates, and status update logic.

## [2026-07-10] task | Any-email member OTP signup and login (Task 7)
- **Goal:** Implement secure OTP sign-up/login for any email address without creating standard WordPress user accounts, protecting routes and setting session states.
- **Changes:**
  - Created `build/mu-plugins/gv-members/auth.php` exposing `gv_otp_request` and `gv_otp_verify` AJAX controllers and a POST-only logout handler.
  - Implemented email/IP transient-based rate-limits (5 sends/email/hour, 10 sends/IP/hour) using truncated HMAC hashes.
  - Integrated `OsOTPHelper` to send and verify codes, creating active customer records on demand and guarding against concurrent creation race conditions.
  - Added interactive numeric inputs in `assets/gv-members.js` with focus navigation, clipboard pasting, and auto-submit.
  - Updated contracts test suite to verify AJAX endpoints, nonce protection, rate limiting, and customer creation logic.

## [2026-07-10] task | Requests, sessions, players, and profile (Task 8)
- **Goal:** Render the `[gv_members_portal]` training journal dashboard for signed-in members, showing their timeline, confirmed sessions, unique players, and profile edits.
- **Changes:**
  - Created `build/mu-plugins/gv-members/portal.php` rendering the tabbed dashboard using `OsAuthHelper::get_logged_in_customer()`.
  - Displayed requests timeline sorted newest-first, suppressing nominal times for pending requests while showing exact times for approved ones.
  - Rendered upcoming/past confirmed sessions in Asia/Manila local time and enqueued template-redirect ICS calendar files.
  - Implemented athlete select reuse dropdown in the wizard, and locked native wizard input fields.
  - Rendered editable profile forms with nonced POST updates and a secure POST logout button.
  - Styled the training journal UI canvas with navy frames, orange timeline nodes/states, and responsive CSS in `assets/gv-members.css`.
  - Added comprehensive contract tests verifying customer fixtures, timeline time-suppression, newest-first request sorting, player extraction, and change mailto links.


