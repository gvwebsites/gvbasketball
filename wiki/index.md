# GV Basketball Wiki — Index

Welcome to the GV Basketball project wiki. This is a persistent, compounding knowledge base compiled and maintained by LLM agents in collaboration with the engineering team. It serves as the primary source of truth for the codebase, configurations, page listings, design styles, and chronological project history.

---

## Wiki Pages

The wiki is organized into the following topic-specific documents:

| Page | Purpose |
|------|---------|
| [log.md](file:///Users/rico/Git/gvbasketball/wiki/log.md) | Chronological log of all tasks, updates, and releases (replaces project/progress logs). |
| [access-and-hosting.md](file:///Users/rico/Git/gvbasketball/wiki/access-and-hosting.md) | Details on Hostinger premium hosting, SSH connections, and database/file backups. |
| [architecture.md](file:///Users/rico/Git/gvbasketball/wiki/architecture.md) | Architecture of the custom WordPress build, helper plugins (`gv-brand`, `gv-build`, `gv-otp-email`). |
| [design-system.md](file:///Users/rico/Git/gvbasketball/wiki/design-system.md) | Design tokens, color palettes, fonts, custom CSS classes, and photo-normalization workflows. |
| [pages.md](file:///Users/rico/Git/gvbasketball/wiki/pages.md) | Complete directory of pages, post IDs, slugs, header/footer templates, and nav menus. |
| [booking-latepoint.md](file:///Users/rico/Git/gvbasketball/wiki/booking-latepoint.md) | LatePoint scheduler configuration, active work periods, and member passwordless OTP login/signup. |
| [forms-and-emails.md](file:///Users/rico/Git/gvbasketball/wiki/forms-and-emails.md) | WPForms setup (contact, waiver, newsletter) and FluentSMTP transactional email routing. |
| [deployment-workflows.md](file:///Users/rico/Git/gvbasketball/wiki/deployment-workflows.md) | Deploys over SSH, helper script inventory, cache purging, and the "Golden Workflow". |
| [client-status.md](file:///Users/rico/Git/gvbasketball/wiki/client-status.md) | Client-facing headlines, completed revisions, and pending/deferred action items. |

---

## Recommended Repository Folder Structure

To keep the workspace clean, maintainable, and clear of bloat, future agents should work towards or maintain the following folder structure:

```
gvbasketball/
├── .git/                      # Git repository database
├── .gitignore                 # Excluded directories (node_modules, .env, graphify-out, etc.)
├── .env                       # Local secrets (API tokens, OAuth keys) - NEVER COMMIT
├── AGENTS.md                  # Entrypoint runbook & wiki schema
├── wiki/                      # LLM Wiki (this folder)
│   ├── index.md               # Table of Contents & Structure
│   ├── log.md                 # Chronological changelog
│   └── *.md                   # Topic-specific wiki files
├── build/                     # Source of truth for custom deployed code
│   ├── assets/                # Production css/js and optimized web images
│   ├── mu-plugins/            # Custom WordPress Must-Use plugins
│   ├── pages/                 # Marketing and structural page HTML
│   ├── templates/             # Elementor Theme Builder templates (header/footer)
│   └── scripts/               # Server-side deploy and configuration scripts
└── resources/                 # Archive, reference, and non-deployed assets (Recommended)
    ├── docs/                  # Client reports, plans, specs, and screenshots
    ├── client-revisions/      # Raw revision files, feedback notes, and source photos
    ├── raw-assets/            # High-res uncompressed photography and original graphics
    ├── logos/                 # Master vector files (SVGs) for brand logos
    └── backups/               # Database dumps and local filesystem zip backups
```

### Cleanliness Guidelines
- **No root-level clutter:** Do not place temporary files, raw images, or miscellaneous text documents in the root directory. Use `resources/` for non-code artifacts and `wiki/` for documentation.
- **Git Hygiene:** Never commit secrets from `.env`. Ensure that generated assets (e.g. `graphify-out/` or local search databases) are ignored.
