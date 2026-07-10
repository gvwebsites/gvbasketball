# GV Basketball Wiki — Workflows & Deployment

Step-by-step instructions for editing files, deploying them to the remote server, and managing server caches.

---

## The Golden Workflow

> Edit in local `build/` directory → `scp` file to host → run apply command (if database sync is needed) → flush server cache.

---

## 1. Deploying CSS

The brand stylesheet `gv-brand.css` is served directly from the server's `mu-plugins` folder.

```bash
# 1. Upload CSS to server public directory
scp build/mu-plugins/gv-assets/gv-brand.css gvweb:/home/u907133977/domains/gvbasketball.com/public_html/wp-content/mu-plugins/gv-assets/gv-brand.css

# 2. Flush LiteSpeed CDN and Elementor style cache
ssh gvweb 'wp elementor flush-css && wp litespeed-purge all'
```

---

## 2. Deploying Pages

Page content is enqueued inside Elementor text widgets so pages look clean but remain editable in the WP dashboard.

```bash
# 1. Upload the HTML file to server home folder
scp build/pages/home.html gvweb:~/

# 2. Execute gv_set_page_html() helper via WP-CLI on server
ssh gvweb 'wp eval "echo gv_set_page_html(2887, file_get_contents(\"/home/u907133977/home.html\"));"'

# 3. Clean up the uploaded temp file on server
ssh gvweb 'rm ~/home.html'

# 4. Flush caches
ssh gvweb 'wp elementor flush-css && wp litespeed-purge all'
```

---

## 3. Script Inventory (`build/scripts/`)

Rather than running commands manually, the repository contains server-executable PHP scripts that build pages, menus, and booking settings in batches.

### How to execute scripts
Upload the script to the home folder on the server, execute it via WP-CLI `eval-file`, and delete it:
```bash
scp build/scripts/apply-pages.php gvweb:~/
ssh gvweb 'wp eval-file ~/apply-pages.php && rm ~/apply-pages.php'
```

### Script Catalog

| Script | Purpose | Target Post ID / Action |
|---|---|---|
| **`apply-pages.php`** | Synchronizes standard marketing page HTML files on the server. | Runs `gv_set_page_html` for main pages |
| **`build-functional.php`** | Deploys booking portal, contact forms, and modal handlers. | Booking (`2983`), Contact (`2989`) |
| **`build-extras.php`** | Builds waiver page and elementor footer template. | Waiver (`3009`), Footer (`2991`) |
| **`build-menu.php`** | Re-compiles the Primary Navigation Menu and links to Astra location. | Astra theme menu mapping |
| **`deploy-training-programs.php`** | Regenerates the training programs templates. | Programs (`2981`) |
| **`setup-latepoint.php`** | Automates LatePoint location schedules and services. **⚠️ Do NOT re-run** — it reseeds data and clobbers the live consultation configuration. | Database options sync |
| **`configure-members-page.php`** | Builds the GV Members portal page. | Members (`2983`) |
| **`configure-members-consultation.php`** | Consultation wizard LatePoint config: zeroes the default work schedule, cleans stale `booking__locations` step settings. | LatePoint options/work periods |
| **`deploy-members-theme-parts.php`** | Targeted GV Header/Footer theme-part deploy (expects `header.html`/`footer.html` scp'd to `$HOME`); backs up `_elementor_data`, then **deletes `_elementor_element_cache`** and clears Elementor's files cache. | Header (`3002`), Footer (`2991`) |
| **`render-member-report-emails.php`** | Renders the three branded member emails (OTP, parent receipt, coach request) as HTML files in `~/gv-report-emails/` with fictional sample data — never sends mail. Used for client-report screenshots. | Report artifacts only |
| **`set-kit.php`** | Syncs Elementor Global Style Kit details. | Kit (`5`) |
| **`apply-hide.php`** | Hides draft sections or deactivated modules. | Global content adjustments |

---

## 4. Cache Clearing (Run After Every Deploy)

If changes do not render immediately on the browser:

1. **Clear server-side caches:**
   ```bash
   ssh gvweb 'wp elementor flush-css && wp litespeed-purge all'
   ```
2. **Clear Cloudflare edge cache (if needed):**
   Run the Purge Cache command inside Gino's Cloudflare console or send a POST request to Cloudflare API (requires zone ID and token, stored in the repo-root `.env` — read locally, never commit or print values). Note that token limitations exist, so manual console purge is preferred if API fails with unauthorized error.

3. **Elementor element cache gotcha ⚠️ (learned 2026-07-10):**
   Elementor caches each post's *rendered element HTML* in the `_elementor_element_cache` postmeta.
   Updating `_elementor_data` (e.g. theme-part deploys) does **not** invalidate it, so stale markup
   keeps serving even after LiteSpeed + Cloudflare purges. Fixes:
   - `deploy-members-theme-parts.php` deletes the meta per deployed part and calls
     `\Elementor\Plugin::$instance->files_manager->clear_cache()`.
   - The Element Cache experiment (`elementor_experiment-e_element_cache`) is now forced
     **`inactive`** site-wide (per client-dev decision, 2026-07-10) — leave it off.
