# GV Elite Academy — Teaser Page + Priority List (Design Spec)

**Date:** 2026-07-10
**Source requirements:** `revisions/2026-07-10-elite-academy-client.md`
**Sequencing:** Phase 2 — executes **after** the GV Elite Performance plan (`docs/superpowers/plans/2026-07-10-gv-elite-performance-application.md`) is deployed. That plan is unamended; the client's second message re-confirmed its copy verbatim.

---

## 1. Goal

A dedicated **GV Elite Academy** page and nav tab that previews an institution launching 2027 — deliberately *not* styled like another training program. Cinematic, minimal, image/video-led. Primary conversion: **Join the Priority List** (a real lead-capture pipeline for parents, athletes, and potential partners).

## 2. Scope decisions (locked with Rico, 2026-07-10)

| Decision | Choice |
|---|---|
| Sequencing | Ship Elite Performance plan first; Academy is a separate spec/plan |
| Navigation | Add **GV Elite Academy** to the existing primary nav only — no full 8-item restructure this round (Apply stays footer-linked per phase 1) |
| Priority List | Minimal capture form: Name + Email + "I am a" (Parent / Athlete / Potential Partner), stored in wp-admin + branded notification to Coach Gino |
| Partner marks | **Actual brand logos**, labeled clearly as "Proposed Founding Partners." ⚠️ Trademark/false-endorsement risk was flagged and **accepted by Rico/client**. Mitigation: prominent "Proposed" framing and a "partnership opportunities available" disclaimer; revisit if any brand objects. |

**Out of scope:** standalone Academy website/domain, the full nav restructure (Programs / The Process / About Coach Gino / Results), the Results social-proof page (content assets pending from client), site-wide photography swap (assets pending), Academy application form (applications open later — the priority list is the only capture).

**Open dependencies (do not block build):**
- Client's concept reference image — not yet received; request again before visual polish.
- Cinematic hero video — until dedicated Academy footage exists, reuse the existing homepage training video with a heavy dark overlay so the page reads cinematic, not recycled.
- Real partner logo files — sourced as clean SVG/PNG wordmark-style logos, rendered monochrome (see §4.6).

## 3. Page & navigation

- New page **`/elite-academy/`** (title: *GV Elite Academy*), source `build/pages/elite-academy.html`, deployed like other pages via `gv_set_page_html()`.
- Primary nav: insert **GV Elite Academy** after the existing programs/gallery items (exact position: last item before Contact), via the site's menu deploy script (`build/scripts/build-menu.php` pattern). Footer Explore list gets the link too.
- The page uses the existing header/footer chrome (client wants two brands on one site for now), but its body sections carry a distinct, darker, more cinematic treatment so it doesn't read as "another program page."

## 4. Page sections (copy is client-final — apply verbatim where quoted)

Design language throughout: near-black/deep-navy full-bleed sections, generous vertical whitespace (roughly 2× the spacing of GV Basketball pages), large serif-or-display headlines, one supporting sentence per section, no paragraphs over ~40 words. Gold accent `#C9A24B` (already in the design system) is the Academy's accent — not orange.

1. **Hero (full viewport, video background)** — background video with dark overlay; content: eyebrow **COMING 2027**, title **GV ELITE ACADEMY**, statement *"Develop Better People. Not Just Better Basketball Players."*, subline "The Philippines' future premier residential basketball academy for student-athletes committed to excellence in character, leadership, and performance.", gold CTA **Join the Priority List** (smooth-scrolls to §4.8).
2. **Our Belief** — centered statement block: "Basketball is our classroom. Character is our curriculum." then the two supporting lines ("We believe success isn't built by talent alone…" / "Every drill. Every conversation. Every decision. Shapes the athlete.") set small and widely spaced.
3. **The Experience** — 10-item icon grid (line-style SVG icons matching the site's existing icon language — no emoji in production): Elite Basketball Training, Strength & Conditioning, Nutrition Education, Sports Psychology, NBA Film Study, Coach's Table, Recovery, Performance Reviews, Leadership Development, Residential Academy Experience. Icon + label only, no descriptions.
4. **Who It's For** — three short lines: "Ages 12–18." / "Student-athletes committed to becoming better players and better people." / "Limited annual enrollment. Application required."
5. **The Vision** — horizontal milestone row: Year One — 30 student-athletes / Year Two — 60 / Year Three — 100+; then the long-term line: "A dedicated GV Elite Academy campus recognized as one of the premier basketball development institutions in Asia."
6. **Founding Partners** — intro copy verbatim ("Building the next generation requires more than great coaching…"), then a logo row of the 8 brands (Nike, Mercedes-Benz, HSBC, Ayala Land Premier, Century Tuna, Vita Coco, Therabody, ZAMST) rendered **monochrome/50% opacity** under the heading **Proposed Founding Partners** with the disclaimer line "Partnership opportunities available." Button: **Become a Founding Partner** → opens the priority-list form pre-selected to "Potential Partner".
7. **Apply teaser** — "Applications Opening Soon." / "Join the priority list to receive early access when applications officially open." Button: **Join the Priority List**.
8. **Priority List form** (in-page section, `id="priority-list"`) — Name*, Email*, I am a* (Parent / Athlete / Potential Partner). Nonce + honeypot + Turnstile.
9. **Closing quote** (final section, replaces any contact CTA): *"The next generation is already here. The question is who will help shape it."* — no button after it.

## 5. Priority List plumbing — mu-plugin `gv-elite-academy.php` (prefix `gv_ak_`)

Same architecture family as `gv-elite-application.php`:

- Shortcode `[gv_priority_list]` renders the form; POST handled on `template_redirect` for `/elite-academy/`; redirect to `?joined=1` on success (confirmation state in place of the form).
- Validation: name required, email required + format, role required ∈ {parent, athlete, partner}. `?role=partner` query/prefill support for the Founding Partner button.
- **Storage:** private CPT `gv_priority_lead` — wp-admin menu **Priority List**, columns: Name, Email, Role, Date. Stored before any email.
- **Emails:** coach alert to `GV_RF_RECIPIENT` via `gv_rf_email_shell()` (subject distinguishes role — a partner lead should stand out), plus a short branded auto-reply to the lead ("You're on the list — you'll be first to know when applications open").
- Reuses `gv_rf_verify_turnstile()`; same include-order rule (call `gv_rf_*` only inside hooks).
- **Tests:** `build/mu-plugins/tests/test-gv-elite-academy.php`, framework-free CLI, covering field model, validation, role prefill, email builders.

## 6. Error handling

Same contract as the application form: server-side failures re-render with a summary + per-field errors preserving values; honeypot hits fake success; CPT saved before email; `wp_mail()` failures logged, never user-facing.

## 7. Testing & verification

- CLI suite green + all existing suites green.
- Live checklist: nav tab present on desktop/mobile; hero video plays muted/looped with readable overlay; smooth-scroll CTAs; partner logos monochrome with "Proposed" heading visible; full test lead per role lands in wp-admin + both emails; no-JS submission works; page does not visually read like the Training Programs template.

## 8. Deployment & docs

- Golden Workflow (edit `build/` → SCP → `wp eval-file` → cache clear). New deploy script `build/scripts/deploy-elite-academy.php` (ensure page + set HTML); menu update via the `build-menu.php` pattern; partner logo assets uploaded to the media library and referenced by URL like other page images.
- Wiki sync: `pages.md` (new page ID), `architecture.md` (new mu-plugin), `forms-and-emails.md` (priority list section), `client-status.md` (highlight + note the accepted logo-risk decision), `log.md`.
