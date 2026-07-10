# GV Basketball Wiki — Client Status & Feedback

A plain-language summary of completed updates, delivered features, and pending items awaiting client feedback.

---

## Recent Highlights (July 2026 Revision Round)

1. **Header Navigation — Instagram + Member Login:** Added a pink Instagram icon linking to `@gvbasketballl`, and (re-added) a discreet **Member Login** account icon beside it that opens the member portal at `/booking/`.
0. **Member Login Portal Activated (view-only):** Turned the member portal back on. Members sign in with a passwordless email code and can **view** their consultation schedule and history. To change a day, they message the team (self-reschedule is a paid LatePoint add-on and is intentionally not enabled). Consultation records are added by the coach in the LatePoint admin when confirming the day; the coach's request email now carries an **"Add to Google Calendar"** button that prefills the event with the client as a guest.
2. **Homepage Hero Video:** Added a loopable training video in a clean frame on desktop and as a mobile screen background with a readable dark overlay.
3. **Real Photography Sync:** Replaced stock placeholders with real coaching shots of Coach Gino and athletes. Widescreen crop is on the Home page, Coach Gino's portrait is on the About page, and real action shots are on the Programs page.
4. **Program Schedules Re-write:** Updated content to reflect the three true venues: **Dasma, Makati** (Mon/Wed/Thu), **Urdaneta Village** (Fri/Sun), and **Corinthian Gardens** (Sun). Simplified pricing text to "shared during the consultation."
5. **Premium Gold Accents:** Introduced `#C9A24B` gold to key blocks (CTA background, footer text highlights, gold button styling, and newsletter button styling) to elevate the premium feel.
6. **Booking Flow Graphic:** Injected a 5-step payment-free purchase graphic (*Book Online → We Confirm → Reserve Spot → Confirmed → Train*) to clarify that credit card details are not collected on-site.
7. **Gallery Real Photos Grid:** Replaced AI-generated placeholders on `/gallery/` with a 3x3 grid of 9 real session images. The Gallery is now integrated into the header navigation and footer Explore links.
8. **Consultation Form Upgrade:** The popup modal now features a location picker and day-selection chips. The days dynamically update based on location (e.g. selecting Dasma hides all checkboxes except Mon, Wed, Thu). Modal emails include chosen venue/days.
9. **Modal Auto-Open Interception:** Legacy links pointing to `/book-a-consultation/` now 302 redirect to `/training-programs/?gv_open_modal=1` to open the modal immediately. Legacy button clicks are intercepted.
10. **Newsletter Strip Hidden:** Deactivated the footer newsletter banner until campaigns are ready, leaving the footer clean.

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
