<?php
// Framework-free CLI test. Run: php build/mu-plugins/tests/test-gv-members-emails.php

define('ABSPATH', __DIR__);
define('DAY_IN_SECONDS', 86400);

// Stub WordPress functions or constants if needed
if (!class_exists('OsBookingModel')) {
    class OsBookingModel {
        public $id = 101;
        public $booking_code = 'ABC123';
        public $service_id = 7;
        public $customer_id = 202;
        public $location_id = 303;
        public $start_date = '2026-07-13';
        public $start_time = 900; // 3:00 PM nominal
        public $meta = [];

        public function get_meta_by_key($key, $default = '') {
            return $this->meta[$key] ?? $default;
        }
    }
}

if (!class_exists('OsCustomerModel')) {
    class OsCustomerModel {
        public $id = 202;
        public $first_name = 'John';
        public $last_name = 'Doe';
        public $email = 'parent@example.com';
        public $phone = '09171234567';
        public function __construct($id = null) {}
    }
}

if (!class_exists('OsLocationModel')) {
    class OsLocationModel {
        public $id = 303;
        public $name = 'Dasma, Makati';
        public function __construct($id = null) {}
    }
}

function wp_salt($scheme = 'auth') {
    return 'test_wp_salt_key_for_' . $scheme;
}
function esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_url($s) { return (string)$s; }
function home_url($p = '') { return 'https://example.test' . $p; }

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

function gv_assert_same($expected, $actual, $label) {
    check($label, $expected === $actual);
}

function gv_assert_false($actual, $label) {
    check($label, $actual === false);
}

function gv_assert_true($actual, $label) {
    check($label, $actual === true);
}

function gv_assert_contains($needle, $haystack, $label) {
    check($label, strpos($haystack, $needle) !== false);
}

function gv_assert_not_contains($needle, $haystack, $label) {
    check($label, strpos($haystack, $needle) === false);
}

// Load core helpers first, then emails library
require_once __DIR__ . '/../gv-members/core.php';
require_once __DIR__ . '/../gv-members/emails.php';

$sample = [
    'booking_code' => 'ABC123',
    'player_name' => 'Alex Victorino',
    'player_age' => 12,
    'training_interest' => 'small_group',
    'contact_alt' => '@parent',
    'note' => 'First consultation',
    'venue_name' => 'Dasma, Makati',
    'day' => 'Monday, July 13, 2026',
    'parent_name' => 'John Doe',
    'parent_email' => 'parent@example.com',
    'parent_phone' => '09171234567',
];

$meta = [
    'member_opt_in' => 'yes',
];

$receipt = gv_members_parent_receipt_html($sample, ['member_opt_in' => 'no']);
gv_assert_contains('Request received', $receipt, 'receipt state');
gv_assert_contains('exact time', $receipt, 'timing copy');
gv_assert_not_contains('3:00 PM', $receipt, 'nominal time hidden');
gv_assert_not_contains('/members/', $receipt, 'no promotion');

$opted_in = gv_members_parent_receipt_html($sample, ['member_opt_in' => 'yes']);
gv_assert_contains('/members/', $opted_in, 'promotion shown');

$coach = gv_members_coach_request_html($sample, $meta, 'https://example.invalid/finalize');
foreach (['1. Review', '2. Contact', '3. Agree', '4. Open', '5. Select', '6. Personally send'] as $step) {
    gv_assert_contains($step, $coach, $step);
}
gv_assert_contains('does not send an automatic final confirmation', $coach, 'manual final warning');

// Test view-model extraction
$booking = new OsBookingModel();
$booking->meta = [
    'gv_player_name' => 'Alex Victorino',
    'gv_player_age' => '12',
    'gv_training_interest' => 'small_group',
    'gv_contact_alt' => '@parent',
    'gv_note' => 'First consultation',
    'gv_member_opt_in' => 'yes',
];
$data = gv_members_booking_email_data($booking);
gv_assert_same('ABC123', $data['booking_code'], 'booking code extracted');
gv_assert_same('Alex Victorino', $data['player_name'], 'player name extracted');
gv_assert_same(12, $data['player_age'], 'player age extracted');
gv_assert_same('small_group', $data['training_interest'], 'interest extracted');
gv_assert_same('@parent', $data['contact_alt'], 'contact alt extracted');
gv_assert_same('First consultation', $data['note'], 'note extracted');
gv_assert_same('yes', $data['member_opt_in'], 'opt_in extracted');
gv_assert_same('Dasma, Makati', $data['venue_name'], 'venue name extracted');
gv_assert_same('Monday, July 13, 2026', $data['day'], 'day extracted');
gv_assert_same('John Doe', $data['parent_name'], 'parent name extracted');
gv_assert_same('parent@example.com', $data['parent_email'], 'parent email extracted');
gv_assert_same('09171234567', $data['parent_phone'], 'parent phone extracted');

echo $failures ? "\n$failures FAILED\n" : "\nPASS: GV Members emails\n";
exit($failures ? 1 : 0);
