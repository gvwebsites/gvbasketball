# GV Basketball — Website Build Log & Runbook

Live site: **https://gvbasketball.com** — a basketball skills-training business in Metro Manila
(Makati & Ortigas), run by Coach Gino Victorino. Tagline: *"Build Better Players. Build Better
People."*

This document is the source of truth for how the site is built and how to change it. The actual
custom source lives in [`build/`](build/) (also deployed to the live server's `wp-content/`).

---

## 1. Hosting & access

| Thing | Value |
|---|---|
| Platform | Hostinger Premium (shared) |
| WordPress | 6.8.5 · PHP 8.2 · WP-CLI 2.12 |
| Theme | Astra 4.13 (active) |
| Page builder | **Elementor Pro 3.30** (Theme Builder used for header/footer) |
| SSH alias | `gvweb` (user `u907133977`) |
| WP root | `/home/u907133977/domains/gvbasketball.com/public_html` |
| Origin IP | `37.44.245.74` |
| DNS | **Cloudflare** (`norman/carol.ns.cloudflare.com`); A record → `37.44.245.74` (proxied) |
| Email routing | Cloudflare Email Routing — `info@gvbasketball.com` is active |
| Full backup | `~/backups/gvbasketball-20260627-015018/` (`db.sql` + `wp-content.tar.gz`) taken before any change |

All work was done over SSH + WP-CLI. Secrets (Google OAuth client id/secret) live in the local
`.env` (gitignored) and in `wp-config.php` as constants.

---

## 2. Architecture of the custom build

The front end is delivered as **hand-crafted HTML + a shared CSS design system**, mounted inside
Elementor so pages stay Elementor-native (editable, with Theme Builder header/footer). Two
must-use plugins power it:

- **`wp-content/mu-plugins/gv-brand.php`** → enqueues the design system CSS site-wide
  (`mu-plugins/gv-assets/gv-brand.css`). All classes are namespaced `gv-`.
- **`wp-content/mu-plugins/gv-build.php`** → build helpers used by the deploy scripts:
  - `gv_set_page_html($page_id, $html)` — set a page to one full-width HTML widget.
  - `gv_set_page_blocks($page_id, $blocks)` — page from ordered html/shortcode widgets.
  - `gv_set_theme_part($title, $type, $html)` / `gv_set_theme_part_blocks(...)` — Elementor
    Theme Builder header/footer from HTML/blocks, applied site-wide.
  - `gv_ensure_page($slug, $title)` — idempotent page creator.
- **`wp-content/mu-plugins/gv-otp-email.php`** → runtime hook (not build-time): brands the member
  login OTP email by intercepting `wp_mail` (see §6 "Member login & signup").

> Theme Builder note: Elementor Pro caches header/footer display conditions in the option
> `elementor_pro_theme_builder_conditions` (`['header'=>[id=>['include/general']], 'footer'=>...]`).
> Setting the post meta alone is **not** enough — the option must be updated too (the deploy
> scripts do this).

### Global background fix
The Astra "photography" starter shipped a **dark/black body**. `gv-brand.css` forces
`html body{background:#fff}` and white default sections so text is readable everywhere.

---

## 3. Design system (`build/mu-plugins/gv-assets/gv-brand.css`)

- **Colors:** Navy `#123B78`, Deep Navy `#021F51`, Basketball Orange `#F47B20`, Charcoal `#1C1C1E`,
  Steel `#6B6F76`, Light `#E6E7E9`, Silver `#A7A9AC`, White. (Also set in Elementor Global Kit, post id 5.)
- **Fonts:** Bebas Neue (display/headlines), Montserrat (sub-heads/UI), Inter (body).
- **Components:** `gv-hero`, `gv-section[--light|--navy|--deep|--charcoal|--tight]`, `gv-wrap`,
  `gv-section-title`, `gv-eyebrow`, `gv-lead`, `gv-btn[--primary|--navy|--ghost|--outline]`,
  `gv-grid--2/3/4`, `gv-card`, `gv-program`, `gv-steps`/`gv-step`, `gv-person`, `gv-quote`,
  `gv-acc` (FAQ accordion via `<details>`), `gv-stats`, `gv-gallery`, `gv-nav` (header),
  `gv-footer`, `gv-newsletter-band`, booking/form wrappers.

---

## 4. Pages (all live unless noted)

| Page | Slug | Post ID | Notes |
|---|---|---|---|
| Home | `/` | 2887 | front page · **minimalist revamp** (2026-06-27): lean 5-section, light/airy, editorial hero |
| About Coach Gino | `/about/` | 26 | |
| Training Programs | `/training-programs/` | 2981 | |
| Athlete Development System | `/athlete-development/` | 2984 | |
| Success Stories | `/success-stories/` | 2985 | testimonial section removed |
| Testimonials | `/testimonials/` | 2986 | **draft (hidden)** — placeholder until real reviews |
| Gallery | `/gallery/` | 2987 | |
| FAQ | `/faq/` | 2988 | |
| Book a Consultation | `/book-a-consultation/` | 2982 | LatePoint `[latepoint_book_form]` |
| Member Booking (portal) | `/booking/` | 2983 | LatePoint `[latepoint_customer_dashboard]` |
| Contact | `/contact/` | 2989 | WPForms contact + location cards |
| Player Waiver & Consent | `/waiver/` | 3009 | WPForms waiver |

Page HTML source: [`build/pages/`](build/pages/) (marketing pages) and generated inline in
[`build/scripts/build-functional.php`](build/scripts/) + `build-extras.php` (booking/portal/contact/waiver).

### Header / footer (Elementor Theme Builder)
- **GV Header** (post 3002): custom `gv-nav` — horizontal logo, nav, orange "Book a Consultation",
  sticky, CSS-only mobile hamburger. Replaces Astra's header. Source: `build/templates/header.html`.
- **GV Footer** (post 2991): newsletter band (top) + 4-column footer. Source: `build/templates/footer.html`.
- Primary nav menu: WP menu "Primary Menu" assigned to Astra `primary` location (rebuilt via
  `build/scripts/build-menu.php`; its term ID changes on each rebuild).

---

## 5. Brand assets (media library)

| Asset | Attachment ID |
|---|---|
| Horizontal logo (header / `custom_logo`) | 2977 (`/uploads/2026/06/gvbasketball-long.svg`) |
| Primary vertical logo | 2978 |
| Circle icon | 2979 |
| Favicon PNG (site icon) | 2976 |
| Hero (moody dribble) | 2917 |
| Coach Gino clinic (landscape) | 2937 / 2938 (`GV2`) |
| Coach Gino clinic (square) | 2935 |
| Team photo | 2936 (`GV`) |
| Phil Handy | 2929 · Micah Lancaster | 2930 |

Source SVGs: [`logo/`](logo/). SVG uploads enabled via **Safe SVG** plugin (uploads must run as an
admin user: `wp media import ... --user=1`). All ~80 demo images + 10 demo posts from the starter
were deleted.

---

## 6. Booking — LatePoint (free), payments OFF

Config created by [`build/scripts/setup-latepoint.php`](build/scripts/setup-latepoint.php):

- **Agent:** Coach Gino (id 1). **Locations:** Makati (1), Ortigas (2).
- **Services:** Player Consultation (45m), Private Training (60m), Small Group (90m, cap 5),
  Elite Performance (90m, cap 5) — all `charge_amount=0`.
- **Work periods:** Mon/Tue/Fri/Sun, 15:00–18:00 (minutes 900–1080), both locations, all services.
  (LatePoint weekday: 1=Mon … 7=Sun; times = minutes from midnight.)
- **Settings:** `enable_payments_local=off`, service/location category steps off, timezone selector
  off, `accent_color=#F47B20`, **currency = Philippine Peso** (`currency_iso_code=PHP`,
  `currency_symbol_before=₱`), support text → Instagram (`steps_support_text`), `support_phone` cleared.
- **Shortcodes:** `[latepoint_book_form]`, `[latepoint_customer_dashboard]`, `[latepoint_customer_login]`.

### Member login & signup (passwordless email OTP)

Configured by [`build/scripts/enable-member-auth.php`](build/scripts/enable-member-auth.php).
The nav "Member Login" → `/booking/` (2983) renders `[latepoint_customer_dashboard]`, which shows
the login/signup form when logged out. Auth is **passwordless one-time code over email**, so every
signup has a verified email address. LatePoint settings set:

| Setting | Value | Why |
|---|---|---|
| `selected_customer_authentication_method` | `otp` | Code-only auth (no passwords) |
| `default_customer_authentication_method` | `otp` | OTP shown by default |
| `selected_customer_authentication_field_type` | `email` | Verify via email |
| `notifications_email_processor` | `wp_mail` | **Required** — without it LatePoint email notifications (incl. OTP) are disabled and OTP send fails silently |
| `page_url_customer_dashboard` / `page_url_customer_login` | `/booking/` | Post-login redirects land on the GV-styled page, not the bare `/customer-cabinet/` (2980) |

**Branded OTP email:** LatePoint sends a plain-text "Your OTP code is: …" with no pre-send content
filter, so mu-plugin [`build/mu-plugins/gv-otp-email.php`](build/mu-plugins/gv-otp-email.php) hooks
`wp_mail` (scoped to subjects containing "OTP"), extracts the code, and swaps in a branded HTML email
(GV logo, navy/orange, spaced code, 10-min expiry note) with `Content-Type: text/html`.

Verified end-to-end: OTP send to `test@favor.church` returned `status=success` (active row in
`wp_latepoint_customer_otp_codes`, code stored hashed); a `phpmailer_init` capture confirmed the wire
payload is the branded HTML (subject "Your GV Basketball login code", `ContentType: text/html`, logo +
code present); and `/booking/` renders `auth[via]=otp` with password fields hidden. Pre-change settings
snapshot: `backups/latepoint_settings-pre-member-auth-2026-06-29.tsv`. Design spec:
[`docs/superpowers/specs/2026-06-29-member-signup-verified-email-design.md`](docs/superpowers/specs/2026-06-29-member-signup-verified-email-design.md).

---

## 7. Transactional email — FluentSMTP + Gmail OAuth

- **Sender:** `info@gvbasketball.com` (Gmail "Send mail as" alias on `gvictorino.websites@gmail.com`).
- **Gmail API** enabled on GCP project `gvbasketball`. OAuth client id/secret stored in
  `wp-config.php` as `FLUENTMAIL_GMAIL_CLIENT_ID` / `FLUENTMAIL_GMAIL_CLIENT_SECRET`.
- FluentSMTP connection authenticated and **verified** (`wp_mail` test sent OK).
- ⚠️ **Security TODO:** the OAuth **client secret was echoed** during setup (WP-CLI `config set`
  prints values) — recommend resetting it in Cloud Console, updating `.env`, then re-setting the
  constant with `--quiet`.

## 8. Forms (WPForms Lite)

| Form | ID | Notifies |
|---|---|---|
| Contact GV Basketball | 3003 | info@gvbasketball.com |
| GV Newsletter | 3005 | info@ (footer band) |
| GV Player Waiver | 3007 | info@ |

Note: WPForms Lite has no **Phone** field (Pro only) — phone uses a text field. Newsletter currently
emails signups to info@; to auto-sync to an **Omnisend** list, connect the Omnisend account and use
its form/integration.

---

## 9. How to make common changes

All deploy scripts live in [`build/scripts/`](build/scripts/). General loop: edit the file in
`build/`, `scp` it to the server, run via `wp eval-file`, then flush caches
(`wp elementor flush-css && wp litespeed-purge all`). The CSS is cache-busted by file mtime.

- **Edit design system:** change `build/mu-plugins/gv-assets/gv-brand.css` →
  `scp` to `wp-content/mu-plugins/gv-assets/gv-brand.css` → purge LiteSpeed.
- **Edit a marketing page:** edit `build/pages/<slug>.html` → `scp` to server →
  `wp eval "gv_set_page_html(<ID>, file_get_contents('<path>'));"`.
- **Edit booking/contact/waiver pages or forms:** edit `build-functional.php` / `build-extras.php`
  → `wp eval-file`.
- **Re-show testimonials:** `wp post update 2986 --post_status=publish`, restore the testimonial
  sections in `build/pages/home.html` + `success-stories.html`, re-add the nav (`build-menu.php`)
  and footer (`footer.html`) links, redeploy.

---

## 10. Status

**Done & verified live:** brand system, logos/favicon, all 12 pages, header/footer, mobile menu,
LatePoint booking (renders + bookable), member portal/login, contact + waiver forms, newsletter
band, SMTP (Gmail), readable white design across desktop + mobile. Demo content purged.

**Open / optional (not blockers):**
1. Reset the leaked Google OAuth **client secret** (security).
2. **Omnisend** newsletter auto-sync (currently emails info@).
3. **Automated referral rewards** — needs a paid plugin + customer accounts.
4. Real **testimonials / photos / before-after videos** (placeholders currently hidden/used) and
   **exact venue addresses** for the location links.
5. **New home hero photo** — the editorial hero currently shows a branded navy fallback block. When
   a photo is supplied, upload to WP media and replace `.gv-hero__media-fallback` in
   `build/pages/home.html` with an `<img>`, then redeploy.

---

## 11. Changelog

### 2026-06-29 — PHP currency, card-text fix, client handover report
- LatePoint currency set to **Philippine Peso** (`currency_iso_code=PHP`, `currency_symbol_before=₱`);
  booking-form support text switched from WhatsApp to Instagram, `support_phone` cleared — completes
  the Instagram-only rollout. Applied via targeted settings update (not a full `setup-latepoint.php`
  re-run, to avoid recreating agents/services and breaking existing bookings).
- Fixed light-blue body text on white cards inside navy sections (booking "From Consultation to Court"
  steps): `.gv-step` / `.gv-card` / `.gv-program` now force charcoal text (same class of bug as the
  mentor cards earlier).
- Added **`docs/CLIENT-REPORT.html`** — a branded, screenshot-rich handover report for the client
  (capabilities tour, booking → info@ → personal inbox flow, payments-by-design, what needs his input).
  Source template: `docs/report.template.html`. Also published as a private Artifact link.

### 2026-06-29 — Member signup with verified email (passwordless OTP)

Enabled LatePoint native customer auth as **passwordless email OTP** on `/booking/`
([`build/scripts/enable-member-auth.php`](build/scripts/enable-member-auth.php)): members sign up /
log in by entering an email and a 6-digit code sent to it, so every account is email-verified. Also
set `notifications_email_processor=wp_mail` (LatePoint email notifications were off, which had been
silently blocking OTP send) and pointed `page_url_customer_dashboard`/`page_url_customer_login` at
`/booking/` so post-login lands on the branded page. Added mu-plugin `gv-otp-email.php` to replace
LatePoint's plain-text OTP email with a branded HTML version (logo, navy/orange, spaced code, expiry).
Verified by sending OTPs to `test@favor.church` (success; `phpmailer_init` capture confirmed branded
HTML on the wire). No front-end HTML/CSS/template changes. Note: `wp db export` fails on this host
(mysqldump unavailable) — backed up the settings table to `backups/` instead.

### 2026-06-27 — Minimalist refinement pass (mood-board alignment)
Client wanted a more minimalist, peg-aligned feel (Rooted Heat Studio reference) and Instagram-only contact.

- **Home hero** — replaced the light editorial split (image-in-card) with a full-bleed cinematic hero:
  `.gv-hero--home` (gv-home-hero.webp bg, even dark navy overlay, **centered** headline + CTAs,
  min-height 82vh). Now consistent with interior-page heroes + the dark minimalist peg. CSS in
  `gv-brand.css` (`.gv-hero--home`, `.gv-hero__inner--center`).
- **Nav trimmed** (`templates/header.html`) — removed Development, Success, Gallery (pages stay
  published, reachable via footer/links). Nav = Home · About · Programs · FAQ · Contact · **Member
  Login (person icon → /booking/)** · Book CTA. Login is icon-only on desktop, icon + label in the
  mobile dropdown (`.gv-nav__login`, `.gv-nav__login-label`).
- **Instagram only** — removed **all WhatsApp and Facebook** integrations. Every "WhatsApp Us" CTA →
  "Message on Instagram" (`https://ig.me/m/gvbasketballl`); footer socials = Instagram only; footer
  "WhatsApp" line → Instagram DM; contact page rebuilt to Instagram + Email (Lucide SVG icons,
  dropped the ✆/✉/◎/f Unicode glyphs). Touched all pages + `build-functional.php` + `build-extras.php`.
- **Full-bleed width** — `.ast-container{max-width:100%}` so all `.gv-section` backgrounds are true
  edge-to-edge (verified section width == viewport).
- **Newsletter gap** — the white strip under "Get Training Tips & Updates" was Elementor's default 20px
  flex `gap` on the footer container; zeroed with `.elementor-location-footer .e-con{gap:0}` (now 0px,
  band sits flush on the footer).
- **Deploy** — `build/scripts/deploy-refine.php` (header + 8 marketing pages, with backups) + re-ran
  idempotent `build-functional.php` (book/portal/contact) and `build-extras.php` (waiver + footer).
  Flushed Elementor CSS + LiteSpeed. QA'd live desktop + mobile (headless browser) and via curl.

### 2026-06-27 — New AI image library deployed (all old photos replaced)
Replaced every legacy photo across the site with a 16-image cinematic library (dark/moody, navy +
orange, faces obscured) matching the brand mood board. Kept only the Phil Handy + Micah Lancaster
headshots and the SVG logos. Images were generated externally, mapped to slots, optimized, and wired
in per section (not a blanket swap — each section gets a contextually correct image).

- **Pipeline**: 16 PNGs (`assets/`) → `cwebp -q 80` resize (heroes 1600w, content/gallery 1100w) →
  `build/assets/img/web/*.webp` (≈790 KB total, down from ~30 MB). Uploaded via `wp media import
  --user=1` to `wp-content/uploads/2026/06/gv-*.webp` (attachment IDs **3023–3038**).
- **Slot map** (source → slot): dribble→home-hero · court→about-hero · ball-and-cones→programs-hero ·
  shooting-form→development-hero · sweat-fingers→success-hero · tactics-whiteboard→faq-hero ·
  gym-bag→contact-hero · one-on-one→private · footwork-drill→group · conditioning→elite ·
  agility-ladder→footwork · two-ball→ballhandling · hand-whiteboard→film · through-net→gallery ·
  sneaker→gallery · empty-bleachers→court/gallery-hero.
- **Pages updated** (per-section imagery): `home` (hero `<img>` replaces the fallback panel), `about`,
  `training-programs`, `athlete-development` (6 step images), `success-stories`, `faq`, `gallery`
  (rebuilt as a 6-tile non-repeating grid), `testimonials` (hidden draft, content refreshed).
  Functional pages (`book` 2982, `booking` 2983, `contact` 2989, `waiver` 3009) hero backgrounds
  swapped in-place via recursive str_replace on `_elementor_data` (see `build/scripts/deploy-images.php`).
- **Old media deleted** (`wp post delete --force`): 2917 (GV-Basketball-Hero), 2935 (clinic jpg),
  2936 (GV.png), 2937 (GV2.png), 2938 (GV2.jpeg). Home page `post_content` (stale pre-revamp copy
  Elementor never renders) re-synced to remove dangling refs. **Kept**: 2929 Phil, 2930 Micah, logos
  2977/2978/2979, favicon 2976. `GV_Logo_Main.png` (2949) left in place — referenced by `astra-settings`
  (the Astra logo slot the GV Header SVG overrides; not rendered on any live page).
- **Deploy**: `build/scripts/deploy-images.php` (`wp eval-file`) with per-target backups to `~/backups/`,
  then `wp elementor flush-css && wp litespeed-purge all`. QA'd live with a headless browser (desktop +
  mobile) and `curl`; all new images return 200, no old URLs remain on any page.

