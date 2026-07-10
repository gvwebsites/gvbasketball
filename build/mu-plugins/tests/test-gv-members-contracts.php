<?php
// Framework-free CLI test. Run: php build/mu-plugins/tests/test-gv-members-contracts.php
namespace {
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
function wp_verify_nonce($nonce, $action = -1) { return $nonce === 'valid-nonce'; }
function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true) {
    $html = '<input type="hidden" name="' . esc_attr($name) . '" value="valid-nonce">';
    if ($echo) echo $html;
    return $html;
}
function admin_url($p=''){ return 'https://example.test/wp-admin/'.$p; }
function sanitize_key($s){ return strtolower(preg_replace('/[^a-z0-9_]/','', (string)$s)); }
function wp_json_encode($d){ return json_encode($d); }
function wp_salt($scheme = 'auth') { return 'salt'; }
function sanitize_text_field($s) { return trim((string)$s); }

$sent_mails = [];
function wp_mail($to, $subject, $message, $headers = '', $attachments = []) {
    global $sent_mails;
    $sent_mails[] = [
        'to' => $to,
        'subject' => $subject,
        'message' => $message,
        'headers' => $headers,
    ];
    return true;
}

function remove_action($hook, $func, $priority = 10) {
    global $registered_actions;
    if (isset($registered_actions[$hook])) {
        foreach ($registered_actions[$hook] as $key => $action) {
            if ($action['function'] === $func && $action['priority'] === $priority) {
                unset($registered_actions[$hook][$key]);
            }
        }
    }
    return true;
}

function remove_filter($hook, $func, $priority = 10) {
    global $registered_filters;
    if (isset($registered_filters[$hook])) {
        foreach ($registered_filters[$hook] as $key => $filter) {
            if ($filter['function'] === $func && $filter['priority'] === $priority) {
                unset($registered_filters[$hook][$key]);
            }
        }
    }
    return true;
}

if (!class_exists('MockWpdb')) {
    class MockWpdb {
        public $prefix = 'wp_';
        public function query($q) { return true; }
        public function prepare($q, ...$args) { return vsprintf(str_replace('%d', '%s', $q), $args); }
        public function get_row($q) { return (object)['id' => 888, 'status' => 'pending']; }
    }
}
global $wpdb;
$wpdb = new MockWpdb();

function add_query_arg($args, $url = '') {
    $parts = parse_url($url);
    $query = [];
    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    if (is_array($args)) {
        foreach ($args as $k => $v) {
            $query[$k] = $v;
        }
    }
    $parts['query'] = http_build_query($query);
    $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
    $host = isset($parts['host']) ? $parts['host'] : '';
    $path = isset($parts['path']) ? $parts['path'] : '';
    return $scheme . $host . $path . '?' . $parts['query'];
}

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

function wp_send_json_success($data = null, $status_code = null, $options = 0) {
    throw new WpSendJsonException(['success' => true, 'data' => $data]);
}

function wp_send_json_error($data = null, $status_code = null, $options = 0) {
    throw new WpSendJsonException(['success' => false, 'data' => $data]);
}

$gv_transients = [];
function get_transient($key) {
    global $gv_transients;
    return isset($gv_transients[$key]) ? $gv_transients[$key] : false;
}
function set_transient($key, $val, $ttl) {
    global $gv_transients;
    $gv_transients[$key] = $val;
    return true;
}

function is_email($email) {
    return is_string($email) && strpos($email, '@') !== false;
}

function apply_filters($tag, $value, ...$args) {
    global $registered_filters;
    if (isset($registered_filters[$tag])) {
        foreach ($registered_filters[$tag] as $filter) {
            $value = call_user_func_array($filter['function'], array_merge([$value], $args));
        }
    }
    return $value;
}

