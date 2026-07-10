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
| **`setup-latepoint.php`** | Automates LatePoint location schedules and services. | Database options sync |
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
   Run the Purge Cache command inside Gino's Cloudflare console or send a POST request to Cloudflare API (requires zone ID and token). Note that token limitations exist, so manual console purge is preferred if API fails with unauthorized error.
