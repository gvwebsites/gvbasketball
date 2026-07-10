<?php
/**
 * GV Members — OTP Authentication
 */
defined('ABSPATH') || exit;

defined('HOUR_IN_SECONDS') || define('HOUR_IN_SECONDS', 3600);


// Register AJAX endpoints
add_action('wp_ajax_gv_otp_request', 'gv_otp_request_handler');
add_action('wp_ajax_nopriv_gv_otp_request', 'gv_otp_request_handler');

add_action('wp_ajax_gv_otp_verify', 'gv_otp_verify_handler');
add_action('wp_ajax_nopriv_gv_otp_verify', 'gv_otp_verify_handler');

// Register logout endpoint
add_action('admin_post_gv_logout', 'gv_members_logout_handler');
add_action('admin_post_nopriv_gv_logout', 'gv_members_logout_handler');

/**
 * AJAX handler to generate and send OTP
 */
function gv_otp_request_handler() {
    nocache_headers();

    // Verify nonce
    if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gv_otp_nonce')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh and try again.']);
    }

    $email = isset($_POST['email']) ? gv_members_normalize_email($_POST['email']) : '';

    if (empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'Enter a valid email address.']);
    }

    // Determine client IP address
    $ip = '';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $ip = sanitize_text_field(trim($ip));

    // Rate limits: 5 sends/email/hour and 10/IP/hour
    $email_hash = gv_members_token_hash('rate_email_' . $email);
    $ip_hash = gv_members_token_hash('rate_ip_' . $ip);

    $email_transient = 'gv_otp_e_' . substr($email_hash, 0, 45);
    $ip_transient = 'gv_otp_i_' . substr($ip_hash, 0, 45);

    $email_count = (int) get_transient($email_transient);
    $ip_count = (int) get_transient($ip_transient);

    if ($email_count >= 5 || $ip_count >= 10) {
        wp_send_json_error(['message' => 'Too many requests. Please try again later.']);
    }

    // Call LatePoint generateAndSendOTP
    if (class_exists('OsOTPHelper')) {
        $result = OsOTPHelper::generateAndSendOTP($email, 'email', 'email');
        if ($result) {
            set_transient($email_transient, $email_count + 1, HOUR_IN_SECONDS);
            set_transient($ip_transient, $ip_count + 1, HOUR_IN_SECONDS);
            wp_send_json_success(['message' => 'If the address can receive mail, a six-digit code is on its way.']);
        } else {
            wp_send_json_error(['message' => 'Failed to send verification code. Please try again.']);
        }
    } else {
        wp_send_json_error(['message' => 'Authentication service is currently unavailable.']);
    }
}

/**
 * AJAX handler to verify OTP and log in customer
 */
function gv_otp_verify_handler() {
    nocache_headers();

    // Verify nonce
    if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gv_otp_nonce')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh and try again.']);
    }

    $email = isset($_POST['email']) ? gv_members_normalize_email($_POST['email']) : '';
    $otp_code = isset($_POST['otp']) ? sanitize_text_field($_POST['otp']) : '';

    if (empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'Enter a valid email address.']);
    }

    if (empty($otp_code)) {
        wp_send_json_error(['message' => 'Enter verification code.']);
    }

    // Call LatePoint verifyOTP
    if (!class_exists('OsOTPHelper') || !OsOTPHelper::verifyOTP($otp_code, $email, 'email', 'email')) {
        wp_send_json_error(['message' => 'Invalid or expired verification code.']);
    }

    // Find customer by email
    if (!class_exists('OsCustomerModel')) {
        wp_send_json_error(['message' => 'Customer service is currently unavailable.']);
    }

    $customer = (new OsCustomerModel())->where(['email' => $email])->set_limit(1)->get_results_as_models();

    if (empty($customer)) {
        // Temporarily filter model validations to require only unique valid email
        $validation_filter = function($validations) {
            return [
                'email' => [
                    'presence' => true,
                    'email' => true,
                    'uniqueness' => true,
                ]
            ];
        };
        add_filter('latepoint_customer_model_validations', $validation_filter);

        $new_customer = new OsCustomerModel();
        $new_customer->email = $email;
        $new_customer->status = 'active';
        $new_customer->is_guest = 0;

        // Re-query immediately before save to handle races (if a concurrent request won, authorize that row)
        $race_customer = (new OsCustomerModel())->where(['email' => $email])->set_limit(1)->get_results_as_models();
        if (!empty($race_customer)) {
            $customer = $race_customer;
        } else {
            if ($new_customer->save()) {
                $customer = $new_customer;
                // Fire latepoint_customer_created with ONE arg
                do_action('latepoint_customer_created', $customer);
            } else {
                remove_filter('latepoint_customer_model_validations', $validation_filter);
                wp_send_json_error(['message' => 'Failed to create customer profile.']);
            }
        }
        remove_filter('latepoint_customer_model_validations', $validation_filter);
    }

    // Mark contact verified
    if (is_object($customer)) {
        $customer->is_email_verified = 1;
        if (method_exists($customer, 'save_meta_by_key')) {
            $customer->save_meta_by_key('is_email_verified', 'yes');
        }
    }

    // Authorize customer
    $customer_id = is_object($customer) ? $customer->id : (int) $customer;
    if (class_exists('OsAuthHelper') && OsAuthHelper::authorize_customer($customer_id)) {
        wp_send_json_success(['message' => 'Success! Redirecting...']);
    } else {
        wp_send_json_error(['message' => 'Failed to authorize user session.']);
    }
}

/**
 * Handle POST logout request
 */
function gv_members_logout_handler() {
    if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'gv_logout_nonce')) {
        wp_die('Security check failed.', 403);
    }

    if (class_exists('OsAuthHelper')) {
        OsAuthHelper::logout_customer();
    }

    wp_safe_redirect(home_url('/members/'));
    exit;
}
