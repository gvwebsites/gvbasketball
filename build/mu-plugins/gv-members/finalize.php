<?php
/**
 * GV Members — Secure Exact-Time Finalization for Coach Gino
 */

defined('ABSPATH') || exit;

add_action('template_redirect', 'gv_members_finalize_route_handler');

/**
 * Capture /members/finalize/ virtual path and render finalization screen.
 */
function gv_members_finalize_route_handler() {
    $path = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    $path = rtrim($path, '/');
    if ($path === '/members/finalize' && isset($_GET['gv_finalize_consultation'])) {
        gv_members_handle_finalize_request();
        exit;
    }
}

/**
 * Process finalization logic.
 */
function gv_members_handle_finalize_request() {
    $booking_code = sanitize_text_field($_GET['booking_code'] ?? ($_POST['booking_code'] ?? ''));
    $token = sanitize_text_field($_GET['token'] ?? ($_POST['token'] ?? ''));
    
    if (empty($booking_code) || empty($token)) {
        gv_members_finalize_render_error();
        return;
    }
    
    if (!class_exists('OsBookingModel')) {
        gv_members_finalize_render_error();
        return;
    }
    
    $bookings = (new OsBookingModel())->where(['booking_code' => $booking_code])->set_limit(1)->get_results_as_models();
    $booking = (!empty($bookings) && is_array($bookings)) ? $bookings[0] : null;
    
    if (!$booking || (method_exists($booking, 'is_new_record') ? $booking->is_new_record() : false)) {
        gv_members_finalize_render_error();
        return;
    }
    
    if (!class_exists('OsServiceModel')) {
        gv_members_finalize_render_error();
        return;
    }
    $service = new OsServiceModel($booking->service_id);
    if (!$service || (method_exists($service, 'is_new_record') ? $service->is_new_record() : false) || $service->name !== 'Player Consultation') {
        gv_members_finalize_render_error();
        return;
    }
    
    $token_hash = gv_members_token_hash($token);
    $stored_hash = $booking->get_meta_by_key('gv_finalize_token_hash');
    $expires_at = $booking->get_meta_by_key('gv_finalize_token_expires_at');
    $used_at = $booking->get_meta_by_key('gv_finalize_token_used_at');
    
    if (!gv_members_secure_equals($stored_hash, $token_hash)) {
        gv_members_finalize_render_error();
        return;
    }
    
    if (gv_members_token_expired($expires_at)) {
        gv_members_finalize_render_error();
        return;
    }
    
    if ($booking->status === 'approved') {
        gv_members_finalize_render_confirmed($booking);
        return;
    }
    
    if ($booking->status !== 'pending') {
        gv_members_finalize_render_error();
        return;
    }
    
    $booking_email_data = gv_members_booking_email_data($booking);
    $post_error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!wp_verify_nonce($_POST['gv_finalize_nonce'] ?? '', 'gv_finalize_action')) {
            $post_error = "Security check failed. Please refresh and try again.";
        } else {
            $selected_time = isset($_POST['selected_time']) ? (int) $_POST['selected_time'] : 0;
            $candidates = gv_members_candidate_start_times();
            if (!in_array($selected_time, $candidates, true)) {
                $post_error = "Invalid time selected.";
            } else {
                global $wpdb;
                $wpdb->query('START TRANSACTION');
                try {
                    $row = $wpdb->get_row($wpdb->prepare(
                        "SELECT id, status FROM {$wpdb->prefix}latepoint_bookings WHERE id = %d FOR UPDATE",
                        $booking->id
                    ));
                    if (!$row) {
                        throw new Exception("Booking row lock failed.");
                    }
                    
                    $booking = new OsBookingModel($booking->id);
                    $fresh_used_at = $booking->get_meta_by_key('gv_finalize_token_used_at');
                    if (!empty($fresh_used_at) || $booking->status !== 'pending') {
                        throw new Exception("Booking has already been finalized.");
                    }
                    
                    if (!gv_members_check_time_available($booking, $selected_time)) {
                        throw new Exception("The selected slot is no longer available.");
                    }
                    
                    remove_action('latepoint_booking_updated', ['OsProcessJobsHelper', 'handle_booking_updated'], 12);
                    
                    $dt_start = new DateTime($booking->start_date, new DateTimeZone('Asia/Manila'));
                    $dt_start->setTime(floor($selected_time / 60), $selected_time % 60, 0);
                    $dt_end = clone $dt_start;
                    $dt_end->modify('+45 minutes');
                    
                    $dt_start_utc = clone $dt_start;
                    $dt_start_utc->setTimezone(new DateTimeZone('UTC'));
                    $dt_end_utc = clone $dt_end;
                    $dt_end_utc->setTimezone(new DateTimeZone('UTC'));
                    
                    $update_data = [
                        'start_date' => $dt_start->format('Y-m-d'),
                        'start_time' => $selected_time,
                        'end_date' => $dt_end->format('Y-m-d'),
                        'end_time' => $selected_time + 45,
                        'start_datetime_utc' => $dt_start_utc->format('Y-m-d H:i:s'),
                        'end_datetime_utc' => $dt_end_utc->format('Y-m-d H:i:s'),
                        'status' => 'approved',
                    ];
                    
                    $old_booking = clone $booking;
                    if (!$booking->update_attributes($update_data)) {
                        throw new Exception("Failed to update booking attributes.");
                    }
                    
                    $now_str = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
                    $booking->save_meta_by_key('gv_finalize_token_used_at', $now_str);
                    $booking->save_meta_by_key('gv_finalized_at', $now_str);
                    
                    do_action('latepoint_booking_updated', $booking, $old_booking);
                    
                    add_action('latepoint_booking_updated', ['OsProcessJobsHelper', 'handle_booking_updated'], 12, 2);
                    
                    $wpdb->query('COMMIT');
                    
                    gv_members_finalize_render_success($booking);
                    return;
                } catch (Exception $e) {
                    $wpdb->query('ROLLBACK');
                    add_action('latepoint_booking_updated', ['OsProcessJobsHelper', 'handle_booking_updated'], 12, 2);
                    $post_error = $e->getMessage();
                }
            }
        }
    }
    
    $available_slots = [];
    $candidates = gv_members_candidate_start_times();
    foreach ($candidates as $time_min) {
        if (gv_members_check_time_available($booking, $time_min)) {
            $available_slots[] = $time_min;
        }
    }
    
    gv_members_finalize_render_form($booking, $booking_email_data, $available_slots, $post_error, $token);
}

