# CLAUDE.md — GV Basketball site

Operational guide for updating **https://gvbasketball.com** (WordPress on Hostinger). **Edit source in
`build/`, then deploy over SSH.**

**Docs map:**
- [`PROJECT_LOG.md`](PROJECT_LOG.md) — technical build log + changelog (source of truth for how it's built).
- [`PROGRESS_LOG.md`](PROGRESS_LOG.md) — plain-language progress summary for the client.
- [`docs/`](docs/) — working handoffs for upcoming work (e.g. `HANDOFF-member-profile.md`).

---

## 1. Connect (SSH + WP-CLI)

```bash
ssh gvweb                      # Hostinger account u907133977
# WordPress root (run all wp-cli from here):
cd /home/u907133977/domains/gvbasketball.com/public_html
```

- WP-CLI is installed (`wp ...`). PHP 8.2. There is **no** browser/admin password on hand — drive
  everything with WP-CLI over SSH.
- SSH prints a harmless "post-quantum" warning to stderr; ignore it (filter with
  `2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"`).
- **Always back up before risky DB/file ops.** ⚠️ `wp db export` currently **fails on this host**
  (exit 255 — mysqldump unavailable). For settings/table-scoped changes, snapshot the table instead:
  `wp db query "SELECT name,value FROM wp_latepoint_settings ORDER BY name;" --skip-column-names > backup.tsv`.
  (Original baseline full backup: `~/backups/gvbasketball-20260627-015018/`.)

---

## 2. The golden workflow (how updates work)

The front end is **hand-written HTML + a shared CSS design system**, mounted into Elementor by
must-use plugins (`wp-content/mu-plugins/gv-brand.php` = CSS, `gv-build.php` = helper functions,
`gv-otp-email.php` = branded member-login OTP email). You almost never touch the Elementor editor.
To change the site:

> **edit a file in `build/` → `scp` it to the server → apply with a `gv_*` helper → flush caches.**

After any change:
```bash
wp elementor flush-css && wp litespeed-purge all
```
The CSS file is cache-busted by file mtime, so a fresh `scp` + LiteSpeed purge is enough. Cloudflare
proxies the site but does not cache HTML by default; if a static asset looks stale, purge Cloudflare
or bump the file.

### Deploy the design system (CSS)
```bash
DEST=/home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins
scp build/mu-plugins/gv-assets/gv-brand.css gvweb:$DEST/gv-assets/gv-brand.css
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && wp litespeed-purge all'
```

### Deploy a marketing page (HTML widget)
```bash
scp build/pages/about.html gvweb:~/about.html
ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && \
  wp eval "echo gv_set_page_html(26, file_get_contents(getenv(\"HOME\").\"/about.html\"));" && \
  wp elementor flush-css && wp litespeed-purge all && rm ~/about.html'
```

### Deploy header / footer or functional pages
Edit `build/templates/*.html` or `build/scripts/*.php`, `scp` to `~`, then `wp eval-file ~/<script>.php`.
Header/footer rebuilds must also keep the conditions option (the scripts handle it):
```bash
# Theme Builder conditions live in option elementor_pro_theme_builder_conditions
# = ['header'=>[<id>=>['include/general']], 'footer'=>[<id>=>['include/general']]]
```

---

## 3. Build helpers (in `mu-plugins/gv-build.php`)

| Helper | Use |
|---|---|
| `gv_set_page_html($id, $html)` | Page = one full-width HTML widget |
| `gv_set_page_blocks($id, $blocks)` | Page = ordered `['type'=>'html'|'shortcode','content'=>..,'css'=>..]` widgets |
| `gv_set_theme_part($title,$type,$html)` | Theme Builder header/footer from HTML (`$type`=`header`|`footer`) |
| `gv_set_theme_part_blocks($title,$type,$blocks)` | …from html/shortcode blocks |
| `gv_ensure_page($slug,$title)` | Idempotent page create |

Reusable deploy scripts already exist in `build/scripts/`: `set-kit.php` (Elementor global
colors/fonts), `apply-pages.php` (all marketing pages), `setup-latepoint.php`, `build-functional.php`
(book/portal/contact), `build-extras.php` (waiver + newsletter + footer), `build-menu.php`,
`apply-hide.php`.

---

## 4. Site structure

### Pages (post IDs)
`/` Home **2887** · `/about/` **26** · `/training-programs/` **2981** · `/athlete-development/`
**2984** · `/success-stories/` **2985** · `/testimonials/` **2986 (draft/hidden)** · `/gallery/`
**2987** · `/faq/` **2988** · `/book-a-consultation/` **2982** · `/booking/` (portal) **2983** ·
`/contact/` **2989** · `/waiver/` **3009**.

### Theme Builder
- **GV Header** (3002): `build/templates/header.html` — `gv-nav` (logo 2977; nav = Home·About·Programs·
  FAQ·Contact; icon-only Member Login → `/booking/`; orange CTA; sticky; CSS-only mobile menu). Replaces
  Astra's header. (Development/Success/Gallery pages are live but intentionally not in the nav.)
- **GV Footer** (2991): `build/templates/footer.html` — newsletter band + 4 columns.
- Nav menu "Primary Menu" → Astra `primary` location (rebuild with `build/scripts/build-menu.php`).

### Design tokens (`gv-brand.css`)
Navy `#123B78` · Deep Navy `#021F51` · Orange `#F47B20` · Charcoal `#1C1C1E` · Steel `#6B6F76` ·
Light `#E6E7E9`. Fonts: **Bebas Neue** (display), **Montserrat** (UI), **Inter** (body). Components:
`gv-hero`, `gv-section[--light|--navy|--deep|--tight]`, `gv-wrap`, `gv-section-title`, `gv-eyebrow`,
`gv-lead`, `gv-btn[--primary|--navy|--ghost|--outline]`, `gv-grid--2/3/4`, `gv-card`, `gv-program`,
`gv-steps`/`gv-step`, `gv-person`, `gv-quote`, `gv-acc` (FAQ), `gv-gallery`, `gv-nav`, `gv-footer`.

### Booking (LatePoint, payments OFF)
Agent Coach Gino (1) · locations Makati (1)/Ortigas (2) · services Consultation/Private/Small
Group/Elite · work periods Mon/Tue/Fri/Sun 900–1080 (mins; weekday 1=Mon…7=Sun). Shortcodes:
`[latepoint_book_form]`, `[latepoint_customer_dashboard]`. Edit via `build/scripts/setup-latepoint.php`.

### Member login & signup (passwordless email OTP)
"Member Login" (nav) → `/booking/` (2983) → `[latepoint_customer_dashboard]` shows login/signup when
logged out. **Passwordless OTP over email** (every signup = verified email). Set via
`build/scripts/enable-member-auth.php`: `selected_customer_authentication_method=otp`,
`default_customer_authentication_method=otp`, `selected_customer_authentication_field_type=email`,
`page_url_customer_dashboard`/`page_url_customer_login=/booking/`, and
`notifications_email_processor=wp_mail` (**without this LatePoint email — incl. OTP — is silently
disabled**). The OTP email is branded by mu-plugin `gv-otp-email.php` (hooks `wp_mail`, swaps in HTML).
Test a send: `wp eval 'var_dump(OsOTPHelper::generateAndSendOTP("test@favor.church","email","email"));'`.

### Forms (WPForms Lite → recipient gvbasketballcoaching@gmail.com)
Contact **3003**, Newsletter **3005**, Waiver **3007**. (Lite has no Phone field — use text.)
Notification **recipient** + the displayed mailto + the LatePoint agent email all deliver to
`gvbasketballcoaching@gmail.com` (set 2026-06-29); the **From/sender** stays `info@gvbasketball.com`
(WPForms `sender_address` + the FluentSMTP connection were left unchanged).

### Email
FluentSMTP + Gmail OAuth, sender `info@gvbasketball.com`. OAuth keys are wp-config constants
`FLUENTMAIL_GMAIL_CLIENT_ID/SECRET`. Test: `wp eval 'var_dump(wp_mail("info@gvbasketball.com","test","ok"));'`.
LatePoint notifications need their own switch (`notifications_email_processor=wp_mail`) on top of FluentSMTP.
Inbound mail (form submissions) is **delivered** to `gvbasketballcoaching@gmail.com`; only the sending
identity is `info@`.

---

## 5. Gotchas (read before editing)

- **Secrets:** never print secret values. `wp config set` **echoes the value** — use `--quiet` and
  verify with `wp eval "echo strlen(CONST);"`. `.env` (gitignored) holds the Google OAuth keys, the
  **Cloudflare API token** (`CLOUDFLARE_API_TOKEN` / `_ACCOUNT_ID`), and the **Turnstile** site/secret keys.
- **Cloudflare zone** (`gvbasketball.com`, Free plan, zone `4efc307b…`): tuned for WordPress —
  `ssl=strict` (origin has a valid Let's Encrypt cert; keep it renewing or strict will hard-fail the
  site), `always_use_https=on`, `min_tls_version=1.2`, `always_online`/`early_hints`/`0rtt` on,
  `cache_level=aggressive`, `brotli`/`http3` on. **Keep `rocket_loader` OFF** (it breaks LatePoint /
  Turnstile / OTP JS) and **never add a "Cache Everything" rule** (the booking portal, request form, and
  OTP login must stay dynamic — HTML is uncached by default). The `.env` token is **account-owned**, so
  the **Page Rules API is unavailable** to it (error 1011); use the dashboard or a user-owned token for
  page/cache rules. Edit zone settings via `PATCH /zones/<zone>/settings/<id>` with the `.env` token.
- **SVG uploads** require the **Safe SVG** plugin AND running as admin: `wp media import file.svg --user=1`.
- **White background:** Astra's starter shipped a dark body; `gv-brand.css` forces white. Keep default
  `.gv-section` white with navy/charcoal text, or use `--navy`/`--deep` sections with light text.
- **Theme Builder:** setting a header/footer template's post meta is not enough — also update the
  `elementor_pro_theme_builder_conditions` option (deploy scripts do this).
- **Don't reintroduce demo content.** Media library should contain only GV brand assets.
- **Testimonials are intentionally hidden** (placeholders). To restore: publish page 2986, re-add the
  testimonial sections in `build/pages/home.html` + `success-stories.html`, re-add nav/footer links.

---

## 6. Conventions

- Brand voice: disciplined, confident, developmental — fundamentals, work ethic, basketball IQ.
  Don't invent specific stats, named athletes, schools, or testimonials.
- On-site contact is **Instagram only** (client preference): IG `@gvbasketballl`, DM link
  `https://ig.me/m/gvbasketballl` (used for every "Message on Instagram" CTA), plus the displayed email
  `gvbasketballcoaching@gmail.com` (sending identity is still `info@gvbasketball.com`).
  **WhatsApp and Facebook were removed from the site** — do not re-add them. (Off-site refs still exist:
  WhatsApp `+63 917 882 4466`, FB `/GvBasketball`, Google reviews `https://g.page/r/CS7s3B4R726oEAE/review`.)
  Locations Makati & Ortigas.
- Pricing is **never** shown publicly — "shared during the consultation." No online payments.
- After meaningful changes, update `PROJECT_LOG.md` and commit (keep `.env` out of git).