// Mock HTTP requests for Turnstile verification
$turnstile_success = true;
function wp_remote_post($url, $args = []) {
    global $turnstile_success;
    return [
        'body' => json_encode(['success' => $turnstile_success])
    ];
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public $code; public $message;
        public function __construct($code = '', $message = '') { $this->code = $code; $this->message = $message; }
    }
}
function is_wp_error($thing) {
    return $thing instanceof WP_Error;
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
    #[\AllowDynamicProperties]
    class OsBookingModel {
        public $id = 777;
        public $booking_code = 'REF777';
        public $service_id = 7;
        public $customer_id = 202;
        public $location_id = 303;
        public $start_date = '2026-07-15';
        public $start_time = 900;
        public $end_time = 945;
        public $status = 'approved';
        public $meta = [];

        public static $query_code = '';
        
        public function __construct($id = null) {
            if ($id !== null) {
                global $mock_bookings;
                if (!empty($mock_bookings)) {
                    foreach ($mock_bookings as $b) {
                        if ($b->id == $id) {
                            $this->id = $b->id;
                            $this->booking_code = $b->booking_code;
                            $this->service_id = $b->service_id;
                            $this->customer_id = $b->customer_id;
                            $this->location_id = $b->location_id;
                            $this->start_date = $b->start_date;
                            $this->start_time = $b->start_time;
                            $this->end_time = $b->end_time;
                            $this->status = $b->status;
                            $this->meta = $b->meta;
                            break;
                        }
                    }
                }
            }
        }

        public function is_new_record() {
            return false;
        }

        public function save_meta_by_key($key, $value) {
            $this->meta[$key] = $value;
            global $mock_bookings;
            if (isset($mock_bookings[$this->booking_code])) {
                $mock_bookings[$this->booking_code]->meta[$key] = $value;
            }
            return true;
        }

        public function get_meta_by_key($key, $default = '') {
            return $this->meta[$key] ?? $default;
        }

        public function update_attributes($attrs) {
            foreach ($attrs as $k => $v) {
                $this->$k = $v;
            }
            global $mock_bookings;
            if (isset($mock_bookings[$this->booking_code])) {
                foreach ($attrs as $k => $v) {
                    $mock_bookings[$this->booking_code]->$k = $v;
                }
            }
            return true;
        }

        public function save() {
            return true;
        }

        public static $query_customer_id = 0;

        public function where($args) {
            if (isset($args['booking_code'])) {
                self::$query_code = $args['booking_code'];
            }
            if (isset($args['customer_id'])) {
                self::$query_customer_id = $args['customer_id'];
            }
            return $this;
        }

        public function set_limit($limit) {
            return $this;
        }

        public function get_results_as_models() {
            global $mock_bookings;
            if (!empty(self::$query_code)) {
                $code = self::$query_code;
                self::$query_code = ''; // reset
                if (isset($mock_bookings[$code])) {
                    return $mock_bookings[$code];
                }
                return [];
            }
            if (!empty(self::$query_customer_id)) {
                $cust_id = self::$query_customer_id;
                self::$query_customer_id = 0; // reset
                $res = [];
                foreach ($mock_bookings as $b) {
                    if ($b->customer_id == $cust_id) {
                        $res[] = $b;
                    }
                }
                return $res;
            }
            return array_values($mock_bookings);
        }
    }
}

if (!class_exists('OsServiceModel')) {
    class OsServiceModel {
        public $id;
        public $name;
        public static $query_name = '';
        
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
            if (isset($args['name'])) {
                self::$query_name = $args['name'];
            }
            return $this;
        }
        public function set_limit($limit) {
            return $this;
        }
        public function get_results_as_models() {
            if (self::$query_name === 'Player Consultation') {
                $this->id = 7;
                $this->name = 'Player Consultation';
            }
            self::$query_name = ''; // reset
            return $this;
        }
    }
}
if (!class_exists('OsCustomerModel')) {
    #[\AllowDynamicProperties]
    class OsCustomerModel {
        public $id = 202;
        public $first_name = 'John';
        public $last_name = 'Doe';
        public $email = 'parent@example.com';
        public $phone = '09171234567';
        public $status = 'active';
        public $is_guest = 0;
        public $meta = [];
        public $is_new = false;
        
        public static $query_email = '';
        public static $customers_db = [];

        public function __construct($id = null) {
            if ($id === null) {
                $this->is_new = true;
            } else {
                $this->id = $id;
            }
        }
        
        public function is_new_record() {
            return $this->is_new;
        }

        public function where($args) {
            if (isset($args['email'])) {
                self::$query_email = $args['email'];
            }
            return $this;
        }

        public function set_limit($limit) {
            return $this;
        }

        public function get_results_as_models() {
            // Check in our fake DB
            if (isset(self::$customers_db[self::$query_email])) {
                return self::$customers_db[self::$query_email];
            }
            // If default mock email requested, return the baseline mock object
            if (self::$query_email === 'parent@example.com') {
                $c = new self(202);
                $c->email = 'parent@example.com';
                $c->first_name = 'John';
                $c->last_name = 'Doe';
                $c->is_new = false;
                return $c;
            }
            return [];
        }

        public function save() {
            $this->is_new = false;
            if (empty($this->id)) {
                $this->id = rand(1000, 9999);
            }
            self::$customers_db[$this->email] = $this;
            return true;
        }

        public function save_meta_by_key($key, $value) {
            $this->meta[$key] = $value;
            return true;
        }

        public function get_meta_by_key($key, $default = '') {
            return $this->meta[$key] ?? $default;
        }
    }
}

if (!class_exists('OsOTPHelper')) {
    class OsOTPHelper {
        public static $last_sent = [];
        // Real LatePoint semantics: WP_Error on failure (truthy!), array with
        // status 'success' on verified, true-ish on sent.
        public static function generateAndSendOTP($email, $type, $method) {
            self::$last_sent = [$email, $type, $method];
            return true;
        }
        public static function verifyOTP($code, $email, $type = 'email', $method = 'email') {
            if ($code === '123456') {
                return ['status' => 'success', 'contact_value' => $email];
            }
            return new WP_Error('otp_generation_error', 'Invalid Code');
        }
    }
}