/**
 * Check if a specific nominal time is genuinely available in LatePoint.
 */
function gv_members_check_time_available($booking, $start_time_minutes) {
    if (!class_exists('OsBookingHelper') || !class_exists('LatePoint\Misc\BookingRequest')) {
        return false;
    }
    $clone = clone $booking;
    $clone->start_time = $start_time_minutes;
    $clone->end_time = $start_time_minutes + 45;
    $clone->buffer_before = 0;
    $clone->buffer_after = 0;
    
    $request = \LatePoint\Misc\BookingRequest::create_from_booking_model($clone);
    return \OsBookingHelper::is_booking_request_available($request, ['exclude_booking_ids' => [(int) $booking->id]]);
}

/**
 * Format minutes of day to 12-hour AM/PM string.
 */
function gv_members_format_minutes($minutes) {
    $hour = floor($minutes / 60);
    $min = $minutes % 60;
    $ampm = ($hour >= 12) ? 'PM' : 'AM';
    $hour_12 = ($hour > 12) ? ($hour - 12) : (($hour === 0) ? 12 : $hour);
    return sprintf('%d:%02d %s', $hour_12, $min, $ampm);
}

function gv_members_finalize_header() {
    $logo = 'https://gvbasketball.com/wp-content/uploads/2026/07/gv-logo-crest.png';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>GV Basketball — Finalize Consultation</title>
        <style>
            body {
                background: #f4f5f7;
                font-family: Arial, Helvetica, sans-serif;
                margin: 0;
                padding: 40px 15px;
                color: #1C1C1E;
            }
            .gv-finalize-container {
                max-width: 600px;
                margin: 0 auto;
            }
            .gv-logo-wrap {
                text-align: center;
                margin-bottom: 24px;
            }
            .gv-finalize-card {
                background: #ffffff;
                border: 1px solid #E6E7E9;
                border-radius: 14px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                overflow: hidden;
                border-top: 4px solid #F47B20;
            }
            .gv-card-header {
                background: #f8f9fa;
                padding: 20px 24px;
                border-bottom: 1px solid #E6E7E9;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .gv-card-header h2 {
                margin: 0;
                font-size: 20px;
                color: #123B78;
                font-weight: 800;
            }
            .gv-card-body {
                padding: 24px;
            }
            .gv-badge {
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: bold;
            }
            .gv-badge-success {
                background: #e6f7ed;
                color: #1a7f37;
            }
            .gv-details-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .gv-details-table th, .gv-details-table td {
                padding: 10px 12px;
                text-align: left;
                border-bottom: 1px solid #f0f0f2;
                font-size: 14px;
            }
            .gv-details-table th {
                background: #f0f4fa;
                color: #123B78;
                width: 35%;
                font-weight: bold;
                vertical-align: top;
            }
            .gv-error-message {
                color: #d9381e;
                font-size: 15px;
                line-height: 1.5;
                margin: 0;
            }
            .gv-error-banner {
                background: #fce8e6;
                color: #c53929;
                padding: 12px 16px;
                border-radius: 6px;
                margin-bottom: 20px;
                font-size: 14px;
                font-weight: bold;
            }
            .gv-success-banner {
                background: #e6f7ed;
                color: #1a7f37;
                padding: 12px 16px;
                border-radius: 6px;
                margin-bottom: 20px;
                font-size: 14px;
            }
            .gv-success-banner p { margin: 0; }
            .gv-instruction {
                font-size: 16px;
                font-weight: bold;
                color: #123B78;
                margin-bottom: 15px;
            }
            .gv-contact-panel {
                background: #f0f4fa;
                border: 1px solid #d9e2f0;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .gv-contact-panel h3 {
                margin-top: 0;
                margin-bottom: 12px;
                font-size: 15px;
                color: #123B78;
            }
            .gv-slot-label:hover {
                border-color: #F47B20 !important;
                background: #fff8f5 !important;
            }
            .gv-slot-label input:checked + span {
                font-weight: bold;
                color: #F47B20;
            }
        </style>
    </head>
    <body>
        <div class="gv-finalize-container">
            <div class="gv-logo-wrap">
                <img src="<?php echo esc_url($logo); ?>" width="80" alt="GV Basketball Logo">
            </div>
    <?php
}

