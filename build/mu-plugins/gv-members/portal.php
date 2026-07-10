<?php
/**
 * GV Members — Portal Interface
 */
defined('ABSPATH') || exit;

// Handle ICS download
add_action('template_redirect', 'gv_members_download_ics_handler');

/**
 * Handle ICS calendar event download for verified owners only
 */
function gv_members_download_ics_handler() {
    if (empty($_GET['gv_download_ics']) || empty($_GET['booking_code'])) {
        return;
    }

    nocache_headers();

    if (!class_exists('OsAuthHelper') || !class_exists('OsBookingModel')) {
        wp_die('Service is currently unavailable.', 503);
    }

    $customer = OsAuthHelper::get_logged_in_customer();
    if (!$customer) {
        wp_die('Unauthorized.', 401);
    }

    $booking_code = sanitize_text_field($_GET['booking_code']);
    $booking = (new OsBookingModel())->where(['booking_code' => $booking_code])->set_limit(1)->get_results_as_models();

    if (empty($booking) || is_array($booking)) {
        wp_die('Booking not found.', 404);
    }

    // Ownership check: must match customer ID
    if ($booking->customer_id != $customer->id) {
        wp_die('Access denied.', 403);
    }

    // Status check: must be approved
    if ($booking->status !== 'approved') {
        wp_die('Session is not confirmed.', 400);
    }

    // Load venue/location details
    $location_name = 'Unknown Location';
    if (!empty($booking->location_id) && class_exists('OsLocationModel')) {
        $loc = new OsLocationModel($booking->location_id);
        if ($loc && !$loc->is_new_record() && !empty($loc->name)) {
            $location_name = $loc->name;
        }
    }

    // Load service name
    $service_name = 'Player Consultation';
    if (!empty($booking->service_id) && class_exists('OsServiceModel')) {
        $srv = new OsServiceModel($booking->service_id);
        if ($srv && !$srv->is_new_record() && !empty($srv->name)) {
            $service_name = $srv->name;
        }
    }

    // Calculate Manila times and convert to UTC for ICS standard
    $manila_tz = new DateTimeZone('Asia/Manila');
    $time_str = gv_members_format_time_int($booking->start_time);
    $start_dt = new DateTime($booking->start_date . ' ' . $time_str, $manila_tz);

    // Duration fallback
    $duration_minutes = 45;
    if (!empty($booking->service_id) && class_exists('OsServiceModel')) {
        $srv = new OsServiceModel($booking->service_id);
        if ($srv && !$srv->is_new_record() && isset($srv->duration)) {
            $duration_minutes = (int) $srv->duration;
        }
    }

    $end_dt = clone $start_dt;
    $end_dt->modify('+' . $duration_minutes . ' minutes');

    // Convert timezone to UTC for ICS
    $start_dt->setTimezone(new DateTimeZone('UTC'));
    $end_dt->setTimezone(new DateTimeZone('UTC'));

    $ics_start = $start_dt->format('Ymd\THis\Z');
    $ics_end = $end_dt->format('Ymd\THis\Z');
    $ics_stamp = gmdate('Ymd\THis\Z');

    $player_name = $booking->get_meta_by_key('gv_player_name') ?: $customer->first_name . ' ' . $customer->last_name;

    $summary = "GV Basketball: {$service_name} ({$player_name})";
    $description = "Confirmed session with GV Basketball. Reference: {$booking->booking_code}.";

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="gv-session-' . $booking->booking_code . '.ics"');

    echo "BEGIN:VCALENDAR\r\n";
    echo "VERSION:2.0\r\n";
    echo "PRODID:-//GV Basketball//Member Portal//EN\r\n";
    echo "CALSCALE:GREGORIAN\r\n";
    echo "BEGIN:VEVENT\r\n";
    echo "UID:gv-booking-{$booking->booking_code}@gvbasketball.com\r\n";
    echo "DTSTAMP:{$ics_stamp}\r\n";
    echo "DTSTART:{$ics_start}\r\n";
    echo "DTEND:{$ics_end}\r\n";
    echo "SUMMARY:" . addcslashes($summary, ",\\;") . "\r\n";
    echo "DESCRIPTION:" . addcslashes($description, ",\\;") . "\r\n";
    echo "LOCATION:" . addcslashes($location_name, ",\\;") . "\r\n";
    echo "END:VEVENT\r\n";
    echo "END:VCALENDAR\r\n";
    exit;
}

