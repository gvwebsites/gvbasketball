<?php
// Framework-free CLI test. Run: php build/mu-plugins/tests/test-gv-members-contracts.php

define('ABSPATH', __DIR__);
define('DAY_IN_SECONDS', 86400);

// Define Turnstile constants for testing
defined('GV_TURNSTILE_SITEKEY') || define('GV_TURNSTILE_SITEKEY', 'sitekey_test');
defined('GV_TURNSTILE_SECRET') || define('GV_TURNSTILE_SECRET', 'secret_test');

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
function sanitize_text_field($s) { return trim((string)$s); }

// Exception to intercept wp_send_json and prevent exit;
class WpSendJsonException extends Exception {
    public $response;
    public function __construct($response) {
        $this->response = $response;
    }
}

function wp_send_json($response, $status_code = null, $options = 0) {
    throw new WpSendJsonException($response);
}

// Mock HTTP requests for Turnstile verification
$turnstile_success = true;
function wp_remote_post($url, $args = []) {
    global $turnstile_success;
    return [
        'body' => json_encode(['success' => $turnstile_success])
    ];
}

function is_wp_error($thing) {
    return false;
}

function wp_remote_retrieve_body($response) {
    return $response['body'];
}

// Additional WordPress mocks for testing functionality
$is_page_val = false;
function is_page($id) {
    global $is_page_val;
    if (is_array($is_page_val)) {
        return in_array($id, $is_page_val);
    }
    return $is_page_val === $id || $is_page_val === true;
}

$nocache_headers_called = false;
function nocache_headers() {
    global $nocache_headers_called;
    $nocache_headers_called = true;
}

$actions_fired = [];
function has_action($tag) {
    return true; // Simple stub
}
function do_action($tag, ...$args) {
    global $actions_fired;
    $actions_fired[] = $tag;
}

// Exception to intercept wp_safe_redirect and prevent exit;
class RedirectException extends Exception {
    public $url;
    public $status;
    public function __construct($url, $status) {
        $this->url = $url;
        $this->status = $status;
    }
}

$redirect_url = null;
$redirect_status = null;
function wp_safe_redirect($location, $status = 302, $x_redirect_by = 'WordPress') {
    global $redirect_url, $redirect_status;
    $redirect_url = $location;
    $redirect_status = $status;
    throw new RedirectException($location, $status);
}

$enqueued_styles = [];
function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {
    global $enqueued_styles;
    $enqueued_styles[$handle] = ['src' => $src, 'ver' => $ver];
}

$enqueued_scripts = [];
function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
    global $enqueued_scripts;
    $enqueued_scripts[$handle] = ['src' => $src, 'ver' => $ver];
}

$localized_scripts = [];
function wp_localize_script($handle, $object_name, $l10n) {
    global $localized_scripts;
    $localized_scripts[$handle] = [$object_name => $l10n];
}

$is_admin_val = false;
function is_admin() {
    global $is_admin_val;
    return $is_admin_val;
}

function do_shortcode($content) {
    return $content; // Stub
}

// Stubs for plugins_loaded require logic
if (!class_exists('OsBookingModel')) {
    class OsBookingModel {
        public $service_id;
    }
}

if (!class_exists('OsServiceModel')) {
    class OsServiceModel {
        public $id;
        public $name;
        
        public function __construct($id = null) {
            $this->id = $id;
            if ($id == 7) {
                $this->name = 'Player Consultation';
            } else {
                $this->name = 'Other Training';
            }
        }
        public function is_new_record() {
            return false;
        }
        public function where($args) {
            return $this;
        }
        public function set_limit($limit) {
            return $this;
        }
        public function get_results_as_models() {
            return [$this];
        }
    }
}

if (!class_exists('OsCartModel')) {
    class OsCartModel {
        public $meta = [];
        public function save_meta_by_key($key, $value) {
            $this->meta[$key] = $value;
            return true;
        }
    }
}