if (!class_exists('OsAuthHelper')) {
    class OsAuthHelper {
        public static $logged_in_customer = null;
        public static function authorize_customer($customer_id) {
            // Find in db
            foreach (OsCustomerModel::$customers_db as $c) {
                if ($c->id == $customer_id) {
                    self::$logged_in_customer = $c;
                    return true;
                }
            }
            // fallback
            $c = new OsCustomerModel($customer_id);
            $c->is_new = false;
            self::$logged_in_customer = $c;
            return true;
        }
        public static function logout_customer() {
            self::$logged_in_customer = null;
        }
        public static function get_logged_in_customer() {
            return self::$logged_in_customer;
        }
    }
}

if (!class_exists('OsLocationModel')) {
    class OsLocationModel {
        public $id = 303;
        public $name = 'Dasma, Makati';
        public function __construct($id = null) {}
        public function is_new_record() { return false; }
        public function should_be_active() { return $this; }
        public function get_results_as_models() {
            $venues = [[1, 'Dasma, Makati'], [2, 'Urdaneta Village'], [3, 'Corinthian Gardens']];
            $models = [];
            foreach ($venues as $v) {
                $loc = new OsLocationModel();
                $loc->id = $v[0];
                $loc->name = $v[1];
                $models[] = $loc;
            }
            return $models;
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
        public function get_meta_by_key($key, $default = '') {
            return $this->meta[$key] ?? $default;
        }
    }
}

if (!class_exists('OsStepsHelper')) {
    class OsStepsHelper {
        public static $cart_object;
    }
}

if (!class_exists('OsBookingHelper')) {
    class OsBookingHelper {
        public static $available_slots = [900, 915, 930, 945, 960, 975, 990, 1005, 1020, 1035];
        public static function is_booking_request_available($request, $options = []) {
            return in_array($request->booking->start_time, self::$available_slots, true);
        }
    }
}
}
namespace LatePoint\Misc {
    if (!class_exists('BookingRequest', false)) {
        class BookingRequest {
            public $booking;
            public static function create_from_booking_model($booking) {
                $req = new self();
                $req->booking = $booking;
                return $req;
            }
        }
    }
}

