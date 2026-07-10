# GV Elite Performance — Rebrand Card + Application Experience (Design Spec)

**Date:** 2026-07-10
**Source requirements:** `revisions/2026-07-10-creative-direction-client.md` (Parts B & C; Part A parked)
**Approved approach:** Custom mu-plugin application (Option A), scoped to the Elite Performance package only.

---

## 1. Goal

Reposition **Elite Performance** as **GV Elite Performance** — a selective, application-based program. Two deliverables:

1. Updated program-card and detail copy with an **APPLY NOW** CTA (Elite Performance surfaces only).
2. A premium, multi-step **athlete application** at `/apply/`, implemented as a custom mu-plugin following the `gv-request-form.php` house pattern.

## 2. Scope decisions (locked with Rico, 2026-07-10)

| Decision | Choice |
|---|---|
| Build round scope | Elite Performance package only |
| Highlight video | Link field (YouTube/Drive URL), not file upload |
| CTA replacement | Elite Performance card + its detail section only; all other programs keep **Book a Consultation** (`data-gv-consultation`) |
| Submission destination | Branded email to Coach Gino + entries stored in wp-admin |
| Button label | **APPLY NOW** (client's starred favorite) |

**Out of scope this round** (tracked in the requirements doc): stock-photo replacement and Coach Gino visibility (awaiting client photos/certifications), site-wide emotional copy pass, GV Elite Academy identity/site, future nav restructure, Founding Partners page.

## 3. Copy changes (client-final, apply verbatim)

### 3.1 Program cards — `build/pages/home.html` (~L81–87) and `build/pages/training-programs.html` (~L43–53)

- Name: **GV Elite Performance** (keep existing card markup/classes and icon).
- Kicker (`gv-program__for`): **Application Required · Limited enrollment** (replaces "By appointment").
- Body:
  - home.html (paragraph card): "The complete performance system for aspiring elite athletes — court training, strength & conditioning, recovery, and nutrition."
  - training-programs.html (list card):
    - The complete performance system for aspiring elite athletes *(lead line above the list)*
    - Court Training
    - Strength & Conditioning
    - Recovery
    - Nutrition
- CTA: `<a class="gv-btn gv-btn--primary" href="/apply/">Apply Now</a>` — **remove** `data-gv-consultation` and `role="button"` on these buttons only.

### 3.2 Detail section — `build/pages/training-programs.html` (~L118–146)

- Title: **GV Elite Performance**.
- "Who It's For": drop "aqua training"; describe the complete performance system (court training, strength & conditioning, recovery, nutrition) for serious athletes preparing for the next level. Add the selective framing: admission is by application — based on character, coachability, commitment, and potential.
- "What's Included" list: Court Training (offensive & defensive skills) / Strength & Conditioning / Recovery, mobility & injury prevention / Nutrition guidance / Structured, periodized progression.
- "Scheduling" → **Admission**: "Application required. Limited enrollment."
- CTA: **Apply Now** → `/apply/`.

### 3.3 Other copy touchpoints

- `build/scripts/setup-latepoint.php:50`: service description → "The complete performance system: court training, strength & conditioning, recovery, and nutrition." (drop "aqua training").
- `build/pages/faq.html` and any other `aqua training` mentions: align wording (grep for `aqua`).
- Consultation modal's Training Type select (`gv-request-form.php`) keeps "Elite Performance" as an option — no change; consultations may still ask about it.

## 4. `/apply/` page

New WordPress page **`/apply/`** (title: *Apply — GV Elite Performance*), source `build/pages/apply.html`, same hero/section conventions as other pages.

- Hero: "GV Elite Performance" / "Athlete Application".
- Tone-setting statement (client-final, verbatim, prominent above the form):
  > GV Elite Performance is a selective development program for committed student-athletes. Admission is based on character, coachability, commitment, and potential—not solely on current basketball ability.
- The form itself is injected by the mu-plugin via shortcode `[gv_elite_application]` so markup, validation, and processing live in one place.
- Navigation: **not** added to primary nav this round (client's future nav has APPLY; for now the page is reached via the APPLY NOW CTAs). Add to footer Explore links.

## 5. Mu-plugin — `build/mu-plugins/gv-elite-application.php`

Follows `gv-request-form.php` conventions: `gv_ea_` function prefix, nonce + honeypot + Cloudflare Turnstile, branded crest email shell, `wp_mail()` via FluentSMTP.

### 5.1 Form structure — 6 steps (client-side stepper, single POST at the end)

1. **Athlete Information** — Full Name*, Date of Birth*, School*, Grade Level*, Height, Weight. (Age auto-derived from DOB server-side; no separate Age field to keep data consistent.)
2. **Basketball Background** — Q1 years (radio, 4 options)*, Q2 current level (radio, 6 options)*, Q3 positions (text)*, Q4 current team/club/school (text, optional).
3. **Commitment** — Q5 why join (textarea)*, Q6 12-month goals (textarea)*, Q7 biggest improvement area (radio incl. Other + text)*, Q8 days/week (radio: 2/3/4/5+)*.
4. **Character** — Q9 qualities (checkboxes, **max 3 enforced client- and server-side**)*, Q10 coachable meaning (textarea)*, Q11 challenge overcome (textarea)*.
5. **Video + Parent** — Video link (URL field*, helper text: "YouTube, Google Drive, or similar — 2–4 minutes showing ball handling, shooting, finishing, footwork; game footage encouraged but not required"). Parent/Guardian Name*, Relationship*, Mobile Number*, Email* — then Parent Questionnaire: P1 why interested (textarea)*, P2 values (checkboxes + Other)*, P3 prepared to support (Yes/No radio)*, P4 anything coaches should know (textarea, optional).
6. **Final Commitment** — the three client confirmation checkboxes, **all required**, then Submit.

Stepper UX: progress indicator ("Step 2 of 6"), Back/Next buttons, client-side validation per step, all state kept in the DOM (no persistence between visits). Progressive enhancement: without JS the form renders as one long page and still submits.

### 5.2 Processing & storage

- Handler on `admin_post_nopriv_gv_elite_application` + `admin_post_...` (same pattern family as existing forms), validating nonce, honeypot, Turnstile, required fields, email format, URL format, checkbox limits.
- **Storage:** private CPT `gv_application` (not publicly queryable; admin menu "Elite Applications" with an admin list showing athlete name, age, level, parent email, date). All answers saved as post meta; admin detail rendered via a read-only meta box.
- **Emails:**
  - **Admin alert** to `gvbasketballcoaching@gmail.com`: branded shell + crest logo, all answers grouped by section, video link prominent, reply-to set to parent email.
  - **Applicant auto-reply** to parent email: confirms receipt, restates the selective-review framing, says the coaching staff will review and respond.
- On success: redirect back to `/apply/?submitted=1` showing a branded confirmation state instead of the form.

### 5.3 Anti-abuse

Nonce, honeypot field, Cloudflare Turnstile (keys from `.env` as in `gv-request-form.php`), server-side re-validation of every field.

## 6. Error handling

- Server-side validation failures re-render the form with a top-level error summary and per-field messages, preserving entered values (except honeypot).
- Turnstile/nonce failure → generic "verification failed, please try again" (no detail leakage).
- `wp_mail()` failure does **not** lose the application — the CPT entry is saved first; email failures are logged via `error_log`.

## 7. Testing

- **CLI suite** `build/mu-plugins/tests/test-gv-elite-application.php` (framework-free, matching existing test files): field definitions/required map, validation rules (checkbox max-3, URL/email formats, DOB→age derivation, required confirmations), markup builders, email body builders.
- Run alongside existing suites; existing tests must stay green.
- Manual verification post-deploy: submit a live test application end-to-end (form → CPT entry → both emails), and confirm non-Elite CTAs still open the consultation wizard.

## 8. Deployment & docs

- Golden Workflow: edit in `build/`, SCP to host, `wp eval-file` page-sync script for `/apply/` + updated pages, mu-plugin copied to `mu-plugins/`, clear cache.
- Wiki sync: `forms-and-emails.md` (new form section), `pages.md` (new `/apply/` page ID), `architecture.md` (new mu-plugin), `client-status.md`, `log.md` entry.
