<?php
// Framework-free CLI test. Run: php build/mu-plugins/tests/test-gv-members-contracts.php

define('ABSPATH', __DIR__);
define('DAY_IN_SECONDS', 86400);

// Mock WordPress functions
$registered_shortcodes = [];
function add_shortcode($tag, $func) {
    global $registered_shortcodes;
    $registered_shortcodes[$tag] = $func;
}

$registered_actions = [];
function add_action($hook, $func, $priority = 10, $accepted_args = 1) {
    global $registered_actions;
    $registered_actions[$hook][] = [
        'function' => $func,
        'priority' => $priority
    ];
}

$registered_filters = [];
function add_filter($hook, $func, $priority = 10, $accepted_args = 1) {
    global $registered_filters;
    $registered_filters[$hook][] = [
        'function' => $func,
        'priority' => $priority
    ];
}

function plugins_url($path, $plugin) {
    return 'https://example.test/wp-content/plugins/' . ltrim($path, '/');
}

// Stub other functions
function esc_attr($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_html($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_url($s){ return (string)$s; }
function home_url($p=''){ return 'https://example.test'.$p; }
function wp_create_nonce($a=''){ return 'testnonce'; }
function admin_url($p=''){ return 'https://example.test/wp-admin/'.$p; }
function sanitize_key($s){ return strtolower(preg_replace('/[^a-z0-9_]/','', (string)$s)); }
function wp_json_encode($d){ return json_encode($d); }
function wp_salt($scheme = 'auth') { return 'salt'; }

// Stubs for plugins_loaded require logic
if (!class_exists('OsBookingModel')) {
    class OsBookingModel {}
}

$failures = 0;

function check($label, $cond) {
    global $failures;
    if ($cond) {
        echo "ok   - $label\n";
    } else {
        echo "FAIL - $label\n";
        $failures++;
    }
}

function gv_assert_contains($needle, $haystack, $label) {
    check($label, strpos($haystack, $needle) !== false);
}

function gv_assert_not_contains($needle, $haystack, $label) {
    check($label, strpos($haystack, $needle) === false);
}

// Load bootstrap file
require_once __DIR__ . '/../gv-members.php';

// Check shortcode registration
check('registers [gv_members_portal] shortcode', isset($registered_shortcodes['gv_members_portal']));

// Check action registrations
check('registers template_redirect actions', isset($registered_actions['template_redirect']));
check('registers wp_enqueue_scripts action', isset($registered_actions['wp_enqueue_scripts']));
check('registers wp_footer action', isset($registered_actions['wp_footer']));

// Verify action mappings
$has_private_response = false;
$has_legacy_redirect = false;
foreach ($registered_actions['template_redirect'] as $act) {
    if ($act['function'] === 'gv_members_private_response') {
        $has_private_response = true;
    }
    if ($act['function'] === 'gv_members_legacy_redirect') {
        $has_legacy_redirect = true;
    }
}
check('template_redirect has gv_members_private_response', $has_private_response);
check('template_redirect has gv_members_legacy_redirect', $has_legacy_redirect);

// Static configuration checks
$script_dir = __DIR__ . '/../../scripts';
$consultation_script = $script_dir . '/configure-members-consultation.php';
$members_page_script = $script_dir . '/configure-members-page.php';
$consult_page_script = $script_dir . '/configure-consultation-page.php';

check('configure-members-consultation.php exists', file_exists($consultation_script));
check('configure-members-page.php exists', file_exists($members_page_script));
check('configure-consultation-page.php exists', file_exists($consult_page_script));

if (file_exists($consultation_script)) {
    $content = file_get_contents($consultation_script);
    gv_assert_not_contains('TRUNCATE', $content, 'consultation script does not contain TRUNCATE');
    gv_assert_not_contains('DELETE FROM wp_latepoint_bookings', $content, 'consultation script does not delete bookings');
    gv_assert_not_contains('DELETE FROM wp_latepoint_customers', $content, 'consultation script does not delete customers');
    gv_assert_contains('duration = 45', $content, 'consultation script sets duration to 45');
    gv_assert_contains('timeblock_interval = 180', $content, 'consultation script sets interval to 180');
    gv_assert_contains('override_default_booking_status =', $content, 'consultation script overrides default status');
    gv_assert_contains('require_otp_for_new_contacts', $content, 'consultation script requires OTP for new contacts');
    gv_assert_contains('/members/', $content, 'consultation script redirects dashboard/login to /members/');
    gv_assert_contains('Private Training', $content, 'consultation script hides paid services');
}

if (file_exists($members_page_script)) {
    $content = file_get_contents($members_page_script);
    gv_assert_not_contains('TRUNCATE', $content, 'members page script does not contain TRUNCATE');
    gv_assert_contains('gv_members_portal', $content, 'members page script contains portal shortcode');
}

if (file_exists($consult_page_script)) {
    $content = file_get_contents($consult_page_script);
    gv_assert_not_contains('TRUNCATE', $content, 'consultation page script does not contain TRUNCATE');
    gv_assert_contains('latepoint_book_form', $content, 'consultation page script contains native booking form shortcode');
}

echo $failures ? "\n$failures FAILED\n" : "\nALL PASS\n";
exit($failures ? 1 : 0);
