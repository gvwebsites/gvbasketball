# Members + Consultation — Production Baseline (2026-07-10)

Captured before any mutation for the members/self-service-consultation implementation.

## Remote backup directory

`gvweb:~/gv-backup-members-20260710/` (also copied locally to `resources/backups/members-consultation-20260710/`)

Contents: `post-2983.json` (Member Booking page), `post-3002.json` (header), `post-2991.json` (footer), and TSV dumps of `wp_latepoint_services`, `wp_latepoint_service_meta`, `wp_latepoint_settings`, `wp_latepoint_customers`, `wp_latepoint_bookings`, `wp_latepoint_booking_meta`, `wp_latepoint_orders`, `wp_latepoint_order_items`.

## Baseline facts

- WordPress core: **6.8.5**
- LatePoint: **5.6.6** (FREE)
- Page 2983: `Member Booking`, slug `booking`, status `publish`
- Player Consultation service: id **1**, duration **45**, timeblock_interval **60** (to become 180), override_default_booking_status **(empty)** (to become pending)
- `wp_latepoint_customers`: **0 rows**
- `wp_latepoint_bookings`: **0 rows**
- `wp_latepoint_service_meta`, bookings, booking_meta, orders, order_items: **all empty**

## Hook reconfirmation (LatePoint 5.6.6)

- `latepoint_booking_steps_contact_after` — `lib/views/steps/partials/_contact_form.php:46`
- `latepoint_process_step` — fired in `lib/controllers/steps_controller.php:364`; core handler `OsStepsHelper::process_step` at priority 10 (`lib/helpers/steps_helper.php:280`)
- `latepoint_booking_created` — `lib/models/order_intent_model.php:360` (and `steps_helper.php:2365,2386`, `orders_controller.php:391`); native notifications via `OsProcessJobsHelper::handle_booking_created` at priority **12** (`lib/helpers/process_jobs_helper.php:279`)
- `latepoint_booking_updated` — `lib/models/booking_model.php:1209` (plus admin/cabinet controllers); native handler `OsProcessJobsHelper::handle_booking_updated` at priority **12**

All locations match the approved design's assumptions.