### 2026-06-27 — Full frontend revamp (icons, footer socials, a11y, forms)
Client brief: professional brand-aligned revamp — fix missing footer FB/IG icons, kill amateur
Unicode "icons", remove placeholder/"coming soon" content, improve contrast/spacing/mobile/forms,
and replace all old imagery with a new AI-generated library (keep only Phil Handy + Micah Lancaster
headshots + SVG logos). Imagery is generated via the `codex` CLI (OpenAI image model).

- **`build/templates/footer.html`** — replaced text social labels (`IG`/`f`/`WA`) with real brand
  SVG icons (Instagram, Facebook, WhatsApp; Simple Icons) + `aria-label`s. This was the "missing FB/IG
  icons" issue. IG handle `gvbasketballl` (3 L's) left intact (correct).
- **`build/mu-plugins/gv-assets/gv-brand.css`** — SVG icon system (`.gv-card__icon svg`, `.gv-ic`,
  `.gv-quote__stars svg`, `.gv-contact-item__ic svg`, `.gv-footer__socials a svg`, `.gv-program__ic`);
  global `:focus-visible` rings; `prefers-reduced-motion` guard; mobile form inputs (16px / 48px
  targets / focus rings) for contact + waiver; keyboard-accessible mobile nav toggle; nav CTA ≥44px;
  footer fine-print contrast nudge (`#7e8aa6`→`#9aa8c4`).