function gv_members_finalize_footer() {
    ?>
        </div>
    </body>
    </html>
    <?php
}

function gv_members_finalize_render_error() {
    gv_members_finalize_header();
    ?>
    <div class="gv-finalize-card gv-error-card">
        <div class="gv-card-header">
            <h2>Unable to Process Request</h2>
        </div>
        <div class="gv-card-body">
            <p class="gv-error-message">Invalid or expired finalization token. Please verify the URL or contact support.</p>
        </div>
    </div>
    <?php
    gv_members_finalize_footer();
}

function gv_members_finalize_render_confirmed($booking) {
    $data = gv_members_booking_email_data($booking);
    $formatted_time = gv_members_format_minutes($booking->start_time);
    gv_members_finalize_header();
    ?>
    <div class="gv-finalize-card gv-confirmed-card">
        <div class="gv-card-header">
            <span class="gv-badge gv-badge-success">Confirmed</span>
            <h2>Consultation Finalized</h2>
        </div>
        <div class="gv-card-body">
            <p class="gv-intro-text" style="margin: 0 0 16px; font-size:15px; color:#6B6F76;">This consultation booking has already been finalized and approved.</p>
            <table class="gv-details-table">
                <tr><th>Player</th><td><?php echo esc_html($data['player_name']); ?></td></tr>
                <tr><th>Parent / Guardian</th><td><?php echo esc_html($data['parent_name']); ?></td></tr>
                <tr><th>Confirmed Day</th><td><?php echo esc_html($data['day']); ?></td></tr>
                <tr><th>Confirmed Time</th><td><strong><?php echo esc_html($formatted_time); ?></strong></td></tr>
                <tr><th>Venue</th><td><?php echo esc_html($data['venue_name']); ?></td></tr>
                <tr><th>Reference Code</th><td><code><?php echo esc_html($booking->booking_code); ?></code></td></tr>
            </table>
            <div class="gv-action-box" style="margin-top:20px; background:#f0f4fa; padding:12px; border-radius:6px; font-size:14px; text-align:center;">
                Status: Confirmed and updated on the member portal.
            </div>
        </div>
    </div>
    <?php
    gv_members_finalize_footer();
}