if (!class_exists('OsStepsHelper')) {
    class OsStepsHelper {
        public static $cart_object;
    }
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

// ==================== TASK 3 CONTRACT TESTS ====================

// 1. Module Loading Check
// Simulate plugins_loaded hook priority 20 call
$pre_loaded = get_included_files();
gv_members_load_modules();
$post_loaded = get_included_files();

$loaded_modules = [
    'booking.php',
    'emails.php',
    'auth.php',
    'portal.php',
    'finalize.php'
];
foreach ($loaded_modules as $mod) {
    $found = false;
    foreach ($post_loaded as $path) {
        if (basename($path) === $mod) {
            $found = true;
            break;
        }
    }
    check("module $mod is loaded", $found);
}

// 2. Legacy Redirect Test (gv_members_legacy_redirect) - RUN FIRST before DOING_AJAX is defined
// A. Successful GET redirect for /booking
$redirect_url = null;
$redirect_status = null;
$is_admin_val = false;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/booking';

try {
    gv_members_legacy_redirect();
    $redirected = false;
} catch (RedirectException $e) {
    $redirected = true;
}
check('redirects /booking to /members/', $redirected && $redirect_url === 'https://example.test/members/' && $redirect_status === 301);

// B. Successful GET redirect for /customer-cabinet/
$redirect_url = null;
$redirect_status = null;
$_SERVER['REQUEST_URI'] = '/customer-cabinet/';

try {
    gv_members_legacy_redirect();
    $redirected = false;
} catch (RedirectException $e) {
    $redirected = true;
}
check('redirects /customer-cabinet/ to /members/', $redirected && $redirect_url === 'https://example.test/members/' && $redirect_status === 301);

// C. Skip redirect for POST method
$redirect_url = null;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/booking';

try {
    gv_members_legacy_redirect();
    $redirected = false;
} catch (RedirectException $e) {
    $redirected = true;
}
check('skips redirect for POST requests', !$redirected && $redirect_url === null);

// D. Skip redirect for admin
$redirect_url = null;
$is_admin_val = true;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/booking';

try {
    gv_members_legacy_redirect();
    $redirected = false;
} catch (RedirectException $e) {
    $redirected = true;
}
check('skips redirect for admin requests', !$redirected && $redirect_url === null);

// Reset mocks
$is_admin_val = false;
$_SERVER['REQUEST_METHOD'] = 'GET';

// 3. Cache Protection Test (gv_members_private_response)
$nocache_headers_called = false;
$actions_fired = [];
$is_page_val = 2983; // Members portal page
unset($_GET['gv_finalize_consultation']);

gv_members_private_response();
check('calls nocache_headers for page 2983', $nocache_headers_called);
check('defines DONOTCACHEPAGE', defined('DONOTCACHEPAGE') && DONOTCACHEPAGE === true);
check('fires litespeed_control_set_nocache action', in_array('litespeed_control_set_nocache', $actions_fired));

// Test with gv_finalize_consultation GET parameter
$nocache_headers_called = false;
$actions_fired = [];
$is_page_val = 123; // Some other page
$_GET['gv_finalize_consultation'] = '1';

gv_members_private_response();
check('calls nocache_headers for gv_finalize_consultation query parameter', $nocache_headers_called);
check('fires litespeed_control_set_nocache action for finalize', in_array('litespeed_control_set_nocache', $actions_fired));

// Test with AJAX action gv_otp_
$nocache_headers_called = false;
$actions_fired = [];
$is_page_val = 123;
unset($_GET['gv_finalize_consultation']);
if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', true);
}
$_REQUEST['action'] = 'gv_otp_request';

gv_members_private_response();
check('calls nocache_headers for GV OTP AJAX requests', $nocache_headers_called);

// Test with non-matching request
$nocache_headers_called = false;
$actions_fired = [];
$is_page_val = 123;
unset($_GET['gv_finalize_consultation']);
$_REQUEST['action'] = 'some_other_action';

gv_members_private_response();
check('does NOT call nocache_headers for non-matching requests', !$nocache_headers_called);

// 4. Asset Versioning Test (gv_members_enqueue_assets)
$enqueued_styles = [];
$enqueued_scripts = [];
$localized_scripts = [];
$is_page_val = 2983;

gv_members_enqueue_assets();
check('enqueues css stylesheet', isset($enqueued_styles['gv-members-css']));
check('enqueues js script', isset($enqueued_scripts['gv-members-js']));
check('localizes js script', isset($localized_scripts['gv-members-js']));

if (isset($enqueued_styles['gv-members-css'])) {
    $ver = $enqueued_styles['gv-members-css']['ver'];
    $expected_ver = filemtime(__DIR__ . '/../gv-members/assets/gv-members.css');
    check('stylesheet version matches filemtime', $ver === $expected_ver);
}

if (isset($enqueued_scripts['gv-members-js'])) {
    $ver = $enqueued_scripts['gv-members-js']['ver'];
    $expected_ver = filemtime(__DIR__ . '/../gv-members/assets/gv-members.js');
    check('script version matches filemtime', $ver === $expected_ver);
}


// ==================== TASK 4 CONTRACT TESTS ====================

// 1. Hook Registrations Check
check('registers latepoint_booking_steps_contact_after hook', isset($registered_actions['latepoint_booking_steps_contact_after']));

$has_step_priority_1 = false;
$has_step_priority_20 = false;
$all_step_hooks = array_merge(
    isset($registered_actions['latepoint_process_step']) ? $registered_actions['latepoint_process_step'] : [],
    isset($registered_filters['latepoint_process_step']) ? $registered_filters['latepoint_process_step'] : []
);
foreach ($all_step_hooks as $hook_info) {
    if ($hook_info['priority'] === 1 && $hook_info['function'] === 'gv_members_process_step_validation') {
        $has_step_priority_1 = true;
    }
    if ($hook_info['priority'] === 20 && $hook_info['function'] === 'gv_members_process_step_persistence') {
        $has_step_priority_20 = true;
    }
}
check('latepoint_process_step has priority 1 validation handler', $has_step_priority_1);
check('latepoint_process_step has priority 20 persistence handler', $has_step_priority_20);

