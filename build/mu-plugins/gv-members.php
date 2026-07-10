<?php
/**
 * Plugin Name: GV Members & Consultation Integration
 * Description: Core bootstrap, cache-protection, legacy redirects, and wizard bridge.
 * Version: 1.0.0
 * Author: GV Basketball
 */

defined('ABSPATH') || exit;

// Load pure domain helpers immediately
require_once __DIR__ . '/gv-members/core.php';

// Load LatePoint dependent modules after plugins are loaded
add_action('plugins_loaded', 'gv_members_load_modules', 20);

function gv_members_load_modules() {
    if (!class_exists('OsBookingModel')) {
        return; // LatePoint is not active
    }

    require_once __DIR__ . '/gv-members/booking.php';
    require_once __DIR__ . '/gv-members/emails.php';
    require_once __DIR__ . '/gv-members/auth.php';
    require_once __DIR__ . '/gv-members/portal.php';
    require_once __DIR__ . '/gv-members/finalize.php';
}

// 1. Private Response Cache Protection (Priority 0)
add_action('template_redirect', 'gv_members_private_response', 0);

function gv_members_private_response() {
    $is_portal = is_page(2983);
    $is_finalize = isset($_GET['gv_finalize_consultation']);
    $is_ajax = (defined('DOING_AJAX') && DOING_AJAX) && isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'gv_otp_') === 0;

    if ($is_portal || $is_finalize || $is_ajax) {
        nocache_headers();
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        if (has_action('litespeed_control_set_nocache')) {
            do_action('litespeed_control_set_nocache');
        }
        if (!headers_sent()) {
            header('Cache-Control: private, no-store, max-age=0');
        }
    }
}

// 2. Legacy Redirects (Priority 1)
add_action('template_redirect', 'gv_members_legacy_redirect', 1);

function gv_members_legacy_redirect() {
    if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX) || (defined('REST_REQUEST') && REST_REQUEST) || (defined('WP_CLI') && WP_CLI)) {
        return;
    }
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }

    $path = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    $path = rtrim($path, '/');

    if ($path === '/booking' || $path === '/customer-cabinet') {
        wp_safe_redirect(home_url('/members/'), 301);
        exit;
    }
}

// 3. Enqueue Assets (Priority 30)
add_action('wp_enqueue_scripts', 'gv_members_enqueue_assets', 30);

function gv_members_enqueue_assets() {
    // Only load on members/booking pages or when finalize query parameter is active
    if (!is_page(2983) && !is_page(2982) && !isset($_GET['gv_finalize_consultation'])) {
        return;
    }

    $css_path = __DIR__ . '/gv-members/assets/gv-members.css';
    $js_path = __DIR__ . '/gv-members/assets/gv-members.js';

    $css_ver = file_exists($css_path) ? filemtime($css_path) : '1.0.0';
    $js_ver = file_exists($js_path) ? filemtime($js_path) : '1.0.0';

    wp_enqueue_style(
        'gv-members-css',
        plugins_url('gv-members/assets/gv-members.css', __FILE__),
        [],
        $css_ver
    );

    wp_enqueue_script(
        'gv-members-js',
        plugins_url('gv-members/assets/gv-members.js', __FILE__),
        ['jquery'],
        $js_ver,
        true
    );

    wp_localize_script('gv-members-js', 'gvMembers', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'otpNonce' => wp_create_nonce('gv_otp_nonce'),
        'turnstileSitekey' => defined('GV_TURNSTILE_SITEKEY') ? GV_TURNSTILE_SITEKEY : '',
    ]);
}

// 4. Hidden Booking Trigger (Priority 40)
add_action('wp_footer', 'gv_members_hidden_booking_trigger', 40);

function gv_members_hidden_booking_trigger() {
    if (!class_exists('OsServiceModel')) {
        return;
    }

    $service = (new OsServiceModel())->where(['name' => 'Player Consultation'])->set_limit(1)->get_results_as_models();
    if ($service && !$service->is_new_record()) {
        echo '<div id="gv-consult-trigger" hidden>' .
            do_shortcode('[latepoint_book_button caption="Book a Consultation" selected_service="' . (int) $service->id . '" hide_side_panel="yes"]') .
            '</div>';
    }
}

// 5. Shortcode Stub Registration
add_shortcode('gv_members_portal', 'gv_members_portal_shortcode');

function gv_members_portal_shortcode() {
    if (function_exists('gv_members_portal_render')) {
        return gv_members_portal_render();
    }
    return '<p>Members Portal Loading...</p>';
}
