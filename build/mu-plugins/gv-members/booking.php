<?php
/**
 * GV Members — Wizard Fields, Turnstile, Cart Payload
 */

defined('ABSPATH') || exit;

defined('LATEPOINT_STATUS_ERROR') || define('LATEPOINT_STATUS_ERROR', 'error');

// Register wizard field hooks.
// latepoint_process_step is a do_action with 3 args: ($step_code, $booking_object, $params) —
// see latepoint/lib/controllers/steps_controller.php. Core handler runs at priority 10.
add_action('latepoint_booking_steps_contact_after', 'gv_members_booking_fields', 10, 2);
add_action('latepoint_process_step', 'gv_members_process_step_validation', 1, 3);
add_action('latepoint_process_step', 'gv_members_process_step_persistence', 20, 3);
add_action('latepoint_booking_created', 'gv_members_booking_created_handler', 5, 1);

/**
 * Render custom fields on the LatePoint contact step for Player Consultation.
 * LatePoint fires this hook as ($customer, $booking) — booking is the 2nd arg
 * (see latepoint/lib/views/steps/partials/_contact_form.php).
 */
function gv_members_booking_fields($customer, $booking = null) {
    if (!is_object($booking) || empty($booking->service_id)) {
        return;
    }

    if (!class_exists('OsServiceModel')) {
        return;
    }

    $service = new OsServiceModel($booking->service_id);
    if (!$service || $service->is_new_record() || $service->name !== 'Player Consultation') {
        return;
    }

    $sitekey = defined('GV_TURNSTILE_SITEKEY') ? GV_TURNSTILE_SITEKEY : '';

    // Check if customer is logged in to offer player reuse
    $prior_players = [];
    if (class_exists('OsAuthHelper') && class_exists('OsBookingModel')) {
        $customer = OsAuthHelper::get_logged_in_customer();
        if ($customer) {
            $customer_bookings = (new OsBookingModel())->where(['customer_id' => $customer->id])->get_results_as_models();
            if (!is_array($customer_bookings)) {
                $customer_bookings = $customer_bookings ? [$customer_bookings] : [];
            }
            foreach ($customer_bookings as $b) {
                $p_name = $b->get_meta_by_key('gv_player_name');
                $p_age = $b->get_meta_by_key('gv_player_age');
                if (!empty($p_name) && !empty($p_age)) {
                    $key = strtolower(trim($p_name)) . '|' . $p_age;
                    $prior_players[$key] = [
                        'name' => trim($p_name),
                        'age'  => (int) $p_age,
                    ];
                }
            }
            $prior_players = array_values($prior_players);
        }
    }
    ?>
    <div class="gv-consult-fields">
        <?php if (!empty($prior_players)): ?>
            <div class="gv-field-wrap">
                <label for="gv-select-player">Select Athlete</label>
                <select id="gv-select-player" class="gv-player-select" style="min-height: 44px; padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 6px; font-family: inherit;">
                    <option value="">-- New Athlete --</option>
                    <?php foreach ($prior_players as $idx => $p): ?>
                        <option value="<?php echo $idx; ?>" data-name="<?php echo esc_attr($p['name']); ?>" data-age="<?php echo esc_attr($p['age']); ?>">
                            <?php echo esc_html($p['name'] . ' (Age ' . $p['age'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="gv-field-wrap">
            <label for="gv-player-name">Player Name <span class="required">*</span></label>
            <input type="text" id="gv-player-name" name="gv_consult[player_name]" required maxlength="100">
        </div>

        <div class="gv-field-wrap">
            <label for="gv-player-age">Player Age (3–99) <span class="required">*</span></label>
            <input type="number" id="gv-player-age" name="gv_consult[player_age]" required min="3" max="99">
        </div>

        <div class="gv-field-wrap">
            <label for="gv-training-interest">Training Interest <span class="required">*</span></label>
            <select id="gv-training-interest" name="gv_consult[training_interest]" required>
                <option value="">Select interest...</option>
                <option value="private">Private</option>
                <option value="small_group">Small Group</option>
                <option value="elite">Elite Performance</option>
            </select>
        </div>

        <div class="gv-field-wrap">
            <label for="gv-contact-alt">Phone or Instagram (Optional)</label>
            <input type="text" id="gv-contact-alt" name="gv_consult[contact_alt]" placeholder="e.g. @username or 0917..." maxlength="120">
        </div>

        <div class="gv-field-wrap">
            <label for="gv-note">Anything we should know? (Optional)</label>
            <textarea id="gv-note" name="gv_consult[note]" maxlength="500" placeholder="Describe player's level, goals, or injuries..."></textarea>
        </div>

        <div class="gv-field-wrap gv-checkbox-wrap">
            <label class="gv-checkbox-label">
                <input type="checkbox" name="gv_consult[member_opt_in]" value="yes">
                <span>Send me access to the GV Members site</span>
            </label>
        </div>

        <!-- Honeypot -->
        <div style="display:none !important;" aria-hidden="true" tabindex="-1">
            <input type="text" name="gv_website" value="" autocomplete="off">
        </div>

        <!-- Turnstile Widget -->
        <?php if ($sitekey): ?>
            <div class="gv-turnstile-wrap">
                <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($sitekey); ?>" data-theme="light"></div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Validate step submission and verify Turnstile before LatePoint advances.
 */
function gv_members_process_step_validation($step_code, $booking_object, $params) {
    if ($step_code !== 'customer') {
        return;
    }

    if (!is_object($booking_object) || empty($booking_object->service_id)) {
        return;
    }

    if (!class_exists('OsServiceModel')) {
        return;
    }

    $service = new OsServiceModel($booking_object->service_id);
    if (!$service || $service->is_new_record() || $service->name !== 'Player Consultation') {
        return;
    }

    $checked = gv_members_validate_payload($params['gv_consult'] ?? []);
    if ($checked['errors']) {
        wp_send_json([
            'status' => LATEPOINT_STATUS_ERROR,
            'message' => reset($checked['errors']),
            'send_to_step' => 'customer',
            'fields_to_update' => []
        ]);
    }

    if (!empty($params['gv_website'])) {
        wp_send_json([
            'status' => LATEPOINT_STATUS_ERROR,
            'message' => 'Please refresh and try again.',
            'send_to_step' => 'customer',
            'fields_to_update' => []
        ]);
    }

    $turnstile = sanitize_text_field($params['cf-turnstile-response'] ?? '');
    if (!gv_members_verify_turnstile($turnstile, $_SERVER['REMOTE_ADDR'] ?? '')) {
        wp_send_json([
            'status' => LATEPOINT_STATUS_ERROR,
            'message' => 'Please complete the security check.',
            'send_to_step' => 'customer',
            'fields_to_update' => []
        ]);
    }
}

/**
 * Persist payload to Cart Meta.
 */
function gv_members_process_step_persistence($step_code, $booking_object, $params) {
    if ($step_code !== 'customer') {
        return;
    }

    if (!is_object($booking_object) || empty($booking_object->service_id)) {
        return;
    }

    if (!class_exists('OsServiceModel')) {
        return;
    }

    $service = new OsServiceModel($booking_object->service_id);
    if (!$service || $service->is_new_record() || $service->name !== 'Player Consultation') {
        return;
    }

    $checked = gv_members_validate_payload($params['gv_consult'] ?? []);
    if (empty($checked['errors'])) {
        if (class_exists('OsStepsHelper') && isset(OsStepsHelper::$cart_object) && is_object(OsStepsHelper::$cart_object)) {
            OsStepsHelper::$cart_object->save_meta_by_key('gv_consult_payload', wp_json_encode($checked['data']));
        }
    }
}

/**
 * Verify Cloudflare Turnstile token.
 */
function gv_members_verify_turnstile($token, $ip) {
    if (!defined('GV_TURNSTILE_SECRET') || !GV_TURNSTILE_SECRET) {
        return false; // Fails closed when production constants are absent
    }
    if (empty($token)) {
        return false;
    }

    $resp = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
        'timeout' => 10,
        'body'    => [
            'secret'   => GV_TURNSTILE_SECRET,
            'response' => $token,
            'remoteip' => $ip
        ]
    ]);

    if (is_wp_error($resp)) {
        return false;
    }

    $data = json_decode(wp_remote_retrieve_body($resp), true);
    return !empty($data['success']);
}

/**
 * Handle booking creation to persist custom metadata, suppress native notifications, and send branded emails.
 */
function gv_members_booking_created_handler($booking) {
    if (!is_object($booking) || empty($booking->service_id)) {
        return;
    }

    if (!class_exists('OsServiceModel')) {
        return;
    }

    $service = new OsServiceModel($booking->service_id);
    if (!$service || $service->is_new_record() || $service->name !== 'Player Consultation') {
        return;
    }

    // Read payload from Cart Meta
    $payload_json = '';
    if (class_exists('OsStepsHelper') && isset(OsStepsHelper::$cart_object) && is_object(OsStepsHelper::$cart_object)) {
        $payload_json = OsStepsHelper::$cart_object->get_meta_by_key('gv_consult_payload');
    }

    $payload = [];
    if (!empty($payload_json)) {
        $payload = json_decode($payload_json, true) ?: [];
    }
    if (!$payload && isset($_POST['gv_consult'])) {
        $payload = (array) $_POST['gv_consult'];
    }

    // Revalidate. Without a valid wizard payload (e.g. a booking created in wp-admin)
    // stand down entirely: no meta, no notification suppression, no emails.
    $checked = gv_members_validate_payload($payload);
    if ($checked['errors'] || empty($checked['data'])) {
        return;
    }
    $data = $checked['data'];

    // Guard against a double-fire of latepoint_booking_created for the same booking.
    if ($booking->get_meta_by_key('gv_coach_request_sent_at') || $booking->get_meta_by_key('gv_finalize_token_hash')) {
        return;
    }

    // Persist all approved gv_* keys
    $booking->save_meta_by_key('gv_player_name', $data['player_name']);
    $booking->save_meta_by_key('gv_player_age', $data['player_age']);
    $booking->save_meta_by_key('gv_training_interest', $data['training_interest']);
    $booking->save_meta_by_key('gv_contact_alt', $data['contact_alt']);
    $booking->save_meta_by_key('gv_note', $data['note']);
    $booking->save_meta_by_key('gv_member_opt_in', $data['member_opt_in']);
    $booking->save_meta_by_key('gv_day_request', 'yes');

    // Force pending status
    defined('LATEPOINT_BOOKING_STATUS_PENDING') || define('LATEPOINT_BOOKING_STATUS_PENDING', 'pending');
    if ($booking->status !== LATEPOINT_BOOKING_STATUS_PENDING) {
        $booking->status = LATEPOINT_BOOKING_STATUS_PENDING;
        if (method_exists($booking, 'update_attributes')) {
            $booking->update_attributes(['status' => LATEPOINT_BOOKING_STATUS_PENDING]);
        } else {
            $booking->save();
        }
    }

    // Create secure finalization token
    $raw_token = bin2hex(random_bytes(32));
    $token_hash = gv_members_token_hash($raw_token);
    defined('GV_MEMBERS_FINALIZE_TTL') || define('GV_MEMBERS_FINALIZE_TTL', 30 * 86400);
    $expiry = time() + GV_MEMBERS_FINALIZE_TTL;

    $booking->save_meta_by_key('gv_finalize_token_hash', $token_hash);
    $booking->save_meta_by_key('gv_finalize_token_expires_at', $expiry);
    $booking->save_meta_by_key('gv_finalize_token_used_at', '');
    $booking->save_meta_by_key('gv_finalized_at', '');

    // URL to finalize
    $finalize_url = add_query_arg([
        'gv_finalize_consultation' => 1,
        'booking_code' => $booking->booking_code,
        'token' => $raw_token
    ], home_url('/members/finalize/'));

    // Suppress native notifications before priority 12
    remove_action('latepoint_booking_created', ['OsProcessJobsHelper', 'handle_booking_created'], 12);

    // Extract email data view-model
    $booking_email_data = gv_members_booking_email_data($booking);

    // Date/time in Asia/Manila for logging/sending timestamp
    $manila_tz = new DateTimeZone('Asia/Manila');
    $now = new DateTime('now', $manila_tz);
    $now_str = $now->format('Y-m-d H:i:s');

    // Send parent receipt
    $parent_html = gv_members_parent_receipt_html($booking_email_data, $data);
    $parent_sent = wp_mail($booking_email_data['parent_email'], 'GV Basketball — consultation request received', $parent_html, ['Content-Type: text/html; charset=UTF-8']);
    if ($parent_sent) {
        $booking->save_meta_by_key('gv_parent_receipt_sent_at', $now_str);
    } else {
        error_log("Mail delivery failed for booking ID: " . $booking->id);
    }

    // Send Coach Gino request email
    $coach_recipient = defined('GV_RF_RECIPIENT') ? GV_RF_RECIPIENT : 'gvbasketballcoaching@gmail.com';
    $coach_subject = 'New consultation request — ' . $booking_email_data['player_name'] . ' — ' . $booking_email_data['venue_name'] . ' — ' . $booking_email_data['day'];
    $coach_html = gv_members_coach_request_html($booking_email_data, $data, $finalize_url);
    $coach_headers = [
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $booking_email_data['parent_name'] . ' <' . $booking_email_data['parent_email'] . '>'
    ];
    $coach_sent = wp_mail($coach_recipient, $coach_subject, $coach_html, $coach_headers);
    if ($coach_sent) {
        $booking->save_meta_by_key('gv_coach_request_sent_at', $now_str);
    } else {
        error_log("Mail delivery failed for booking ID: " . $booking->id);
    }
}
