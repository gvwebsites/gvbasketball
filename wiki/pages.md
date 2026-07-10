# GV Basketball Wiki — Pages Directory

A reference guide of all public pages, their database Post IDs, slugs, template structures, and nav menus.

---

## 1. Page Inventory

All pages are live and public unless noted. Page HTML sources are located in `build/pages/` or injected via PHP scripts in `build/scripts/`.

| Page Title | URL Path / Slug | Post ID | Key Details & Layout | Source File / Builder |
|---|---|---|---|---|
| **Home** | `/` | `2887` | Minimalist layout, auto-fade video hero, Coach side-by-side mentors row, locations grid. | `build/pages/home.html` |
| **About** | `/about/` | `26` | "About GV Basketball" story, founded by Coach Gino; "Certifications & Credentials" navy section (framed 94 Feet of Game certificate + Phil Handy/Micah Lancaster cards) followed by The Coaching Philosophy; training method 2-photo fade carousel. | `build/pages/about.html` |
| **Training Programs** | `/training-programs/` | `2981` | Core details for Private, Small Group, and Elite Performance. Replaces snap shots with real action assets. | `build/pages/training-programs.html` |
| **Athlete Development** | `/athlete-development/` | `2984` | Editorial outline of GV's development system and standards. | `build/pages/athlete-development.html` |
| **Success Stories** | `/success-stories/` | `2985` | Clean success-stories layout. (Testimonials block removed). | `build/pages/success-stories.html` |
| **Testimonials (Draft)** | `/testimonials/` | `2986` | **Draft (hidden from navigation)**. Kept as a placeholder until real, approved client stories are ready. | `build/pages/testimonials.html` |
| **Gallery** | `/gallery/` | `2987` | Balanced 3x3 grid of 9 real photos (imported to WP library). Cool-neutral overlay styling. | `build/pages/gallery.html` |
| **FAQ** | `/faq/` | `2988` | HTML details accordions covering program formats, locations, age ranges, and capacity limits. No pricing copy (removed July 10). | `build/pages/faq.html` |
| **GV Elite Academy** | `/elite-academy/` | `3098` | One-screen "Coming 2027" teaser (gold `#C9A24B` accents) — in primary nav + footer Explore; deliberately no CTAs or forms. | `build/pages/elite-academy.html` |
| **Book a Consultation (Retired)** | `/book-a-consultation/` | `2982` | **Retired (draft)**. Booking is modal-only now; the path 301s to `/training-programs/` via `gv-members.php`. | — |
| **GV Members** | `/members/` | `2983` | Passwordless OTP login & Training Journal (`[gv_members_portal]`). Old `/booking/` and `/customer-cabinet/` paths 301 here. | `build/scripts/configure-members-page.php` |
| **Contact** | `/contact/` | `2989` | Standard contact form (WPForms ID 3003) & location cards. | `build/scripts/build-functional.php` |
| **Player Waiver** | `/waiver/` | `3009` | Player Waiver & Consent Form (WPForms ID 3007). | `build/scripts/build-extras.php` |

---

## 2. Elementor Theme Builder Templates

These global templates govern layouts site-wide and are injected via the Elementor Theme Builder:

- **GV Header (ID: 3002):** Sticky, dark navy navigation bar. Contains the horizontal logo, desktop menu links, a **Member Login account icon** (`.gv-nav__account`, person glyph → `/booking/`) sitting just before the Instagram icon, the Instagram link, and an orange "Book a Consultation" CTA. Implements a lightweight CSS-only hamburger menu for mobile devices. Source: `build/templates/header.html`.
- **GV Footer (ID: 2991):** Sleek 4-column footer containing the white wordmark logo, address details for the venues, Explore navigation links, and legal/developer credits. Source: `build/templates/footer.html`.
  > [!NOTE]
  > The footer previously featured a newsletter sign-up band (`[wpforms id="3005"]`). This is currently hidden site-wide since the newsletter channel is not active, but the styling classes remain in `gv-brand.css` for easy re-activation.

---

## 3. Navigation Menus

The site navigation is managed under WordPress primary location and Astra theme filters.
- **WP Menu Name:** "Primary Menu"
- **Build Script:** `build/scripts/build-menu.php`
- **Menu Hierarchy:**
  1. Home (`/`)
  2. About (`/about/`)
  3. Programs (`/training-programs/`)
     - **GV Elite Academy** (`/elite-academy/`) — gold submenu item (WP menu class `gv-navgold`; header template uses a `.gv-nav__item`/`.gv-nav__sub` CSS hover dropdown with gold link class `.gv-nav__gold`; shows indented under Programs in the mobile burger)
  4. Gallery (`/gallery/`)
  5. FAQ (`/faq/`)
  6. Contact (`/contact/`)
- **Action CTA:** The orange "Book a Consultation" button is hard-coded into the header template itself and is not part of the standard menu items array.
- **Menu ID Gotcha:** When the primary menu is rebuilt by the build script, its underlying term ID changes. The script handles re-linking this term to the Astra `primary` theme location automatically.
