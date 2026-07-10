# GV Basketball Website — Progress Update

*Prepared for the client · July 2026*

This is a plain-language summary of the work completed on the GV Basketball website in the latest revision round. No technical knowledge needed — it's meant to be something you can read and share.

---

## The headlines

0. **Header Navigation Update**: Replaced the "Member Login" icon/link in the header navigation with a pink Instagram icon that links directly to the GV Basketball Instagram account (`@gvbasketballl`) in a new tab. This unifies the top-level contact/social options and matches the Instagram-only contact preference.
1. **Home Hero Video**: The homepage hero now features a live training video. On desktop it plays in a clean, phone-shaped frame beside the headline (crisp, no stretching); on phones it fills the screen behind the text with a subtle dark overlay so the words stay easy to read. The clip is muted, loops automatically, and was compressed to ~2 MB so it loads fast.
2. **Real Photos Deployed**: We have replaced the previous placeholder imagery with actual photos of Coach Gino and GV Basketball training sessions (optimized to WebP format for fast loading). A widescreen crop of your training session now serves as the Home hero background, Coach Gino's official portrait is on the About page hero and story section, and relevant action shots are placed on each program detail.
3. **Training Programs Content & Schedule Re-write**:
   - Swapped out the Makati & Ortigas references for your three actual venues: **Dasma, Makati** (Mon, Wed, Thu), **Urdaneta Village** (Fri, Sun), and **Corinthian Gardens** (Sun).
   - Removed the old time grids (3–4 PM / 4–6 PM) in favor of a clean Venue & Day schedule table.
   - Updated the Small Group maximum capacity to **6 athletes** (previously 4–5) and added the **all-skill-levels inclusivity note**.
   - Updated Elite Performance to feature **aqua training** as part of its integrated system.
   - Simplified the pricing message to indicate that program options and investment are customized and shared during the consultation.
4. **Premium Scoped Gold Elevation**: We introduced a premium gold accent color (`#C9A24B`) strictly in specific blocks to elevate the academy's feel without diluting the primary orange branding.
   - The shared call-to-action (CTA) section ("Ready To Become Your Best?") now features a deep navy gradient background, gold typography, a subtle GV watermark, a basketball icon divider, and a gold button ("Request Training") with a gold-outline Instagram button.
   - Added a **Trust Badge row** in the CTA section to highlight your academy standards: *Personalized Plan · Measurable Progress · Elite Standards · Results That Last*.
   - Streamlined the site footer into a minimalist, premium layout featuring gold accents, an Instagram-only social icon, a clean three-column address structure, and a compact link menu.
5. **Informational Booking & Payment Flow Graphic**: Near the request form on the consultation page, we added a clean, styled 5-step visual flow (*Book Online → We Confirm → Reserve Your Spot → Booking Confirmed → Train*) explaining how purchase and scheduling work. It explicitly states that payments are handled directly off-site and **no credit card or bank details** are collected on the website, reassuring premium clients.
6. **LatePoint Scheduler Updates**: We reconfigured the booking engine behind the scenes to map the three new venues and set active work periods matching your days (Dasma = Mon/Wed/Thu, Urdaneta = Fri/Sun, Corinthian = Sun). A database backup was taken first for safety.
7. **Polished Design & Copy Alignment**: Based on your feedback, we completed a final polish pass to align the homepage, footer, and buttons with your design peg:
   - **Coach Gino side by side**: Featured Coach Gino first in the mentors block, side-by-side with Phil Handy and Micah Lancaster, as Founder & Head Coach.
   - **New Widescreen Hero**: Installed a crisp, high-resolution AI-generated home hero image that displays clearly at high density.
   - **Real Brand Logos**: Replaced the plain-text footer "GVBASKETBALL" with the official white wordmark logo, and the plain-text watermark ("GV") with the white monogram emblem.
   - **Locations moved**: The locations grid has been moved from the Contact page to the Home page for better flow.
   - **"Book a Consultation" Copy**: Replaced all site-facing occurrences of "Request Training" with "Book a Consultation" (including navigation, buttons, page titles, and forms).
   - **Button styling**: Hovering the gold buttons keeps the text white (previously turned orange), and the newsletter Subscribe button is now styled gold.
8. **Branded Photo Filters Deployed**: We applied a unified, premium CSS-based photo filtering system to make all images consistent with the GV Basketball branding.
   - **Action & Portrait Photos**: Applied a subtle **"Warm Hardwood"** filter (warm contrast) to all inline action and coach portrait photos to unify shots taken under different lighting while keeping skin tones natural. These photos transition back to their original color when hovered/focused.
   - **Hero Backgrounds**: Connected hero background images to the deep navy brand color using a **"Luminosity Blend"** mode. This turns background images into a cohesive monochrome navy texture, elevating the visual appeal and ensuring high text readability.
9. **Gallery Page Revamp with Real Photos**: We updated the Gallery page (`/gallery/`) to feature a balanced 3x3 grid of 9 real, high-performance training and coaching photos from your sessions, completely replacing the previous AI-generated placeholders. The Gallery page has also been integrated into both the main header navigation menu and the footer Explore links.
10. **Consultation Form Upgrade — Location & Day Selection**: The "Book a Consultation" modal on the Training Programs page now includes a **location dropdown** (Dasma Makati, Urdaneta Village, Corinthian Gardens, or Open to any) and **day selection chips** that automatically update based on the chosen location. For example, selecting "Dasma, Makati" shows only Mon, Wed & Thu. The preferred time field is now optional with a notes label. Both the admin notification email and the confirmation email sent to the parent now include the selected location and day(s). The modal subtitle was also updated to a neutral, third-person tone.
11. **All Consultation CTAs Now Open the Modal**: We updated the old consultation-link fallback so any remaining **"Book a Consultation"** links that previously routed people to the Training Programs page now open the consultation modal directly. The legacy `/book-a-consultation/` URL now forwards to Training Programs with the modal auto-opened, and older consultation buttons aimed at that path are intercepted into the same modal flow. We deployed the plugin update, flushed caches, and verified the new redirect target and modal script live.

---

## How to view the site

Just visit **https://gvbasketball.com** on your phone or computer. Everything described here is already live. If a page looks like an older version, refresh it (it's just your browser remembering the old one).

Worth checking on both a **phone and a desktop** — a lot of the polish is in how it adapts to mobile layouts.

---

## Where we stand (Pending Input)

These updates fulfill the design revisions you approved. The following items remain deferred / waiting for your final input before next steps:

1. **FAQ Detailed Answers**: We updated the location answer to list your three new venues, but the other FAQ answers remain untouched. Let us know when you're ready to revise the rest of the FAQ content.
2. **Testimonials Page**: The Testimonials page (and references to client quotes) remains draft/hidden until you are ready to send real testimonials with photos.
3. **Payments**: The site remains strictly informational for payment. If you decide to transition to online booking payments in the future, we will need to integrate a payment provider (like Stripe or GCash), which is out of scope for this revision.

---

*Questions or change requests? Reply with notes against any section above and we'll fold them in.*

---

## Update — July 9, 2026

We removed the newsletter signup strip from the site footer for now, since the newsletter isn’t being used yet. The footer still keeps the streamlined brand/logo, links, and contact details, and the hidden newsletter setup can be restored later without rebuilding it from scratch.

## Update — July 9, 2026

We refreshed the three Training Programs photos using real GV Basketball session images as the source. The new versions stay much closer to the actual gallery photos: clean indoor/covered-court lighting, natural colors, and light polish only. They are already live on the Training Programs page.
