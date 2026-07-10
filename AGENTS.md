# GV Basketball — Agent Runbook

**Site:** https://gvbasketball.com — WordPress on Hostinger. **Source of truth: `build/`; deploy over SSH.**

| Doc | Purpose |
|-----|---------|
| [`PROJECT_LOG.md`](PROJECT_LOG.md) | Technical changelog (append after every change) |
| [`PROGRESS_LOG.md`](PROGRESS_LOG.md) | Client-facing progress summary (append after every change) |
| [`docs/`](docs/) | Working handoffs |

---

## 1. Connect & deploy

```bash
ssh gvweb   # user u907133977, WP root: /home/u907133977/domains/gvbasketball.com/public_html
```

- WP-CLI available. PHP 8.2. No browser/admin password — drive everything via SSH + WP-CLI.
- SSH emits a harmless "post-quantum" warning; filter with `2>&1 | grep -v "post-quantum\|store now\|upgraded. See\|vulnerable"`.
- `wp db export` **fails** on this host. Snapshot tables directly instead: `wp db query "SELECT …" > backup.tsv`.
- Original full backup: `~/backups/gvbasketball-20260627-015018/`.

### Golden workflow

> Edit in `build/` → `scp` to server → apply with `gv_*` helper → flush caches.

```bash
wp elementor flush-css && wp litespeed-purge all   # after every deploy
```

**Deploy CSS:** `scp build/mu-plugins/gv-assets/gv-brand.css gvweb:$DOCROOT/wp-content/mu-plugins/gv-assets/gv-brand.css`
**Deploy page:** `scp build/pages/<page>.html gvweb:~/ && ssh gvweb '… wp eval "echo gv_set_page_html(<ID>, file_get_contents(…));"'`
**Deploy header/footer/scripts:** `scp` script to `~`, then `wp eval-file ~/<script>.php`.

### Build helpers (`gv-build.php`)

`gv_set_page_html($id,$html)` · `gv_set_page_blocks($id,$blocks)` · `gv_set_theme_part($title,$type,$html)` · `gv_set_theme_part_blocks(…)` · `gv_ensure_page($slug,$title)`.

Deploy scripts: `build/scripts/` — `apply-pages.php`, `build-functional.php`, `build-extras.php`, `build-menu.php`, `deploy-training-programs.php`, `setup-latepoint.php`, `set-kit.php`, `apply-hide.php`.

---

## 2. Site structure

### Pages (IDs)
`/` **2887** · `/about/` **26** · `/training-programs/` **2981** · `/athlete-development/` **2984** · `/success-stories/` **2985** · `/testimonials/` **2986 (draft)** · `/gallery/` **2987** · `/faq/` **2988** · `/book-a-consultation/` **2982** (302 → /training-programs/) · `/booking/` **2983** · `/contact/` **2989** · `/waiver/` **3009**.

### Theme Builder
- **GV Header** (3002): `build/templates/header.html` — sticky, CSS-only mobile menu, IG icon, orange CTA opens consultation modal.
- **GV Footer** (2991): `build/templates/footer.html` — newsletter band + 4 columns.
- Nav menu: `build/scripts/build-menu.php` → Astra `primary` location.

### Design tokens (`gv-brand.css`)
Navy `#123B78` · Deep Navy `#021F51` · Orange `#F47B20` · Charcoal `#1C1C1E` · Steel `#6B6F76` · Light `#E6E7E9`.
Fonts: **Bebas Neue** (display), **Montserrat** (UI), **Inter** (body).

### Consultation form (mu-plugin `gv-request-form.php`)
Global modal injected via `wp_footer` — every "Book a Consultation" CTA uses `data-gv-open-modal`.
Fields: parent name, player name/age, email, phone/IG, training type, **location** (Dasma/Urdaneta/Corinthian/Any), **day checkboxes** (filtered per location), optional time/notes.
Anti-abuse: nonce, honeypot, Cloudflare Turnstile. Emails (admin notification + auto-reply) via `wp_mail` / FluentSMTP.
Page 2981 is owned by `deploy-training-programs.php` only.

### Booking (LatePoint, payments OFF)
Locations: Dasma Mon/Wed/Thu · Urdaneta Fri/Sun · Corinthian Sun. Shortcodes: `[latepoint_book_form]`, `[latepoint_customer_dashboard]`.

### Member login (passwordless OTP)
`/booking/` (2983) → LatePoint customer dashboard. OTP over email. Key setting: `notifications_email_processor=wp_mail` (without it, OTP emails are silently disabled).

### Email
FluentSMTP + Gmail OAuth, sender `info@gvbasketball.com`. Recipient (forms + LatePoint): `gvbasketballcoaching@gmail.com`.

### Forms (WPForms Lite)
Contact **3003** · Newsletter **3005** · Waiver **3007**. Recipient: `gvbasketballcoaching@gmail.com`.

---

## 3. Gotchas

- **Secrets:** never print values. `wp config set` echoes — use `--quiet`. `.env` holds OAuth keys, Cloudflare API token, Turnstile keys.
- **Cloudflare:** `ssl=strict`, `rocket_loader` OFF (breaks LatePoint/Turnstile/OTP JS). Never add "Cache Everything" rule (booking/OTP must stay dynamic). `.env` token is account-owned → Page Rules API unavailable (error 1011).
- **SVG uploads:** need Safe SVG plugin + `--user=1`.
- **Theme Builder:** always update `elementor_pro_theme_builder_conditions` option alongside post meta (deploy scripts do this).
- **Testimonials:** intentionally hidden (placeholders). To restore: publish 2986, re-add sections + nav links.

---

## 4. Conventions

- **Brand voice:** disciplined, confident, developmental. Don't invent stats, named athletes, schools, or testimonials.
- **Contact:** Instagram only (`@gvbasketballl`, DM: `https://ig.me/m/gvbasketballl`) + email `gvbasketballcoaching@gmail.com`. **No WhatsApp/Facebook.**
- **Pricing:** never shown publicly — "shared during the consultation." No online payments.
- **After every change:** update both `PROJECT_LOG.md` and `PROGRESS_LOG.md`, commit (keep `.env` out of git).

---

## 5. Knowledge graph (graphify)

Rebuild: `/graphify .` (or `--update` for incremental). Query: `/graphify query "…"`. Output in `graphify-out/` (gitignored).