// 2. Render Custom Fields Check
$booking = new OsBookingModel();
$booking->service_id = 7; // Player Consultation

ob_start();
gv_members_booking_fields($booking);
$output = ob_get_clean();

gv_assert_contains('name="gv_consult[player_name]"', $output, 'fields render player_name input');
gv_assert_contains('name="gv_consult[player_age]"', $output, 'fields render player_age input');
gv_assert_contains('name="gv_consult[training_interest]"', $output, 'fields render training_interest dropdown');
gv_assert_contains('name="gv_consult[contact_alt]"', $output, 'fields render contact_alt input');
gv_assert_contains('name="gv_consult[note]"', $output, 'fields render note textarea');
gv_assert_contains('name="gv_consult[member_opt_in]"', $output, 'fields render member_opt_in checkbox');
gv_assert_contains('name="gv_website"', $output, 'fields render honeypot gv_website');
gv_assert_contains('class="cf-turnstile"', $output, 'fields render Turnstile widget container');

// 3. Validation Handler Check (Priority 1)
$params = [
    'gv_consult' => [
        'player_name' => 'John Doe',
        'player_age' => '10',
        'training_interest' => 'private',
        'contact_alt' => '09123456789',
        'note' => 'Test consultation request',
        'member_opt_in' => 'yes'
    ],
    'gv_website' => '',
    'cf-turnstile-response' => 'valid-token'
];

$turnstile_success = true;

// A. Test valid submission (should pass without exception)
try {
    $res = gv_members_process_step_validation('original_response', 'customer', $booking, $params);
    check('validation passes valid parameters', $res === 'original_response');
} catch (Exception $e) {
    check('validation passes valid parameters (threw ' . get_class($e) . ')', false);
}

// B. Test validation failure: Invalid Age (too low)
$params_bad_age = $params;
$params_bad_age['gv_consult']['player_age'] = '2';
try {
    gv_members_process_step_validation('original_response', 'customer', $booking, $params_bad_age);
    check('validation fails age < 3', false);
} catch (WpSendJsonException $e) {
    check('validation fails age < 3', $e->response['status'] === 'error' && strpos($e->response['message'], 'age') !== false);
}

// C. Test validation failure: Invalid Age (too high)
$params_bad_age_high = $params;
$params_bad_age_high['gv_consult']['player_age'] = '100';
try {
    gv_members_process_step_validation('original_response', 'customer', $booking, $params_bad_age_high);
    check('validation fails age > 99', false);
} catch (WpSendJsonException $e) {
    check('validation fails age > 99', $e->response['status'] === 'error' && strpos($e->response['message'], 'age') !== false);
}

// D. Test validation failure: Honeypot triggered
$params_honeypot = $params;
$params_honeypot['gv_website'] = 'im-a-bot';
try {
    gv_members_process_step_validation('original_response', 'customer', $booking, $params_honeypot);
    check('validation fails if honeypot has value', false);
} catch (WpSendJsonException $e) {
    check('validation fails if honeypot has value', $e->response['status'] === 'error' && $e->response['message'] === 'Please refresh and try again.');
}

// E. Test validation failure: Turnstile verification failure
$params_bad_turnstile = $params;
$turnstile_success = false;
try {
    gv_members_process_step_validation('original_response', 'customer', $booking, $params_bad_turnstile);
    check('validation fails if Turnstile verification fails', false);
} catch (WpSendJsonException $e) {
    check('validation fails if Turnstile verification fails', $e->response['status'] === 'error' && $e->response['message'] === 'Please complete the security check.');
}
$turnstile_success = true;

// 4. Persistence Handler Check (Priority 20)
OsStepsHelper::$cart_object = new OsCartModel();
$res = gv_members_process_step_persistence('original_response', 'customer', $booking, $params);
check('persistence returns response', $res === 'original_response');

$saved_payload_json = OsStepsHelper::$cart_object->meta['gv_consult_payload'] ?? null;
check('persistence saves payload to cart metadata', $saved_payload_json !== null);

if ($saved_payload_json) {
    $saved_payload = json_decode($saved_payload_json, true);
    check('saved player name matches', $saved_payload['player_name'] === 'John Doe');
    check('saved player age matches', $saved_payload['player_age'] === 10);
    check('saved training interest matches', $saved_payload['training_interest'] === 'private');
    check('saved contact details match', $saved_payload['contact_alt'] === '09123456789');
    check('saved member opt-in matches', $saved_payload['member_opt_in'] === 'yes');
    check('saved day_request defaults to yes', $saved_payload['day_request'] === 'yes');
}

echo $failures ? "\n$failures FAILED\n" : "\nALL PASS\n";
exit($failures ? 1 : 0);
