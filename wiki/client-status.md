# GV Basketball Wiki — Client Status & Feedback

A plain-language summary of completed updates, delivered features, and pending items awaiting client feedback.

---

## Recent Highlights (July 2026 Revision Round)

0. **GV Members & Consultation Requests (July 10):** The member portal moved to **`/members/`** as the GV Members **Training Journal**. Parents sign in with a passwordless email code (any email works — no passwords). Booking a consultation is now a real request in the system: the "Book a Consultation" button opens a venue chooser (including "I don't have a venue yet"), the parent picks a day ("Request this day"), and the request instantly appears in their Training Journal as *Submitted*. Coach Gino gets a branded email with the request details and a secure **Finalize Consultation** link to lock in the exact 45-minute time — after which the parent's journal shows it as *Confirmed* with the exact time and an add-to-calendar link. **Note for Coach Gino:** confirming on the site does **not** auto-email the parent the final time — contact them personally (the coach email says this too). Full walkthrough with screenshots in `docs/CLIENT-REPORT-JULY.html`.
1. **Header Navigation — Instagram + Member Login:** Added a pink Instagram icon linking to `@gvbasketballl`, and a discreet **Member Login** account icon beside it that opens the member portal at `/members/`.
2. **Homepage Hero Video:** Added a loopable training video in a clean frame on desktop and as a mobile screen background with a readable dark overlay.
3. **Real Photography Sync:** Replaced stock placeholders with real coaching shots of Coach Gino and athletes. Widescreen crop is on the Home page, Coach Gino's portrait is on the About page, and real action shots are on the Programs page.
4. **Program Schedules Re-write:** Updated content to reflect the three true venues: **Dasma, Makati** (Mon/Wed/Thu), **Urdaneta Village** (Fri/Sun), and **Corinthian Gardens** (Sun). Simplified pricing text to "shared during the consultation."
5. **Premium Gold Accents:** Introduced `#C9A24B` gold to key blocks (CTA background, footer text highlights, gold button styling, and newsletter button styling) to elevate the premium feel.
6. **Booking Flow Graphic:** Injected a 5-step payment-free purchase graphic (*Book Online → We Confirm → Reserve Spot → Confirmed → Train*) to clarify that credit card details are not collected on-site.
7. **Gallery Real Photos Grid:** Replaced AI-generated placeholders on `/gallery/` with a 3x3 grid of 9 real session images. The Gallery is now integrated into the header navigation and footer Explore links.
8. **Consultation Form Upgrade *(superseded by Highlight 0)*:** The email-only popup form was replaced by the real consultation-request wizard — venue chooser, day request, and Training Journal tracking.
9. **Legacy Link Redirects:** Old links to `/book-a-consultation/`, `/booking/`, and `/customer-cabinet/` now 301 to the current destinations (`/training-programs/` and `/members/`).
10. **Newsletter Strip Hidden:** Deactivated the footer newsletter banner until campaigns are ready, leaving the footer clean.
11. **Hero Background Photos Restored:** Brought back the cinematic photo backgrounds behind every page's hero header (they had been flattened to plain navy on Jul 9). All 12 heroes now carry a photo under a navy/orange overlay so headings stay crisp. Per your note, the home, about, and programs heroes use **generic, no-people b-roll** (ball through the net, an empty court, ball-and-cones) rather than shots with people. *NB: this reverses the earlier "plain navy heroes" request — flag for Coach Gino's confirmation.*

---

## Pending Actions & Feedback (Where We Stand)

The following items are currently draft-only, waiting for client content before going live:

### 1. FAQ Copy Polish
- **Status:** Staged locations answer, but standard FAQs (e.g., equipment needed, weather policies) contain boilerplate text.
- **Client Input Needed:** Real answers for remaining FAQ items.

### 2. Testimonials Page
- **Status:** Draft page `/testimonials/` (ID 2986) remains hidden from the navigation menus.
- **Client Input Needed:** High-resolution photo attachments and real quotes from parents/players to populate this page.

### 3. Payment Gateway Integration
- **Status:** Strictly informational. If online payment processing (GCash, credit card) is required in the future, Stripe/Paymongo plugins will need to be purchased and integrated.
- **Client Input Needed:** Confirm if cash/bank transfer outside the site remains the standard indefinitely.