- **Pages** — replaced every Unicode glyph with Lucide SVG icons: `about` (6), `athlete-development`
  (3), `success-stories` (6 + added icons to 6 milestone cards), `home`/`training-programs` (added
  program-head icons + Makati/Ortigas map-pins). `testimonials` (hidden): removed fake video cards,
  stars → SVG. `success-stories`: "Featured Stories Coming Soon" → confident "Follow The Journey"
  (IG + FB). Icon reference: `build/assets/icons.html`.
- **Imagery (in progress)** — prompts in `build/assets/img/PROMPTS.md` + `manifest.json`; generator
  script `/tmp/gvb_generate_images.py`. **Blocked**: OpenAI Platform API `billing_hard_limit_reached`.
  Old hero/section/gallery images still live until generation succeeds, then swap + remove old media
  (GV.png 2936, GV2.png 2937/2938, clinic jpg, hero 2917) and rebuild `gallery.html`.
- **Deploy** — `build/scripts/deploy-revamp.php` (per-page `_elementor_data`/content backups to
  `~/backups/`, then `gv_set_page_html` for 2887/26/2981/2984/2985 + `gv_set_theme_part_blocks` footer
  w/ newsletter 3005 + conditions). CSS scp'd; Elementor CSS + LiteSpeed purged. Verified live: 0
  Unicode glyphs, footer SVG socials on all pages, no "coming soon".