function gv_members_finalize_render_success($booking) {
    $data = gv_members_booking_email_data($booking);
    $formatted_time = gv_members_format_minutes($booking->start_time);
    gv_members_finalize_header();
    ?>
    <div class="gv-finalize-card gv-success-card">
        <div class="gv-card-header">
            <h2>Booking Updated</h2>
        </div>
        <div class="gv-card-body">
            <div class="gv-success-banner">
                <p><strong>Success!</strong> The booking has been updated to Confirmed.</p>
            </div>
            <p class="gv-instruction">Please contact the parent now to confirm the final schedule.</p>
            
            <div class="gv-contact-panel">
                <h3>Parent Contact Info:</h3>
                <table class="gv-details-table">
                    <tr><th>Parent Name</th><td><?php echo esc_html($data['parent_name']); ?></td></tr>
                    <tr><th>Email</th><td><a href="mailto:<?php echo esc_attr($data['parent_email']); ?>"><?php echo esc_html($data['parent_email']); ?></a></td></tr>
                    <?php if (!empty($data['parent_phone'])): ?>
                        <tr><th>Phone</th><td><?php echo esc_html($data['parent_phone']); ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($data['contact_alt'])): ?>
                        <tr><th>Instagram / Alt</th><td><?php echo esc_html($data['contact_alt']); ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <table class="gv-details-table" style="margin-top:20px;">
                <tr><th>Player</th><td><?php echo esc_html($data['player_name']); ?></td></tr>
                <tr><th>Venue</th><td><?php echo esc_html($data['venue_name']); ?></td></tr>
                <tr><th>Schedule</th><td><?php echo esc_html($data['day']); ?> @ <strong><?php echo esc_html($formatted_time); ?></strong></td></tr>
            </table>
        </div>
    </div>
    <?php
    gv_members_finalize_footer();
}

function gv_members_finalize_render_form($booking, $data, $available_slots, $post_error, $token) {
    gv_members_finalize_header();
    ?>
    <div class="gv-finalize-card">
        <div class="gv-card-header">
            <h2>Finalize Consultation Time</h2>
        </div>
        <div class="gv-card-body">
            <?php if (!empty($post_error)): ?>
                <div class="gv-error-banner">
                    <p><?php echo esc_html($post_error); ?></p>
                </div>
            <?php endif; ?>
            
            <table class="gv-details-table">
                <tr><th>Player</th><td><?php echo esc_html($data['player_name']); ?> (Age <?php echo (int) $data['player_age']; ?>)</td></tr>
                <tr><th>Parent / Guardian</th><td><?php echo esc_html($data['parent_name']); ?></td></tr>
                <tr><th>Email</th><td><?php echo esc_html($data['parent_email']); ?></td></tr>
                <?php if (!empty($data['contact_alt'])): ?>
                    <tr><th>Instagram / Alt</th><td><?php echo esc_html($data['contact_alt']); ?></td></tr>
                <?php endif; ?>
                <tr><th>Training Interest</th><td><?php echo esc_html(gv_members_interest_label($data['training_interest'])); ?></td></tr>
                <tr><th>Venue</th><td><?php echo esc_html($data['venue_name']); ?></td></tr>
                <tr><th>Requested Day</th><td><?php echo esc_html($data['day']); ?></td></tr>
                <?php if (!empty($data['note'])): ?>
                    <tr><th>Note</th><td><?php echo esc_html($data['note']); ?></td></tr>
                <?php endif; ?>
            </table>

            <?php if (empty($available_slots)): ?>
                <div class="gv-warning-banner" style="margin-top:20px;border-left:4px solid #F47B20;background:#fff5eb;padding:15px;border-radius:4px;">
                    <p style="color:#c65e00;margin:0;font-weight:bold;">No 45-minute time remains on this day.</p>
                    <p style="margin:5px 0 0;font-size:14px;color:#6B6F76;">Please contact the parent personally to agree on another day.</p>
                </div>
            <?php else: ?>
                <form method="POST" action="" class="gv-finalize-form" style="margin-top:20px;">
                    <?php wp_nonce_field('gv_finalize_action', 'gv_finalize_nonce'); ?>
                    <input type="hidden" name="booking_code" value="<?php echo esc_attr($booking->booking_code); ?>">
                    <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                    
                    <label style="font-weight:bold;display:block;margin-bottom:10px;color:#123B78;">Select Confirmed Start Time (45-Minute Session):</label>
                    <div class="gv-slots-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));gap:10px;margin-bottom:20px;">
                        <?php foreach ($available_slots as $slot_min): ?>
                            <label class="gv-slot-label" style="display:block;border:1px solid #E6E7E9;border-radius:6px;padding:10px;text-align:center;cursor:pointer;background:#ffffff;">
                                <input type="radio" name="selected_time" value="<?php echo esc_attr($slot_min); ?>" required style="margin-right:6px;">
                                <span><?php echo esc_html(gv_members_format_minutes($slot_min)); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="gv-submit-btn" style="background:#F47B20;color:#ffffff;border:none;padding:13px 26px;border-radius:8px;font-weight:bold;font-size:15px;cursor:pointer;display:block;width:100%;">Confirm Booking</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
    gv_members_finalize_footer();
}