namespace {


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

function gv_assert_same($expected, $actual, $label) {
    check($label, $expected === $actual);
}

function gv_assert_false($actual, $label) {
    check($label, $actual === false);
}

function gv_assert_true($actual, $label) {
    check($label, $actual === true);
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
    gv_assert_not_contains('step_codes_in_order', $content, 'consultation script does not fight LatePoint step-order cleanup (locations step is PRO-only)');
    gv_assert_contains('OsWorkPeriodModel', $content, 'consultation script zeroes the default schedule so only venue periods drive availability');
}

// Venue triggers: one hidden LatePoint trigger per active venue + a GV venue
// chooser, since the booking__locations wizard step is a PRO addon feature.
ob_start();
gv_members_hidden_booking_trigger();
$trigger_html = ob_get_clean();
gv_assert_contains('id="gv-consult-trigger"', $trigger_html, 'hidden trigger container renders');
gv_assert_contains('selected_location="1"', $trigger_html, 'trigger preset for venue 1');
gv_assert_contains('selected_location="2"', $trigger_html, 'trigger preset for venue 2');
gv_assert_contains('selected_location="3"', $trigger_html, 'trigger preset for venue 3');
gv_assert_contains('data-gv-venue-trigger="2"', $trigger_html, 'venue trigger wrappers are addressable by location id');
gv_assert_contains('id="gv-venue-chooser"', $trigger_html, 'venue chooser dialog renders');
gv_assert_contains('data-gv-venue="3"', $trigger_html, 'venue chooser has an option per venue');
gv_assert_contains('Dasma, Makati', $trigger_html, 'venue chooser shows venue names');
gv_assert_contains('role="dialog"', $trigger_html, 'venue chooser is an accessible dialog');
gv_assert_contains('selected_service="7"', $trigger_html, 'triggers preset the consultation service');
gv_assert_contains('data-gv-venue-trigger="any"', $trigger_html, 'an any-venue trigger exists for undecided visitors');
gv_assert_contains('selected_location="any"', $trigger_html, 'any-venue trigger uses the LatePoint any-location preset');
gv_assert_contains('data-gv-venue="any"', $trigger_html, 'venue chooser offers an undecided option');
gv_assert_contains("don&#039;t have a venue yet", $trigger_html, 'undecided option is labeled clearly');
gv_assert_contains('gv-venue-loading-note', $trigger_html, 'chooser has a loading note for the modal transition');

$members_plugin = file_get_contents(__DIR__ . '/../gv-members.php');
gv_assert_same(3, substr_count($members_plugin, 'hide_summary="yes"'), 'all consultation trigger variants define hidden Summary');
gv_assert_same(4, substr_count($trigger_html, 'hide_summary="yes"'), 'all consultation triggers hide the Summary panel');

$members_asset_dir = __DIR__ . '/../gv-members/assets';
$members_js = file_get_contents($members_asset_dir . '/gv-members.js');
$members_css = file_get_contents($members_asset_dir . '/gv-members.css');
gv_assert_contains("BOOK A CONSULTATION", $members_js, 'wizard uses the consultation CTA label');
gv_assert_contains("gv-consult-day-action", $members_js, 'wizard adds the namespaced consultation action class');
gv_assert_contains(".gv-consult-day-action", $members_css, 'consultation action has dedicated CSS');
gv_assert_contains("justify-content: center", $members_css, 'consultation action is centered');
gv_assert_contains("border-radius: 10px", $members_css, 'consultation action has rounded edges');

if (file_exists($members_page_script)) {
    $content = file_get_contents($members_page_script);
    gv_assert_not_contains('TRUNCATE', $content, 'members page script does not contain TRUNCATE');
    gv_assert_contains('gv_members_portal', $content, 'members page script contains portal shortcode');
}

if (file_exists($consult_page_script)) {
    $content = file_get_contents($consult_page_script);
    gv_assert_not_contains('TRUNCATE', $content, 'consultation page script does not contain TRUNCATE');
    gv_assert_not_contains('latepoint_book_form', $content, 'consultation page script no longer installs the booking form (modal-only)');
    gv_assert_contains("'post_status' => 'draft'", $content, 'consultation page script drafts page 2982');
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

// LatePoint fires this hook as do_action('latepoint_booking_steps_contact_after',
// $customer, $booking) — customer FIRST, booking SECOND.
ob_start();
gv_members_booking_fields(null, $booking);
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
    gv_members_process_step_validation('customer', $booking, $params);
    check('validation passes valid parameters', true);
} catch (Exception $e) {
    check('validation passes valid parameters (threw ' . get_class($e) . ')', false);
}

// B. Test validation failure: Invalid Age (too low)
$params_bad_age = $params;
$params_bad_age['gv_consult']['player_age'] = '2';
try {
    gv_members_process_step_validation('customer', $booking, $params_bad_age);
    check('validation fails age < 3', false);
} catch (WpSendJsonException $e) {
    check('validation fails age < 3', $e->response['status'] === 'error' && strpos($e->response['message'], 'age') !== false);
}

// C. Test validation failure: Invalid Age (too high)
$params_bad_age_high = $params;
$params_bad_age_high['gv_consult']['player_age'] = '100';
try {
    gv_members_process_step_validation('customer', $booking, $params_bad_age_high);
    check('validation fails age > 99', false);
} catch (WpSendJsonException $e) {
    check('validation fails age > 99', $e->response['status'] === 'error' && strpos($e->response['message'], 'age') !== false);
}

// D. Test validation failure: Honeypot triggered
$params_honeypot = $params;
$params_honeypot['gv_website'] = 'im-a-bot';
try {
    gv_members_process_step_validation('customer', $booking, $params_honeypot);
    check('validation fails if honeypot has value', false);
} catch (WpSendJsonException $e) {
    check('validation fails if honeypot has value', $e->response['status'] === 'error' && $e->response['message'] === 'Please refresh and try again.');
}

// E. Test validation failure: Turnstile verification failure
$params_bad_turnstile = $params;
$turnstile_success = false;
try {
    gv_members_process_step_validation('customer', $booking, $params_bad_turnstile);
    check('validation fails if Turnstile verification fails', false);
} catch (WpSendJsonException $e) {
    check('validation fails if Turnstile verification fails', $e->response['status'] === 'error' && $e->response['message'] === 'Please complete the security check.');
}
$turnstile_success = true;

// 4. Persistence Handler Check (Priority 20)
OsStepsHelper::$cart_object = new OsCartModel();
gv_members_process_step_persistence('customer', $booking, $params);

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

// 5. Booking Created Handler Check
$new_booking = new OsBookingModel();
$new_booking->id = 777;
$new_booking->booking_code = 'REF777';
$new_booking->service_id = 7; // Player Consultation
$new_booking->status = 'approved'; // starts approved to check if it forces pending
$new_booking->meta = [];

$_POST['gv_consult'] = [
    'player_name' => 'Alex Test',
    'player_age' => '15',
    'training_interest' => 'elite',
    'contact_alt' => 'phone123',
    'note' => 'some note',
    'member_opt_in' => 'yes',
];

OsStepsHelper::$cart_object = null;
$sent_mails = [];
gv_members_booking_created_handler($new_booking);

check('handler forces pending status', $new_booking->status === 'pending');
check('handler saves player name to meta', $new_booking->meta['gv_player_name'] === 'Alex Test');
check('handler saves player age to meta', $new_booking->meta['gv_player_age'] === 15);
check('handler saves training interest to meta', $new_booking->meta['gv_training_interest'] === 'elite');
check('handler saves contact alt to meta', $new_booking->meta['gv_contact_alt'] === 'phone123');
check('handler saves note to meta', $new_booking->meta['gv_note'] === 'some note');
check('handler saves member opt-in to meta', $new_booking->meta['gv_member_opt_in'] === 'yes');
check('handler saves day request to meta', $new_booking->meta['gv_day_request'] === 'yes');
check('handler generates finalize token hash', !empty($new_booking->meta['gv_finalize_token_hash']));
check('handler generates finalize token expiry', !empty($new_booking->meta['gv_finalize_token_expires_at']));
check('handler sends parent receipt and coach notification emails', count($sent_mails) === 2);

if (count($sent_mails) === 2) {
    check('first mail is parent receipt', $sent_mails[0]['to'] === 'parent@example.com');
    check('first mail subject matches parent receipt', $sent_mails[0]['subject'] === 'GV Basketball — consultation request received');
    check('first mail contains Ref', strpos($sent_mails[0]['message'], 'REF777') !== false);
    check('first mail hides nominal time', strpos($sent_mails[0]['message'], '3:00 PM') === false);
    check('first mail contains CTA link', strpos($sent_mails[0]['message'], '/members/') !== false);

    check('second mail is coach notification', $sent_mails[1]['to'] === 'gvbasketballcoaching@gmail.com');
    check('second mail subject contains Player name', strpos($sent_mails[1]['subject'], 'Alex Test') !== false);
    check('second mail subject contains Venue name', strpos($sent_mails[1]['subject'], 'Dasma, Makati') !== false);
    check('second mail contains finalize URL', strpos($sent_mails[1]['message'], 'gv_finalize_consultation=1') !== false);
    check('second mail contains manual confirmation warning', strpos($sent_mails[1]['message'], 'does not send an automatic final confirmation') !== false);
}

// ==================== TASK 6 CONTRACT TESTS ====================

global $mock_bookings;
$mock_bookings = [];

function run_finalize_request($get, $post = [], $method = 'GET') {
    $_GET = $get;
    $_POST = $post;
    $_SERVER['REQUEST_METHOD'] = $method;
    
    ob_start();
    gv_members_handle_finalize_request();
    return ob_get_clean();
}

// Stub mock pending booking
$pending_booking = new OsBookingModel();
$pending_booking->id = 888;
$pending_booking->booking_code = 'XYZ888';
$pending_booking->service_id = 7; // Player Consultation
$pending_booking->customer_id = 202;
$pending_booking->location_id = 303;
$pending_booking->start_date = '2026-07-15';
$pending_booking->start_time = 900;
$pending_booking->status = 'pending';
$pending_booking->meta = [
    'gv_player_name' => 'Gino Junior',
    'gv_player_age' => '8',
    'gv_training_interest' => 'private',
    'gv_finalize_token_hash' => gv_members_token_hash('valid_token_123'),
    'gv_finalize_token_expires_at' => time() + 3600,
    'gv_finalize_token_used_at' => '',
    'gv_finalized_at' => '',
];

$mock_bookings['XYZ888'] = $pending_booking;

// Test A: Candidate times count is 10
gv_assert_same(10, count(gv_members_candidate_start_times()), 'assert ten candidate starts');

// Test B: Invalid token fails GET and reveals no PII
$out = run_finalize_request(['booking_code' => 'XYZ888', 'token' => 'invalid_token']);
gv_assert_contains('Invalid or expired finalization token', $out, 'invalid token fails');
gv_assert_not_contains('Gino Junior', $out, 'invalid token reveals no PII');
gv_assert_not_contains('parent@example.com', $out, 'invalid token reveals no parent email');

// Test C: Expired token fails GET
$pending_booking->meta['gv_finalize_token_expires_at'] = time() - 10;
$out = run_finalize_request(['booking_code' => 'XYZ888', 'token' => 'valid_token_123']);
gv_assert_contains('Invalid or expired finalization token', $out, 'expired token fails');
$pending_booking->meta['gv_finalize_token_expires_at'] = time() + 3600; // restore

// Test D: Non-consultation booking fails GET
$pending_booking->service_id = 999; // non-consultation
$out = run_finalize_request(['booking_code' => 'XYZ888', 'token' => 'valid_token_123']);
gv_assert_contains('Invalid or expired finalization token', $out, 'non-consultation booking fails');
$pending_booking->service_id = 7; // restore

// Test E: Non-pending booking fails GET
$pending_booking->status = 'cancelled';
$out = run_finalize_request(['booking_code' => 'XYZ888', 'token' => 'valid_token_123']);
gv_assert_contains('Invalid or expired finalization token', $out, 'non-pending status fails');
$pending_booking->status = 'pending'; // restore

// Test F: GET has no update call
$status_before = $pending_booking->status;
$time_before = $pending_booking->start_time;
$out = run_finalize_request(['booking_code' => 'XYZ888', 'token' => 'valid_token_123']);
gv_assert_same($status_before, $pending_booking->status, 'GET does not change status');
gv_assert_same($time_before, $pending_booking->start_time, 'GET does not change start time');
gv_assert_contains('Gino Junior', $out, 'GET reveals player name for valid token');
gv_assert_contains('parent@example.com', $out, 'GET reveals parent email for valid token');

// Test G: POST requires nonce
$out = run_finalize_request(['booking_code' => 'XYZ888', 'token' => 'valid_token_123'], ['selected_time' => 960], 'POST');
gv_assert_contains('Security check failed', $out, 'POST fails without nonce');

// Test H: POST requires availability
OsBookingHelper::$available_slots = [900, 915]; // only these are available
$out = run_finalize_request(
    ['booking_code' => 'XYZ888', 'token' => 'valid_token_123'],
    ['gv_finalize_nonce' => 'valid-nonce', 'selected_time' => 960],
    'POST'
);
gv_assert_contains('The selected slot is no longer available', $out, 'POST fails if slot not available');

// Test I: Valid POST approves booking once
OsBookingHelper::$available_slots = [900, 915, 960]; // 960 now available
global $wpdb; // mock database
$out = run_finalize_request(
    ['booking_code' => 'XYZ888', 'token' => 'valid_token_123'],
    ['gv_finalize_nonce' => 'valid-nonce', 'selected_time' => 960],
    'POST'
);

gv_assert_contains('Booking Updated', $out, 'valid POST displays success');
gv_assert_contains('parent@example.com', $out, 'valid POST displays parent email');
gv_assert_same('approved', $pending_booking->status, 'valid POST approves booking');
gv_assert_same(960, $pending_booking->start_time, 'valid POST sets correct start time');
gv_assert_same(1005, $pending_booking->end_time, 'valid POST sets correct end time');
gv_assert_same('2026-07-15 08:00:00', $pending_booking->start_datetime_utc, 'valid POST sets correct start UTC time');
gv_assert_same('2026-07-15 08:45:00', $pending_booking->end_datetime_utc, 'valid POST sets correct end UTC time');

// Check used metadata is set
gv_assert_true(!empty($pending_booking->meta['gv_finalize_token_used_at']), 'token marked used');

// Test J: Repeat GET or POST loads read-only approved branch
$out = run_finalize_request(['booking_code' => 'XYZ888', 'token' => 'valid_token_123']);
gv_assert_contains('Consultation Finalized', $out, 'repeat GET shows confirmed read-only title');
gv_assert_contains('This consultation booking has already been finalized', $out, 'repeat GET shows confirmed notice');
gv_assert_contains('4:00 PM', $out, 'confirmed view displays selected time read-only');

// ==================== TASK 7 CONTRACT TESTS ====================

function run_ajax_action($action, $params = []) {
    $_POST = $params;
    $_POST['action'] = $action;
    try {
        if ($action === 'gv_otp_request') {
            gv_otp_request_handler();
        } elseif ($action === 'gv_otp_verify') {
            gv_otp_verify_handler();
        }
        return ['success' => null, 'data' => null];
    } catch (WpSendJsonException $e) {
        return $e->response;
    }
}

// Check AJAX actions are registered
$has_otp_req = false;
$has_otp_ver = false;
if (isset($registered_actions['wp_ajax_gv_otp_request'])) $has_otp_req = true;
if (isset($registered_actions['wp_ajax_nopriv_gv_otp_request'])) $has_otp_req = true;
if (isset($registered_actions['wp_ajax_gv_otp_verify'])) $has_otp_ver = true;
if (isset($registered_actions['wp_ajax_nopriv_gv_otp_verify'])) $has_otp_ver = true;

check('auth: logged-out request AJAX actions registered', $has_otp_req);
check('auth: logged-out verify AJAX actions registered', $has_otp_ver);

// Test A: Request nonce check
$res = run_ajax_action('gv_otp_request', []);
check('auth: request nonce check fails without nonce', $res['success'] === false && strpos($res['data']['message'], 'Security check') !== false);

$res = run_ajax_action('gv_otp_request', ['nonce' => 'invalid-nonce', 'email' => 'test@example.com']);
check('auth: request nonce check fails with invalid nonce', $res['success'] === false && strpos($res['data']['message'], 'Security check') !== false);

// Test B: Verify nonce check
$res = run_ajax_action('gv_otp_verify', []);
check('auth: verify nonce check fails without nonce', $res['success'] === false && strpos($res['data']['message'], 'Security check') !== false);

// Test C: Valid OTP request returns generic success
$res = run_ajax_action('gv_otp_request', ['nonce' => 'valid-nonce', 'email' => 'newuser@example.com']);
check('auth: request success with generic response', $res['success'] === true && strpos($res['data']['message'], 'six-digit code') !== false);

// Test D: IP rate limits (transient count check)
global $gv_transients;
// Reset transients
$gv_transients = [];
// Trigger email rate limit (max 5 sends/email/hour)
for ($i = 0; $i < 5; $i++) {
    $res = run_ajax_action('gv_otp_request', ['nonce' => 'valid-nonce', 'email' => 'limit_email@example.com']);
}
$res_limit = run_ajax_action('gv_otp_request', ['nonce' => 'valid-nonce', 'email' => 'limit_email@example.com']);
check('auth: hashed rate limits for email triggered', $res_limit['success'] === false && strpos($res_limit['data']['message'], 'Too many requests') !== false);

// Test E: Verify OTP for unknown email creates ONE customer
$gv_transients = [];
OsCustomerModel::$customers_db = []; // Clear DB
$verify_res = run_ajax_action('gv_otp_verify', [
    'nonce' => 'valid-nonce',
    'email' => 'newuser@example.com',
    'otp' => '123456'
]);
check('auth: verify success redirect status', $verify_res['success'] === true);
check('auth: one unknown-email customer created in DB', isset(OsCustomerModel::$customers_db['newuser@example.com']));
if (isset(OsCustomerModel::$customers_db['newuser@example.com'])) {
    $cust = OsCustomerModel::$customers_db['newuser@example.com'];
    check('auth: created customer is active', $cust->status === 'active');
    check('auth: created customer is non-guest', $cust->is_guest === 0);
    check('auth: created customer email matches', $cust->email === 'newuser@example.com');
}

// Test F: Repeat login does not duplicate the customer
$customer_count_before = count(OsCustomerModel::$customers_db);
$verify_res_repeat = run_ajax_action('gv_otp_verify', [
    'nonce' => 'valid-nonce',
    'email' => 'newuser@example.com',
    'otp' => '123456'
]);
check('auth: repeat login success', $verify_res_repeat['success'] === true);
check('auth: no duplicate customer on repeat', count(OsCustomerModel::$customers_db) === $customer_count_before);

// ==================== TASK 8 CONTRACT TESTS ====================

// Set up two customer fixtures
$cust_a = new OsCustomerModel(101);
$cust_a->email = 'cust-a@example.com';
$cust_a->first_name = 'Alice';
$cust_a->last_name = 'Smith';
$cust_a->is_new = false;
OsCustomerModel::$customers_db['cust-a@example.com'] = $cust_a;

$cust_b = new OsCustomerModel(102);
$cust_b->email = 'cust-b@example.com';
$cust_b->first_name = 'Bob';
$cust_b->last_name = 'Jones';
$cust_b->is_new = false;
OsCustomerModel::$customers_db['cust-b@example.com'] = $cust_b;

// Set up bookings in mock bookings database
global $mock_bookings;
$mock_bookings = [];

// Booking 1: Customer A, pending
$b1 = new OsBookingModel(1);
$b1->id = 1;
$b1->booking_code = 'REFA1';
$b1->customer_id = 101;
$b1->service_id = 7; // Player Consultation
$b1->status = 'pending';
$b1->start_date = '2026-07-15';
$b1->start_time = 900;
$b1->meta = [
    'gv_player_name' => 'Athlete One',
    'gv_player_age' => '10',
    'gv_training_interest' => 'private',
    'gv_note' => 'Needs work on dribbling',
];
$mock_bookings['REFA1'] = $b1;

// Booking 2: Customer A, approved
$b2 = new OsBookingModel(2);
$b2->id = 2;
$b2->booking_code = 'REFA2';
$b2->customer_id = 101;
$b2->service_id = 7; // Player Consultation
$b2->status = 'approved';
$b2->start_date = '2026-07-20';
$b2->start_time = 960; // 16:00 -> 4:00 PM
$b2->meta = [
    'gv_player_name' => 'Athlete One',
    'gv_player_age' => '10',
    'gv_training_interest' => 'private',
];
$mock_bookings['REFA2'] = $b2;

// Booking 3: Customer B, approved (ownership isolation check)
$b3 = new OsBookingModel(3);
$b3->id = 3;
$b3->booking_code = 'REFB3';
$b3->customer_id = 102;
$b3->service_id = 7;
$b3->status = 'approved';
$b3->start_date = '2026-07-25';
$b3->start_time = 900;
$b3->meta = [
    'gv_player_name' => 'Athlete Two',
    'gv_player_age' => '12',
    'gv_training_interest' => 'elite',
];
$mock_bookings['REFB3'] = $b3;

// Authorize Customer A
OsAuthHelper::authorize_customer(101);

// Render portal for Customer A
$portal_html = gv_members_portal_render();

// Assertions
check('portal: renders requests container', strpos($portal_html, 'gv-portal-container') !== false);

// 1. Ownership isolation
gv_assert_contains('REFA1', $portal_html, 'portal shows customer A pending request');
gv_assert_contains('REFA2', $portal_html, 'portal shows customer A approved request');
gv_assert_not_contains('REFB3', $portal_html, 'portal hides customer B request (ownership isolation)');

// 2. Pending time suppression vs Approved exact time
// Pending REFA1 date: July 15, 2026, time 3:00 PM must be suppressed
gv_assert_contains('July 15, 2026', $portal_html, 'portal contains requested day for pending');
gv_assert_not_contains('July 15, 2026 at 3:00 PM', $portal_html, 'portal suppresses nominal time for pending requests');

// Approved REFA2 exact time: July 20, 2026 at 4:00 PM
gv_assert_contains('July 20, 2026 at 4:00 PM', $portal_html, 'portal contains exact time for approved requests');

// 3. Newest-first ordering in timeline
$refa1_pos = strpos($portal_html, 'REFA1');
$refa2_pos = strpos($portal_html, 'REFA2');
check('portal: requests listed newest-first (REFA2 with ID 2 before REFA1 with ID 1)', $refa2_pos < $refa1_pos);

// 4. Confirmed sessions excludes pending/cancelled
// Let's inspect the confirmed sessions output by checking if the template logic has them split.
// In the confirmed sessions tab block:
// Upcoming Confirmed Sessions will contain REFA2 but NOT REFA1.
$sessions_tab_start = strpos($portal_html, 'id="gv-tab-sessions"');
$sessions_tab_end = strpos($portal_html, 'id="gv-tab-profile"');
$sessions_html = substr($portal_html, $sessions_tab_start, $sessions_tab_end - $sessions_tab_start);

gv_assert_contains('REFA2', $sessions_html, 'confirmed sessions contains approved booking REFA2');
gv_assert_not_contains('REFA1', $sessions_html, 'confirmed sessions excludes pending booking REFA1');

// 5. Unique player reuse
gv_assert_contains('gv-prior-players-json', $portal_html, 'portal outputs player JSON');
gv_assert_contains('Athlete One', $portal_html, 'portal player list contains Athlete One name');

// 6. Booking reference in change email
$change_mailto = gv_members_change_mailto('REFA2');
gv_assert_contains('mailto:gvbasketballcoaching@gmail.com', $change_mailto, 'change email points to coach');
gv_assert_contains('REFA2', $change_mailto, 'change email contains booking reference');

// ==================== TASK 9 CONTRACT TESTS ====================
// The old email-only consultation modal is retired: gv-request-form.php keeps
// compatibility helpers only, and every public entry point routes through the
// native LatePoint wizard modal (data-gv-consultation; /book-a-consultation/ retired).

$legacy_plugin_src = file_get_contents(__DIR__ . '/../gv-request-form.php');
check('legacy plugin: no admin_post_nopriv_gv_request_form registration',
    strpos($legacy_plugin_src, 'admin_post_nopriv_gv_request_form') === false);
check('legacy plugin: no wp_footer modal hook',
    strpos($legacy_plugin_src, 'wp_footer') === false);
check('legacy plugin: no gv_open_modal redirect',
    strpos($legacy_plugin_src, 'gv_open_modal') === false);
check('legacy plugin: no [gv_request_form] shortcode registration',
    strpos($legacy_plugin_src, 'add_shortcode') === false);

$legacy_page_sources = [
    'training-programs.html'       => __DIR__ . '/../../pages/training-programs.html',
    'deploy-training-programs.php' => __DIR__ . '/../../scripts/deploy-training-programs.php',
    'build-functional.php'         => __DIR__ . '/../../scripts/build-functional.php',
];
foreach ($legacy_page_sources as $name => $path) {
    $src = file_exists($path) ? file_get_contents($path) : '';
    check("source $name: exists", $src !== '');
    gv_assert_not_contains('[gv_request_form]', $src, "source $name: no [gv_request_form] shortcode");
    gv_assert_not_contains('data-gv-open-modal', $src, "source $name: no data-gv-open-modal trigger");
}

// Training Programs CTAs are modal-only: data-gv-consultation with no
// /book-a-consultation/ page link.
$tp_src = file_get_contents(__DIR__ . '/../../pages/training-programs.html');
gv_assert_contains('data-gv-consultation', $tp_src,
    'training-programs CTAs carry data-gv-consultation');
gv_assert_not_contains('href="/book-a-consultation/"', $tp_src,
    'training-programs CTAs no longer link to the retired /book-a-consultation/ page');

// Header/footer source templates: member links go to /members/, consultation
// CTAs are modal-only (data-gv-consultation, no /book-a-consultation/ link).
$header_src = file_get_contents(__DIR__ . '/../../templates/header.html');
$footer_src = file_get_contents(__DIR__ . '/../../templates/footer.html');

gv_assert_contains('href="/members/"', $header_src, 'header: member link points to /members/');
gv_assert_not_contains('href="/booking/"', $header_src, 'header: no legacy /booking/ member link');
gv_assert_contains('data-gv-consultation', $header_src,
    'header: consultation CTA carries data-gv-consultation');
gv_assert_not_contains('href="/book-a-consultation/"', $header_src,
    'header: no link to the retired /book-a-consultation/ page');
gv_assert_not_contains('data-gv-open-modal', $header_src, 'header: no data-gv-open-modal trigger');

gv_assert_contains('href="/members/"', $footer_src, 'footer: member link points to /members/');
gv_assert_not_contains('href="/booking/"', $footer_src, 'footer: no legacy /booking/ member link');
gv_assert_contains('data-gv-consultation', $footer_src,
    'footer: consultation CTA carries data-gv-consultation');
gv_assert_not_contains('href="/book-a-consultation/"', $footer_src,
    'footer: no link to the retired /book-a-consultation/ page');
gv_assert_not_contains('data-gv-open-modal', $footer_src, 'footer: no data-gv-open-modal trigger');

// The retired /book-a-consultation path 301s via gv_members_legacy_redirect.
$members_plugin_src = file_get_contents(__DIR__ . '/../gv-members.php');
gv_assert_contains("'/book-a-consultation'", $members_plugin_src,
    'gv-members.php: legacy redirect covers /book-a-consultation');

echo $failures ? "\n$failures FAILED\n" : "\nALL PASS\n";
exit($failures ? 1 : 0);
}
