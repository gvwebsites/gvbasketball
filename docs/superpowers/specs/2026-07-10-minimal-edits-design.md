# Minimal Edits Round — GV Elite Performance Copy + GV Elite Academy Teaser (Design Spec)

**Date:** 2026-07-10
**Status:** ACTIVE — supersedes this round's two earlier specs, which are now DEFERRED:
- `2026-07-10-gv-elite-performance-application-design.md` (application form, `/apply/`, mu-plugin)
- `2026-07-10-gv-elite-academy-teaser-design.md` (full Academy page, priority list, founding partners)

**Why:** Rico agreed with the client (2026-07-10) that anything constituting a **new feature** is deferred; only **straightforward copy/content edits** ship now. New-feature requirements stay archived in `revisions/2026-07-10-creative-direction-client.md` and `revisions/2026-07-10-elite-academy-client.md` for a future round.

---

## 1. Goal

Ship the client-approved copy changes and a minimal "Coming Soon" GV Elite Academy presence — using only existing site machinery. **No new forms, no new mu-plugins, no new email flows, no new CPTs.**

## 2. Scope decisions (locked with Rico, 2026-07-10)

| Decision | Choice |
|---|---|
| APPLY NOW behavior | Pure label swap — button text becomes **Apply Now** but keeps the existing `data-gv-consultation` attribute, opening the working consultation wizard |
| Academy presence | Nav tab **GV Elite Academy** + a minimal one-screen static page at `/elite-academy/` (hero copy + COMING 2027). No forms, no video build, no partner logos |
| Everything else from the client's two messages | Deferred (see §6) |

## 3. Edit 1 — GV Elite Performance copy (client-final, verbatim)

Same copy targets as the deferred application spec §3, minus any `/apply/` linkage:

### 3.1 Cards — `build/pages/home.html:81-87` and `build/pages/training-programs.html:43-53`
- Name: **GV Elite Performance**. Kicker: **Application Required · Limited Enrollment**.
- home.html body: "The complete performance system for aspiring elite athletes — court training, strength & conditioning, recovery, and nutrition."
- training-programs.html body: lead line "The complete performance system for aspiring elite athletes." + bullets **Court Training / Strength & Conditioning / Recovery / Nutrition**.
- CTA on both: `<a class="gv-btn gv-btn--primary" href="#" role="button" data-gv-consultation>Apply Now</a>` — **only the label changes**; attribute, href, and classes stay identical to today.

### 3.2 Detail section — `build/pages/training-programs.html:118-146`
- Title **GV Elite Performance**; "Who It's For" drops "aqua training" and describes the four-pillar system with the selective framing (application-based; character, coachability, commitment, potential).
- "What's Included": Court Training (offensive & defensive skill development) / Strength & Conditioning / Recovery, mobility & injury prevention / Nutrition guidance / Structured, periodized progression.
- "Scheduling" → **Admission**: "Application required. Limited enrollment. Start with a consultation."
- CTA: **Apply Now** (same `data-gv-consultation` mechanism).
- Line ~154: "Private training is scheduled by appointment; GV Elite Performance is by application."

### 3.3 Sweep
- `build/scripts/setup-latepoint.php:50`: service → 'GV Elite Performance', description "The complete performance system: court training, strength & conditioning, recovery, and nutrition. Application required." 
- `grep -rni "aqua" build/` must return nothing afterward.

## 4. Edit 2 — GV Elite Academy teaser (nav tab + one-screen page)

- New static page **`/elite-academy/`** (title: *GV Elite Academy*), source `build/pages/elite-academy.html`, deployed with the standard `gv_ensure_page()` + `gv_set_page_html()` scripts.
- **One screen, no scroll-depth content, no forms.** Dark/cinematic full-bleed section reusing an existing no-people b-roll photo (per the hero-background convention) with a heavy navy overlay and gold `#C9A24B` accent:
  - Eyebrow: **COMING 2027**
  - Title: **GV ELITE ACADEMY**
  - Statement: *"Develop Better People. Not Just Better Basketball Players."*
  - Subline: "The Philippines' future premier residential basketball academy for student-athletes committed to excellence in character, leadership, and performance."
  - Single quiet line (no button): **"Full details and applications opening soon."**
  - Closing quote, small type: *"The next generation is already here. The question is who will help shape it."*
- **Navigation:** add **GV Elite Academy** to the primary nav as the last item before Contact (via the existing `build-menu.php` deploy pattern). Add the link to the footer Explore list (`build/templates/footer.html`).

## 4b. Edit 3 — Remove pricing mentions from copy

Client asked to "remove price hidden on GV Basketball" — confirmed with Rico as: stop discussing pricing in site copy altogether (prices are already CSS-hidden in the booking wizard; that stays as-is).

- `build/pages/faq.html:50`: remove the entire "How much does training cost?" accordion item (question + answer with "we share it during your consultation… no hidden fees").
- `build/scripts/build-functional.php:74`: booking blurb — drop the pricing clause; ends "…Coach Gino's team will follow up to confirm." instead of "…to confirm — pricing is shared during your consultation."
- Sweep: `grep -rni "pricing\|no hidden fees" build/pages/ build/scripts/` afterward and clear any remaining copy mentions of the same kind (do not touch the LatePoint price-hiding CSS in `gv-members.css:602-613` or the `price_min/price_max` service config — those are functional, not copy).

## 5. Constraints

- No new mu-plugins, shortcodes, CPTs, or emails. No JS beyond what pages already carry.
- Only the Elite Performance CTAs change their label; every other Book a Consultation button stays byte-identical.
- Copy quoted above is client-final — apply verbatim (bullets never abbreviated; "aqua training" removed everywhere).
- Existing CLI test suites must stay green (regression only; no new test files needed since no new logic ships).

## 6. Deferred register (future rounds — requirements already archived)

1. GV Elite Performance application form, `/apply/` page, `gv-elite-application.php` (full plan already written: `docs/superpowers/plans/2026-07-10-gv-elite-performance-application.md` — reusable when unlocked).
2. Full GV Elite Academy page (belief/experience/vision sections, hero video, founding partner logos, priority-list capture).
3. Founding Partners initiative and logo display (incl. the accepted-risk logo decision — re-confirm when revived).
4. Results/social-proof page; full nav restructure (Programs / The Process / About Coach Gino / Results / Apply).
5. Site-wide photography swap + Coach Gino visibility (blocked on client assets), emotional "less text" copy pass, cinematic design language rollout.

## 7. Testing & verification

- Local: `grep` checks (no "aqua"; no pricing-copy mentions per §4b; consultation-button counts unchanged except labels; `data-gv-consultation` count on Elite CTAs preserved); full existing CLI suite run.
- Live: home + training-programs show new copy; **Apply Now buttons open the consultation wizard**; other program buttons unaffected; FAQ no longer lists the cost question; booking blurb ends at "…follow up to confirm."; `/elite-academy/` renders one clean screen; nav tab + footer link present on desktop/mobile; cache cleared.

## 8. Deployment & docs

- Golden Workflow per `wiki/deployment-workflows.md`: SCP updated `home.html`, `training-programs.html`, new `elite-academy.html`, `footer.html`; `wp eval-file` page-apply + menu script; purge cache.
- Wiki sync: `pages.md` (new page ID), `client-status.md` (shipped edits + explicit deferred register so Coach Gino sees what's parked), `log.md` entry. `forms-and-emails.md` and `architecture.md` unchanged (nothing new shipped there).