### 2026-06-27 — Home page minimalist revamp
Client brief: rethink the home page as **minimalist** (peg: `rootedheatstudio.rezerv.co`), drop the
current GV-shot images, stay on-brand. Decisions: image-led with a *new* hero photo (client to
supply), keep the Phil Handy / Micah Lancaster mentor headshots, lean **5-section** layout, and a
**light & airy** white-dominant palette.

- **`build/pages/home.html`** — full rewrite. New structure: (1) editorial split hero (white, big
  Bebas headline + 2 CTAs, photo on the right with a branded navy fallback until the real image
  lands), (2) positioning + "Mentored by NBA Skills Coaches" with the two mentor cards, (3) 3-up
  programs preview (concise — full detail lives on `/training-programs/`), (4) the 6-step Athlete
  Development System, (5) final CTA. Dropped the old stats strip, who-we-are split, standalone
  mentors grid, and the 4-up trust grid.
- **`build/mu-plugins/gv-assets/gv-brand.css`** — additive only (no existing components changed):
  `.gv-hero--light` (white editorial hero, 2-col → 1-col ≤860px), `.gv-hero__media` /
  `.gv-hero__media-fallback` (rounded media frame + branded placeholder), `.gv-section--airy`,
  `.gv-narrow`, and a `.gv-program__body p` rule for the slimmer program cards.
- **Mobile responsiveness** — verified all 8 marketing pages (with header + footer) at 390px: zero
  horizontal overflow, burger nav collapses, all grids and the footer stack to one column.
  Confirmed on the live site (desktop 1280px + mobile 390px) after deploy.
- **Deploy** — `gv-brand.css` scp'd; `home.html` applied via `gv_set_page_html(2887, …)`; Elementor
  CSS + LiteSpeed caches purged. Targeted pre-change backups in `~/backups/` (`gv-brand.css.bak-*`,
  `home-2887-content.bak-*.html`, `home-2887-elementor_data.bak-*.json`). Note: `wp db export`
  (mysqldump) errors on this host — use targeted file/post backups (the baseline full backup
  `gvbasketball-20260627-015018/` remains the safety net).
