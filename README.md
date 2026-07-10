# GV Basketball

A custom WordPress site for Grace Valley Basketball, built with [Elementor](https://elementor.com/), [Astra](https://wpastra.com/), and custom PHP plugins. The site includes member booking via [LatePoint](https://latepoint.com/), WPForms for contact/waivers, and passwordless OTP authentication for member signup and login.

**Live Site:** https://gvbasketball.com  
**Hosting:** Hostinger (Premium)  
**Deployment:** SSH/SCP to production + PHP script evaluation on server

---

## Quick Start for Developers

### 1. Clone the Repository
```bash
git clone <repo-url> gvbasketball
cd gvbasketball
```

### 2. Read the Documentation
Start with **[wiki/index.md](wiki/index.md)** for a complete overview of all project documentation. Key pages:
- **[AGENTS.md](AGENTS.md)** — Agent runbook & wiki schema
- **[wiki/architecture.md](wiki/architecture.md)** — Custom plugins and WordPress architecture
- **[wiki/deployment-workflows.md](wiki/deployment-workflows.md)** — How to deploy changes
- **[wiki/log.md](wiki/log.md)** — Chronological changelog of all updates

### 3. Make Changes Locally
All deployable code lives in `build/`:
- `build/mu-plugins/` — Custom WordPress plugins
- `build/pages/` — Page HTML and templates
- `build/assets/` — CSS, JavaScript, and optimized images
- `build/scripts/` — Deployment and configuration scripts

### 4. Deploy to Production
See **[wiki/deployment-workflows.md](wiki/deployment-workflows.md)** for the complete deployment process. The "Golden Workflow" is:
```bash
# 1. Copy files to production over SSH
scp -r build/* user@host:/path/to/wordpress/wp-content/

# 2. Run deployment scripts on the server
ssh user@host
wp eval-file /path/to/script.php

# 3. Clear WordPress cache
wp cache flush
```

### 5. Update the Wiki
After completing a task, append an entry to **[wiki/log.md](wiki/log.md)** with the date, task name, goal, and changes made.

---

## Directory Structure

```
gvbasketball/
├── README.md                     # This file
├── AGENTS.md                     # Runbook and wiki schema
├── CLAUDE.md                     # Project instructions (references AGENTS.md)
├── .git/                         # Git repository
├── .gitignore                    # Excludes node_modules, .env, graphify-out, etc.
├── .env                          # Local secrets (NOT COMMITTED)
│
├── wiki/                         # LLM Wiki — persistent documentation
│   ├── index.md                  # Wiki table of contents
│   ├── log.md                    # Chronological project changelog
│   ├── architecture.md           # WordPress architecture & custom plugins
│   ├── design-system.md          # Colors, fonts, CSS classes, photo workflows
│   ├── pages.md                  # Page listings, post IDs, templates
│   ├── booking-latepoint.md      # Scheduler config, OTP auth
│   ├── forms-and-emails.md       # WPForms, FluentSMTP setup
│   ├── access-and-hosting.md     # SSH, database backup commands
│   ├── deployment-workflows.md   # Deploy process and scripts
│   └── client-status.md          # Client revisions and action items
│
├── build/                        # SOURCE OF TRUTH — deployed to production
│   ├── mu-plugins/               # Custom WordPress Must-Use plugins
│   │   ├── gv-brand.php          # Brand styles & color constants
│   │   ├── gv-build.php          # Core functionality
│   │   ├── gv-otp-email.php      # Passwordless login/signup
│   │   ├── gv-request-form.php   # Request/inquiry form handler
│   │   ├── tests/                # Unit tests
│   │   └── gv-assets/            # Plugin CSS and JS
│   │
│   ├── pages/                    # Page HTML content (Elementor exports)
│   │   └── *.html                # Page template files
│   │
│   ├── templates/                # Elementor Theme Builder templates
│   │   ├── header.html           # Header template
│   │   ├── footer.html           # Footer template
│   │   └── *.html                # Other structural templates
│   │
│   ├── assets/                   # Production CSS, JS, optimized images
│   │   ├── css/                  # Compiled stylesheets
│   │   ├── js/                   # JavaScript bundles
│   │   └── images/               # Optimized web images
│   │
│   └── scripts/                  # Deployment scripts (run via wp eval-file)
│       ├── deploy-refine.php
│       ├── apply-pages.php
│       ├── setup-latepoint.php
│       ├── enable-member-auth.php
│       └── *.php                 # Other setup/config scripts
│
├── resources/                    # Archive, reference, non-deployed assets
│   ├── docs/                     # Client reports, specs, screenshots
│   ├── client-revisions/         # Revision feedback, source photos
│   ├── raw-assets/               # High-res uncompressed images
│   ├── logos/                    # Master SVG brand files
│   └── backups/                  # Database dumps, filesystem backups
│
└── graphify-out/                 # (Gitignored) Knowledge graph output
```

---

## Key Technologies

- **WordPress** — Core CMS and content management
- **Elementor & Astra** — Page builder and theme framework
- **Custom PHP Plugins** — `gv-brand`, `gv-build`, `gv-otp-email`, `gv-request-form`
- **LatePoint** — Booking scheduler with member authentication
- **WPForms** — Contact forms, waivers, newsletters
- **FluentSMTP** — Transactional email via Google OAuth
- **Turnstile** — CAPTCHA protection for forms
- **ImageMagick** — Photo normalization (`normalize-photo.sh`)

---

## Common Tasks

### Adding a New Page
1. Design the page in Elementor on the live site
2. Export the page HTML to `build/pages/`
3. Create a corresponding entry in **[wiki/pages.md](wiki/pages.md)** with the post ID and slug
4. Run `build/scripts/apply-pages.php` via `wp eval-file` to sync
5. Log the change in **[wiki/log.md](wiki/log.md)**

### Updating the Design System
1. Modify brand colors or fonts in `build/mu-plugins/gv-brand.php`
2. Update CSS in `build/mu-plugins/gv-assets/gv-brand.css`
3. Update **[wiki/design-system.md](wiki/design-system.md)** with the new tokens
4. Deploy via SCP and clear cache
5. Log the change

### Managing Bookings (LatePoint)
Refer to **[wiki/booking-latepoint.md](wiki/booking-latepoint.md)** for:
- Venue periods and availability
- OTP authentication flow
- Member signup/login setup
- Scheduler configuration

### Setting Up Email Workflows
Refer to **[wiki/forms-and-emails.md](wiki/forms-and-emails.md)** for:
- WPForms inventory and setup
- FluentSMTP Google OAuth configuration
- Transactional email routing

---

## Wiki Maintenance

This project uses the **LLM Wiki Pattern** — a compounding markdown-based knowledge base maintained by LLM agents. The wiki serves as the single source of truth for all architectural decisions, configurations, and project history.

### When You Make a Change
1. **Update the code** in `build/`
2. **Update the wiki** by finding the relevant topic file (e.g., `wiki/pages.md`, `wiki/design-system.md`) and refining the documentation to match the new reality
3. **Log the change** by appending an entry to **[wiki/log.md](wiki/log.md)**

### Wiki Files at a Glance
| File | Purpose |
|------|---------|
| [log.md](wiki/log.md) | Chronological project changelog — add entries here after each task |
| [architecture.md](wiki/architecture.md) | Custom plugins, Must-Use plugin bindings, WordPress hooks |
| [design-system.md](wiki/design-system.md) | Brand colors, font tokens, CSS classes, photo workflows |
| [pages.md](wiki/pages.md) | Post IDs, slugs, templates, page structure |
| [booking-latepoint.md](wiki/booking-latepoint.md) | Scheduler settings, OTP auth, member signup |
| [forms-and-emails.md](wiki/forms-and-emails.md) | WPForms, FluentSMTP, email routing |
| [deployment-workflows.md](wiki/deployment-workflows.md) | SSH/SCP process, script inventory, cache clearing |
| [access-and-hosting.md](wiki/access-and-hosting.md) | Server credentials, backup commands, database snapshots |
| [client-status.md](wiki/client-status.md) | Client revisions, completed work, pending items |

---

## Deployment

### Prerequisites
- SSH access to Hostinger server
- WordPress CLI (`wp`) installed on the server
- Local `build/` directory is clean and up-to-date

### Golden Workflow
```bash
# 1. Push files to the server
scp -r build/* user@host.hostinger.com:/path/to/wp-content/

# 2. SSH into the server
ssh user@host.hostinger.com

# 3. Run deployment scripts (if needed)
wp eval-file /path/to/build/scripts/apply-pages.php
wp eval-file /path/to/build/scripts/setup-latepoint.php

# 4. Clear all caches
wp cache flush
wp rocket clean
```

For full deployment details, see **[wiki/deployment-workflows.md](wiki/deployment-workflows.md)**.

---

## Troubleshooting & Support

- **Pages not updating?** Check that `apply-pages.php` was run and cache was cleared.
- **Member auth not working?** Verify OTP email settings in **[wiki/booking-latepoint.md](wiki/booking-latepoint.md)**.
- **Email not being sent?** Check FluentSMTP configuration in **[wiki/forms-and-emails.md](wiki/forms-and-emails.md)**.
- **Design changes not showing?** Ensure `gv-brand.php` was deployed and cache was flushed.

For in-depth troubleshooting, refer to the wiki pages listed above or check the server logs on Hostinger.

---

## Getting Help

The wiki is the authoritative source. When you're unsure:
1. Check **[wiki/index.md](wiki/index.md)** to find the relevant topic
2. Read the corresponding wiki page
3. If you make changes, update the wiki to reflect your changes

Questions about the WordPress architecture? Check **[wiki/architecture.md](wiki/architecture.md)**.  
Questions about deployment? Check **[wiki/deployment-workflows.md](wiki/deployment-workflows.md)**.  
Questions about pages or templates? Check **[wiki/pages.md](wiki/pages.md)**.

---

## Agent Instructions

If you're an AI agent working on this project, read **[AGENTS.md](AGENTS.md)** first. It contains:
- Wiki maintenance requirements
- Directory hygiene guidelines
- How to use `/graphify` for knowledge graphs
- How to log changes to `wiki/log.md`

---

**Last Updated:** 2026-07-10  
**Maintained by:** LLM agents and the engineering team
