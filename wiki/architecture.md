# GV Basketball Wiki — Build Architecture

Overview of the custom front-end architecture, must-use (MU) plugins, and build helpers integrated with the Elementor and Astra theme layer.

---

## Front-End Strategy

The front-end design is engineered as **hand-crafted semantic HTML templates and a global CSS design system**, enqueued on top of the WordPress framework. 

This enables maximum developer control over UI precision, responsiveness, and performance, while keeping page nodes native to WordPress (so they can be previewed or configured via WP-CLI).

---

## Custom Must-Use Plugins (`mu-plugins`)

All custom backend hooks and helpers are deployed in `wp-content/mu-plugins/`. These are automatically loaded by WordPress and cannot be disabled by users.

### 1. Brand Enqueuer (`gv-brand.php`)
- **Location:** `wp-content/mu-plugins/gv-brand.php`
- **Purpose:** Enqueues the global compiled design system stylesheet (`wp-content/mu-plugins/gv-assets/gv-brand.css`) site-wide.
- **Cache Busting:** Uses the file modification time (`filemtime`) as the style version parameter to instantly bypass browser/network caching during updates.

### 2. Build Helper Engine (`gv-build.php`)
- **Location:** `wp-content/mu-plugins/gv-build.php`
- **Purpose:** Exposes a collection of custom PHP utilities utilized by the deploy scripts to inject page layouts programmatically:
  - `gv_set_page_html($page_id, $html)`: Replaces a page's content with a single full-width Elementor HTML text widget containing the provided HTML code.
  - `gv_set_page_blocks($page_id, $blocks)`: Sets a page from an ordered array of HTML or shortcode blocks.
  - `gv_set_theme_part($title, $type, $html)`: Registers or updates Elementor Theme Builder sections (e.g. Header or Footer) globally.
  - `gv_ensure_page($slug, $title)`: Idempotently verifies if a page exists in the database and creates a draft if missing.

### 3. Member OTP branding (`gv-otp-email.php`)
- **Location:** `wp-content/mu-plugins/gv-otp-email.php`
- **Purpose:** Intercepts the default plain-text LatePoint login OTP emails via the `wp_mail` filter. If the email subject contains "OTP", it replaces the body with a branded HTML email (featuring the GV logo, navy/orange theme colors, and structured layout).

### 4. GV Members & Consultation (`gv-members.php` + `gv-members/`)
- **Location:** `wp-content/mu-plugins/gv-members.php` with modules in `wp-content/mu-plugins/gv-members/` (`core.php`, `booking.php`, `emails.php`, `auth.php`, `portal.php`, `finalize.php`, `assets/`).
- **Purpose:** The members/consultation system — passwordless OTP login for any email (`gv_otp_*` AJAX), the `[gv_members_portal]` Training Journal on `/members/` (page 2983), the modal-only consultation wizard bridge (per-venue hidden LatePoint triggers + venue chooser dialog), branded consultation emails, and the coach's tokenized finalize screen. Full details in [booking-latepoint.md](file:///Users/rico/Git/gvbasketball/wiki/booking-latepoint.md).
- **Tests:** Framework-free suites in `build/mu-plugins/tests/` — run `for t in build/mu-plugins/tests/test-*.php; do php "$t"; done`.

---

## Technical Gotchas & Constraints

### Theme Builder Conditions Option
Elementor Pro caches display conditions for headers, footers, and archive templates in the WordPress option `elementor_pro_theme_builder_conditions`. 
For example:
```php
update_option('elementor_pro_theme_builder_conditions', [
    'header' => [ 3002 => ['include/general'] ],
    'footer' => [ 2991 => ['include/general'] ]
]);
```
- **Gotcha:** Simply modifying the post meta conditions on the template post is **not** enough. The option must be updated or flushed, otherwise Elementor will fail to render the new header/footer. Custom deploy scripts (e.g. `build-extras.php`) automatically run this option update.

### Astra Body Background Override
- The active Astra starter theme historically enqueued a dark/black canvas.
- To maintain consistency and contrast, `gv-brand.css` forces `html body { background: #fff !important; }` and defaults section wrappers to white, guaranteeing text is readable site-wide.
