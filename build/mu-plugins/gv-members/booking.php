<?php
/**
 * GV Members — Wizard Fields, Turnstile, Cart Payload
 */

defined('ABSPATH') || exit;

defined('LATEPOINT_STATUS_ERROR') || define('LATEPOINT_STATUS_ERROR', 'error');

// Register wizard field hooks
add_action('latepoint_booking_steps_contact_after', 'gv_members_booking_fields', 10, 1);
add_filter('latepoint_process_step', 'gv_members_process_step_validation', 1, 4);
add_filter('latepoint_process_step', 'gv_members_process_step_persistence', 20, 4);

/**
 * Render custom fields on the LatePoint contact step for Player Consultation.
 */
function gv_members_booking_fields($booking) {
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
    ?>
    <div class="gv-consult-fields">
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
                <input type="checkbox" name="gv_consult[member_opt_in]" value="yes" checked>
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
function gv_members_process_step_validation($response, $step_code, $booking_object, $params) {
    if ($step_code !== 'customer') {
        return $response;
    }

    if (!is_object($booking_object) || empty($booking_object->service_id)) {
        return $response;
    }

    if (!class_exists('OsServiceModel')) {
        return $response;
    }

    $service = new OsServiceModel($booking_object->service_id);
    if (!$service || $service->is_new_record() || $service->name !== 'Player Consultation') {
        return $response;
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

    return $response;
}

/**
 * Persist payload to Cart Meta.
 */
function gv_members_process_step_persistence($response, $step_code, $booking_object, $params) {
    if ($step_code !== 'customer') {
        return $response;
    }

    if (!is_object($booking_object) || empty($booking_object->service_id)) {
        return $response;
    }

    if (!class_exists('OsServiceModel')) {
        return $response;
    }

    $service = new OsServiceModel($booking_object->service_id);
    if (!$service || $service->is_new_record() || $service->name !== 'Player Consultation') {
        return $response;
    }

    $checked = gv_members_validate_payload($params['gv_consult'] ?? []);
    if (empty($checked['errors'])) {
        if (class_exists('OsStepsHelper') && isset(OsStepsHelper::$cart_object) && is_object(OsStepsHelper::$cart_object)) {
            OsStepsHelper::$cart_object->save_meta_by_key('gv_consult_payload', wp_json_encode($checked['data']));
        }
    }

    return $response;
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
