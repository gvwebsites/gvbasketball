# GV Basketball — Agent Runbook & Wiki Schema

**Site:** https://gvbasketball.com — WordPress on Hostinger.
**Source of Truth:** `build/` (deployed to server over SSH).

---

## 1. Project Wiki (`wiki/`)

Rather than maintaining monolithic log files, this project uses the **LLM Wiki** pattern. The wiki is a persistent, compounding directory of markdown files containing architectural specifications, design systems, configurations, and workflows.

**Start here:** Refer to the [Wiki Index](file:///Users/rico/Git/gvbasketball/wiki/index.md) (`wiki/index.md`) for the complete catalog of topics:

- **[Changelog](file:///Users/rico/Git/gvbasketball/wiki/log.md) (`wiki/log.md`):** Append to this chronological log after every task completion.
- **[Access & Hosting](file:///Users/rico/Git/gvbasketball/wiki/access-and-hosting.md) (`wiki/access-and-hosting.md`):** SSH configurations and database snap command references.
- **[Build Architecture](file:///Users/rico/Git/gvbasketball/wiki/architecture.md) (`wiki/architecture.md`):** Must-Use plugins (`gv-brand.php`, `gv-build.php`, `gv-otp-email.php`) and Astra/Elementor bindings.
- **[Design System](file:///Users/rico/Git/gvbasketball/wiki/design-system.md) (`wiki/design-system.md`):** Brand colors, font tokens, and the ImageMagick `normalize-photo.sh` CLI workflow.
- **[Pages Directory](file:///Users/rico/Git/gvbasketball/wiki/pages.md) (`wiki/pages.md`):** Active post IDs, templates, and primary navigation layouts.
- **[LatePoint Booking](file:///Users/rico/Git/gvbasketball/wiki/booking-latepoint.md) (`wiki/booking-latepoint.md`):** Scheduler settings, active venue periods, and passwordless OTP email authentication.
- **[Forms & Emails](file:///Users/rico/Git/gvbasketball/wiki/forms-and-emails.md) (`wiki/forms-and-emails.md`):** WPForms inventories, FluentSMTP Google OAuth constants, and Turnstile Modal integration.
- **[Workflows & Deployment](file:///Users/rico/Git/gvbasketball/wiki/deployment-workflows.md) (`wiki/deployment-workflows.md`):** The "Golden Workflow" (edit locally, SCP to host, run `wp eval-file`, clear cache) and script directory catalog.
- **[Client Status](file:///Users/rico/Git/gvbasketball/wiki/client-status.md) (`wiki/client-status.md`):** Completed client revisions and open items waiting for Coach Gino's feedback.

---

## 2. Directory Layout & Hygiene

To maintain order and prevent agent confusion, future agents should follow this structured directory layout:

- **`build/`:** Source of truth for custom assets, mu-plugins, templates, and deploy scripts.
- **`wiki/`:** Compiling documentation, logs, and schemas.
- **`resources/`:** (Recommended) Grouping of non-deployed items:
  - `resources/docs/` (formerly `docs/`)
  - `resources/client-revisions/` (formerly `revisions/`)
  - `resources/raw-assets/` (formerly `assets/`)
  - `resources/logos/` (formerly `logo/`)
  - `resources/backups/` (formerly `backups/`)
- **Config:** `.git`, `.gitignore`, `.env` at the root.

---

## 3. Wiki Maintenance Operations

As the maintainer of this project, you must keep the wiki healthy and updated:

### A. Chronological Logging
After every change or task completion, append an entry to [log.md](file:///Users/rico/Git/gvbasketball/wiki/log.md) at the bottom.
Format:
```markdown
## [YYYY-MM-DD] task | <Short Title>
- **Goal:** Brief sentence describing the goal.
- **Changes:** Bullet points detailing modified files, enqueued assets, or server-side option variables.
```

### B. Concept Synchronization
If a task changes a system configuration (e.g. adding a new page, changing a LatePoint work period, introducing a new CSS class):
1. Update the code in `build/`.
2. Locate the corresponding file in `wiki/` (e.g. `wiki/pages.md`, `wiki/booking-latepoint.md`, or `wiki/design-system.md`).
3. Refine the documentation inside that file to match the new reality.
4. Update `wiki/index.md` if new pages are added to the wiki.

### C. Periodic Linting
Occasionally, scan the wiki to find and reconcile:
- Contradictory claims between wiki pages.
- Stale instructions or dead file paths.
- Broken markdown cross-references.

---

## 4. Knowledge Graph (graphify)

To rebuild or query the project's knowledge graph (stored in the gitignored `graphify-out/` directory):
- **Rebuild:** `/graphify .` (or `--update` for incremental updates)
- **Query:** `/graphify query "<your query>"`