/**
 * Helper to convert minutes integer to military string format (HH:MM)
 */
function gv_members_format_time_int($time_int) {
    $hours = floor($time_int / 60);
    $minutes = $time_int % 60;
    return sprintf('%02d:%02d', $hours, $minutes);
}

/**
 * Handle POST profile update submission
 */
function gv_members_handle_profile_update($customer) {
    if (empty($_POST['gv_profile_nonce']) || !wp_verify_nonce($_POST['gv_profile_nonce'], 'gv_update_profile')) {
        wp_die('Security check failed.', 403);
    }

    $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

    $customer->first_name = $first_name;
    $customer->last_name = $last_name;
    $customer->phone = $phone;

    if (method_exists($customer, 'update_attributes')) {
        $customer->update_attributes([
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'phone'      => $phone,
        ]);
    } else {
        $customer->save();
    }

    wp_safe_redirect(add_query_arg('profile_updated', '1', home_url('/members/')));
    exit;
}

/**
 * Render main Portal content
 */
function gv_members_portal_render() {
    nocache_headers();

    $customer = null;
    if (class_exists('OsAuthHelper')) {
        $customer = OsAuthHelper::get_logged_in_customer();
    }

    // Handle profile updates
    if ($customer && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'gv_update_profile') {
        gv_members_handle_profile_update($customer);
    }

    ob_start();
    ?>
    <div class="gv-members-portal-w">
        <?php if ($customer): ?>
            <?php gv_members_portal_logged_in_view($customer); ?>
        <?php else: ?>
            <?php gv_members_portal_logged_out_view(); ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render logged out OTP signup/login forms
 */
function gv_members_portal_logged_out_view() {
    ?>
    <div class="gv-members-auth-card">
        <div class="gv-members-auth-header">
            <h2>GV Members</h2>
            <p>Enter your email to sign in or create an account</p>
        </div>
        
        <!-- Request OTP Form -->
        <form id="gv-otp-request-form" class="gv-otp-form" method="POST">
            <div id="gv-request-status" class="gv-status" role="status" aria-live="polite"></div>
            <div id="gv-request-error" class="gv-error" role="alert" aria-live="assertive"></div>
            
            <div class="gv-field-wrap">
                <label for="gv-otp-email">Email Address</label>
                <input type="email" id="gv-otp-email" name="email" required placeholder="name@example.com" autocomplete="email">
            </div>
            
            <button type="submit" class="gv-btn gv-submit-btn">Send Code</button>
        </form>

        <!-- Verify OTP Form (Hidden Initially) -->
        <form id="gv-otp-verify-form" class="gv-otp-form" method="POST" style="display:none;">
            <div id="gv-verify-status" class="gv-status" role="status" aria-live="polite"></div>
            <div id="gv-verify-error" class="gv-error" role="alert" aria-live="assertive"></div>
            
            <p class="gv-otp-intro">We sent a 6-digit verification code to <strong id="gv-verify-target-email"></strong>.</p>
            
            <div class="gv-field-wrap">
                <label>Verification Code</label>
                <div class="gv-otp-digits-wrap">
                    <input type="text" class="gv-otp-digit" pattern="[0-9]*" inputmode="numeric" maxlength="1" aria-label="Digit 1" required>
                    <input type="text" class="gv-otp-digit" pattern="[0-9]*" inputmode="numeric" maxlength="1" aria-label="Digit 2" required>
                    <input type="text" class="gv-otp-digit" pattern="[0-9]*" inputmode="numeric" maxlength="1" aria-label="Digit 3" required>
                    <input type="text" class="gv-otp-digit" pattern="[0-9]*" inputmode="numeric" maxlength="1" aria-label="Digit 4" required>
                    <input type="text" class="gv-otp-digit" pattern="[0-9]*" inputmode="numeric" maxlength="1" aria-label="Digit 5" required>
                    <input type="text" class="gv-otp-digit" pattern="[0-9]*" inputmode="numeric" maxlength="1" aria-label="Digit 6" required>
                </div>
            </div>
            <input type="hidden" id="gv-otp-code" name="otp" value="">
            
            <div class="gv-otp-actions">
                <button type="submit" class="gv-btn gv-submit-btn" id="gv-btn-verify">Verify Code</button>
                <button type="button" class="gv-btn gv-btn-secondary" id="gv-btn-resend" style="margin-top: 10px; min-height: 44px; display: block; width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; background: #FFF; font-weight: bold; cursor: pointer;" disabled>Resend Code</button>
                <button type="button" class="gv-btn gv-btn-link" id="gv-btn-back" style="margin-top: 10px; display: block; width: 100%; text-align: center; color: var(--gv-brand-dark-gray); background: none; border: none; cursor: pointer; text-decoration: underline;">Change Email</button>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Render logged in portal dashboard
 */
function gv_members_portal_logged_in_view($customer) {
    // Query owned bookings
    $bookings = [];
    if (class_exists('OsBookingModel')) {
        $bookings = (new OsBookingModel())->where(['customer_id' => $customer->id])->get_results_as_models();
        if (!is_array($bookings)) {
            $bookings = $bookings ? [$bookings] : [];
        }
    }

    // Expose unique players list JSON for JS
    $players = [];
    foreach ($bookings as $b) {
        $p_name = $b->get_meta_by_key('gv_player_name');
        $p_age = $b->get_meta_by_key('gv_player_age');
        if (!empty($p_name) && !empty($p_age)) {
            $key = strtolower(trim($p_name)) . '|' . $p_age;
            $players[$key] = [
                'name' => trim($p_name),
                'age'  => (int) $p_age,
            ];
        }
    }
    $players = array_values($players);
    echo '<script id="gv-prior-players-json" type="application/json">' . wp_json_encode($players) . '</script>';

    // Filter Player Consultation bookings for the Requests tab
    $consult_service_id = 0;
    if (class_exists('OsServiceModel')) {
        $service = (new OsServiceModel())->where(['name' => 'Player Consultation'])->set_limit(1)->get_results_as_models();
        if ($service && !is_array($service) && !$service->is_new_record()) {
            $consult_service_id = $service->id;
        }
    }

    $requests = [];
    foreach ($bookings as $b) {
        if ($b->service_id == $consult_service_id && ($b->status === 'pending' || $b->status === 'approved')) {
            $requests[] = $b;
        }
    }
    // Sort requests newest first
    usort($requests, function($a, $b) {
        return (int)$b->id - (int)$a->id;
    });

    // Split approved bookings into upcoming/past for Confirmed Sessions tab
    $manila_tz = new DateTimeZone('Asia/Manila');
    $now = new DateTime('now', $manila_tz);

    $upcoming_sessions = [];
    $past_sessions = [];

    foreach ($bookings as $b) {
        if ($b->status !== 'approved') {
            continue;
        }

        $time_str = gv_members_format_time_int($b->start_time);
        $b_dt = new DateTime($b->start_date . ' ' . $time_str, $manila_tz);

        $session_item = [
            'booking' => $b,
            'datetime' => $b_dt
        ];

        if ($b_dt >= $now) {
            $upcoming_sessions[] = $session_item;
        } else {
            $past_sessions[] = $session_item;
        }
    }

    // Sort upcoming ascending (soonest first)
    usort($upcoming_sessions, function($a, $b) {
        return $a['datetime']->getTimestamp() - $b['datetime']->getTimestamp();
    });

    // Sort past descending (most recent first)
    usort($past_sessions, function($a, $b) {
        return $b['datetime']->getTimestamp() - $a['datetime']->getTimestamp();
    });
    ?>
    <div class="gv-portal-container">
        <!-- Dashboard Header -->
        <div class="gv-portal-header">
            <div class="gv-portal-header-top" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <h2 style="margin: 0; color: #FFF; font-weight: 800;">Training Journal</h2>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span class="gv-user-badge" style="background: rgba(255,255,255,0.15); padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;"><?php echo esc_html($customer->email); ?></span>
                    <button class="gv-btn gv-new-request-btn" style="background: var(--gv-brand-orange); border: none; color: #FFF; font-weight: bold; padding: 8px 16px; border-radius: 6px; cursor: pointer; min-height: 38px;">New Request</button>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="gv-portal-nav" role="tablist">
            <button class="gv-portal-tab active" role="tab" aria-selected="true" aria-controls="gv-tab-requests" id="tab-requests">Requests</button>
            <button class="gv-portal-tab" role="tab" aria-selected="false" aria-controls="gv-tab-sessions" id="tab-sessions">Confirmed Sessions</button>
            <button class="gv-portal-tab" role="tab" aria-selected="false" aria-controls="gv-tab-profile" id="tab-profile">Profile</button>
        </div>

        <!-- Tab 1: Requests -->
        <div class="gv-portal-tab-content active" id="gv-tab-requests" role="tabpanel" aria-labelledby="tab-requests">
            <?php if (empty($requests)): ?>
                <div class="gv-empty-state" style="text-align: center; padding: 40px 20px; color: var(--gv-brand-dark-gray);">
                    <p style="font-size: 16px; margin-bottom: 20px;">No consultation requests found.</p>
                    <button class="gv-btn gv-new-request-btn" style="background: var(--gv-brand-orange); color: #FFF; border: none; font-weight: bold; padding: 12px 24px; border-radius: 8px; cursor: pointer; min-height: 44px;">Book a Consultation</button>
                </div>
            <?php else: ?>
                <div class="gv-timeline">
                    <?php foreach ($requests as $b):
                        $status_label = gv_members_status_label($b->status);
                        $status_class = ($b->status === 'approved') ? 'gv-status-confirmed' : 'gv-status-submitted';
                        
                        $player_name = $b->get_meta_by_key('gv_player_name');
                        $player_age = $b->get_meta_by_key('gv_player_age');
                        $interest_key = $b->get_meta_by_key('gv_training_interest');
                        $interest_label = gv_members_interest_label($interest_key);
                        $note = $b->get_meta_by_key('gv_note');
                        
                        $venue_name = 'Unknown Venue';
                        if (!empty($b->location_id) && class_exists('OsLocationModel')) {
                            $loc = new OsLocationModel($b->location_id);
                            if ($loc && !$loc->is_new_record()) {
                                $venue_name = $loc->name;
                            }
                        }

                        $agent_name = 'Coach Gino';
                        if (!empty($b->agent_id) && class_exists('OsAgentModel')) {
                            $agent = new OsAgentModel($b->agent_id);
                            if ($agent && !$agent->is_new_record()) {
                                $agent_name = $agent->first_name . ' ' . $agent->last_name;
                            }
                        }
                        
                        $time_str = gv_members_format_time_int($b->start_time);
                        $date_obj = new DateTime($b->start_date . ' ' . $time_str, $manila_tz);
                        ?>
                        <div class="gv-timeline-item">
                            <div class="gv-timeline-node"></div>
                            <div class="gv-timeline-content">
                                <div class="gv-card-title-row">
                                    <h4 style="margin: 0; color: var(--gv-brand-navy); font-weight: 800; font-size: 16px;">
                                        <?php if ($b->status === 'approved'): ?>
                                            Confirmed: <?php echo esc_html($date_obj->format('F j, Y \a\t g:i A')); ?>
                                        <?php else: ?>
                                            Preferred: <?php echo esc_html($date_obj->format('F j, Y')); ?>
                                        <?php endif; ?>
                                    </h4>
                                    <span class="gv-status-tag <?php echo $status_class; ?>"><?php echo esc_html($status_label); ?></span>
                                </div>
                                <div class="gv-card-details" style="font-size: 14px; color: var(--gv-brand-dark-gray); line-height: 1.6;">
                                    <p style="margin: 4px 0;"><strong>Venue:</strong> <?php echo esc_html($venue_name); ?></p>
                                    <?php if ($b->status === 'approved'): ?>
                                        <p style="margin: 4px 0;"><strong>Coach:</strong> <?php echo esc_html($agent_name); ?></p>
                                    <?php endif; ?>
                                    <p style="margin: 4px 0;"><strong>Athlete:</strong> <?php echo esc_html($player_name . ' (Age ' . $player_age . ')'); ?></p>
                                    <p style="margin: 4px 0;"><strong>Interest:</strong> <?php echo esc_html($interest_label); ?></p>
                                    <?php if (!empty($note)): ?>
                                        <p style="margin: 8px 0 4px 0; background: #F8FAFC; padding: 10px; border-radius: 6px; font-style: italic; border-left: 3px solid #CBD5E1;">"<?php echo esc_html($note); ?>"</p>
                                    <?php endif; ?>
                                    <p style="margin: 4px 0; font-size: 12px; color: #64748B;"><strong>Ref:</strong> <?php echo esc_html($b->booking_code); ?></p>
                                </div>
                                <div style="margin-top: 15px; border-top: 1px solid #F1F5F9; padding-top: 10px; font-size: 13px;">
                                    <a href="<?php echo esc_url(gv_members_change_mailto($b->booking_code)); ?>" style="color: var(--gv-brand-orange); font-weight: bold; text-decoration: none;">Need to make a change? Email GV Basketball</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: Confirmed Sessions -->
        <div class="gv-portal-tab-content" id="gv-tab-sessions" role="tabpanel" aria-labelledby="tab-sessions" style="display:none; padding: 24px;">
            <!-- Upcoming Sessions Section -->
            <h3 style="margin-top: 0; color: var(--gv-brand-navy); font-weight: 800; border-bottom: 2px solid var(--gv-brand-light-gray); padding-bottom: 8px; font-size: 18px;">Upcoming Confirmed Sessions</h3>
            <?php if (empty($upcoming_sessions)): ?>
                <div class="gv-empty-state" style="padding: 20px 0; color: var(--gv-brand-dark-gray); font-size: 14px;">
                    <p style="margin-bottom: 15px;">No upcoming confirmed sessions scheduled.</p>
                    <button class="gv-btn gv-new-request-btn" style="background: var(--gv-brand-orange); color: #FFF; border: none; font-weight: bold; padding: 8px 16px; border-radius: 6px; cursor: pointer;">Request a Consultation</button>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px;">
                    <?php foreach ($upcoming_sessions as $item):
                        $b = $item['booking'];
                        $dt = $item['datetime'];
                        
                        $service_name = 'Player Consultation';
                        if (!empty($b->service_id) && class_exists('OsServiceModel')) {
                            $srv = new OsServiceModel($b->service_id);
                            if ($srv && !$srv->is_new_record()) {
                                $service_name = $srv->name;
                            }
                        }

                        $venue_name = 'Unknown Location';
                        if (!empty($b->location_id) && class_exists('OsLocationModel')) {
                            $loc = new OsLocationModel($b->location_id);
                            if ($loc && !$loc->is_new_record()) {
                                $venue_name = $loc->name;
                            }
                        }

                        $agent_name = 'Coach Gino';
                        if (!empty($b->agent_id) && class_exists('OsAgentModel')) {
                            $agent = new OsAgentModel($b->agent_id);
                            if ($agent && !$agent->is_new_record()) {
                                $agent_name = $agent->first_name . ' ' . $agent->last_name;
                            }
                        }
                        
                        $player_name = $b->get_meta_by_key('gv_player_name') ?: $customer->first_name . ' ' . $customer->last_name;
                        ?>
                        <div class="gv-session-card" style="border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; background: #FFF; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <h4 style="margin: 0; color: var(--gv-brand-navy); font-weight: bold; font-size: 16px;"><?php echo esc_html($service_name); ?></h4>
                                    <p style="margin: 4px 0 0 0; font-size: 14px; font-weight: 600; color: var(--gv-brand-orange);"><?php echo esc_html($dt->format('F j, Y \a\t g:i A')); ?></p>
                                </div>
                                <span class="gv-status-tag gv-status-confirmed">Confirmed</span>
                            </div>
                            <div style="margin-top: 12px; font-size: 13px; color: var(--gv-brand-dark-gray); line-height: 1.5;">
                                <p style="margin: 3px 0;"><strong>Venue:</strong> <?php echo esc_html($venue_name); ?></p>
                                <p style="margin: 3px 0;"><strong>Coach:</strong> <?php echo esc_html($agent_name); ?></p>
                                <p style="margin: 3px 0;"><strong>Athlete:</strong> <?php echo esc_html($player_name); ?></p>
                                <p style="margin: 3px 0; font-size: 11px; color: #64748B;"><strong>Ref:</strong> <?php echo esc_html($b->booking_code); ?></p>
                            </div>
                            <div style="margin-top: 12px; border-top: 1px solid #F1F5F9; padding-top: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                                <a href="<?php echo esc_url(add_query_arg(['gv_download_ics' => 1, 'booking_code' => $b->booking_code], home_url('/members/'))); ?>" style="color: var(--gv-brand-navy); font-weight: 600; text-decoration: none;" class="gv-ics-link">Add to Calendar (.ics)</a>
                                <a href="<?php echo esc_url(gv_members_change_mailto($b->booking_code)); ?>" style="color: #64748B; text-decoration: underline;">Request Change</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Past Sessions Section -->
            <h3 style="margin-top: 24px; color: var(--gv-brand-navy); font-weight: 800; border-bottom: 2px solid var(--gv-brand-light-gray); padding-bottom: 8px; font-size: 18px;">Past Confirmed Sessions</h3>
            <?php if (empty($past_sessions)): ?>
                <p style="color: var(--gv-brand-dark-gray); font-size: 14px; margin: 15px 0;">No past sessions found.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($past_sessions as $item):
                        $b = $item['booking'];
                        $dt = $item['datetime'];
                        
                        $service_name = 'Player Consultation';
                        if (!empty($b->service_id) && class_exists('OsServiceModel')) {
                            $srv = new OsServiceModel($b->service_id);
                            if ($srv && !$srv->is_new_record()) {
                                $service_name = $srv->name;
                            }
                        }

                        $venue_name = 'Unknown Location';
                        if (!empty($b->location_id) && class_exists('OsLocationModel')) {
                            $loc = new OsLocationModel($b->location_id);
                            if ($loc && !$loc->is_new_record()) {
                                $venue_name = $loc->name;
                            }
                        }
                        
                        $player_name = $b->get_meta_by_key('gv_player_name') ?: $customer->first_name . ' ' . $customer->last_name;
                        ?>
                        <div class="gv-session-card" style="border: 1px solid #E2E8F0; border-radius: 8px; padding: 12px; background: #FAFAFA;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="margin: 0; color: #475569; font-weight: bold; font-size: 14px;"><?php echo esc_html($service_name . ' (' . $player_name . ')'); ?></h4>
                                    <p style="margin: 2px 0 0 0; font-size: 13px; color: #64748B;"><?php echo esc_html($dt->format('F j, Y \a\t g:i A')); ?></p>
                                </div>
                                <span style="font-size: 12px; color: #64748B; font-weight: bold;"><?php echo esc_html($venue_name); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 3: Profile -->
        <div class="gv-portal-tab-content" id="gv-tab-profile" role="tabpanel" aria-labelledby="tab-profile" style="display:none; padding: 24px;">
            <?php if (isset($_GET['profile_updated'])): ?>
                <div class="gv-success-banner" style="background: #e6f7ed; color: #1a7f37; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; font-size: 14px;">
                    Profile updated successfully.
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo esc_url(home_url('/members/')); ?>" style="max-width: 500px;">
                <input type="hidden" name="action" value="gv_update_profile">
                <?php wp_nonce_field('gv_update_profile', 'gv_profile_nonce'); ?>

                <div class="gv-field-wrap">
                    <label for="gv-prof-first">First Name</label>
                    <input type="text" id="gv-prof-first" name="first_name" value="<?php echo esc_attr($customer->first_name); ?>" required>
                </div>

                <div class="gv-field-wrap">
                    <label for="gv-prof-last">Last Name</label>
                    <input type="text" id="gv-prof-last" name="last_name" value="<?php echo esc_attr($customer->last_name); ?>" required>
                </div>

                <div class="gv-field-wrap">
                    <label for="gv-prof-phone">Phone Number</label>
                    <input type="text" id="gv-prof-phone" name="phone" value="<?php echo esc_attr($customer->phone); ?>" placeholder="0917...">
                </div>

                <div class="gv-field-wrap">
                    <label for="gv-prof-email">Verified Email</label>
                    <input type="email" id="gv-prof-email" value="<?php echo esc_attr($customer->email); ?>" readonly disabled style="background: #F1F5F9; cursor: not-allowed; border-color: #E2E8F0;">
                    <p style="font-size: 12px; color: #64748B; margin: 5px 0 0 0;">Email GV Basketball to change this address</p>
                </div>

                <button type="submit" class="gv-submit-btn" style="margin-top: 15px;">Update Profile</button>
            </form>

            <!-- Logout Form -->
            <div style="margin-top: 40px; border-top: 1px solid #E2E8F0; padding-top: 20px;">
                <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="gv_logout">
                    <?php wp_nonce_field('gv_logout_nonce'); ?>
                    <button type="submit" class="gv-btn" style="background: none; border: 1px solid #CBD5E1; color: var(--gv-brand-navy); font-weight: bold; padding: 10px 20px; border-radius: 6px; cursor: pointer; min-height: 44px;">Logout</button>
                </form>
            </div>
        </div>
    </div>
    <?php
}
