<?php
// Framework-free CLI test. Run: php build/mu-plugins/tests/test-gv-members-core.php

define('ABSPATH', __DIR__);
define('DAY_IN_SECONDS', 86400);

// Stub WordPress functions or constants if needed
function wp_salt($scheme = 'auth') {
    return 'test_wp_salt_key_for_' . $scheme;
}

require __DIR__ . '/../gv-members/core.php';

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

// 1. Valid Payload Test
$valid = gv_members_validate_payload([
    'player_name' => 'Alex Victorino',
    'player_age' => '12',
    'training_interest' => 'small_group',
    'contact_alt' => '@parent',
    'note' => 'First consultation',
    'member_opt_in' => 'yes',
]);
gv_assert_same([], $valid['errors'], 'valid request - no errors');
gv_assert_same(12, $valid['data']['player_age'], 'age normalized to integer');
gv_assert_same('Alex Victorino', $valid['data']['player_name'], 'player_name preserved');
gv_assert_same('small_group', $valid['data']['training_interest'], 'training_interest preserved');
gv_assert_same('@parent', $valid['data']['contact_alt'], 'contact_alt preserved');
gv_assert_same('First consultation', $valid['data']['note'], 'note preserved');
gv_assert_same('yes', $valid['data']['member_opt_in'], 'member_opt_in preserved');

// 2. Labels, Times, and Change Email Tests
gv_assert_same('Small Group', gv_members_interest_label('small_group'), 'interest label for small_group');
gv_assert_same('Private', gv_members_interest_label('private'), 'interest label for private');
gv_assert_same('Elite Performance', gv_members_interest_label('elite'), 'interest label for elite');
gv_assert_same('Submitted', gv_members_status_label('pending'), 'pending status label');
gv_assert_same('Confirmed', gv_members_status_label('approved'), 'approved status label');
gv_assert_same('Someother', gv_members_status_label('someother'), 'other status label');

gv_assert_same([900,915,930,945,960,975,990,1005,1020,1035], gv_members_candidate_start_times(), 'available starts');

// Token hash and secure equals
gv_assert_false(gv_members_secure_equals(gv_members_token_hash('alpha'), gv_members_token_hash('beta')), 'wrong token check');
gv_assert_true(gv_members_secure_equals(gv_members_token_hash('alpha'), gv_members_token_hash('alpha')), 'same token check');

gv_assert_contains('GV%20Basketball%20booking%20ABC123', gv_members_change_mailto('ABC123'), 'change email has booking code');

// 3. Payload Failure Assertions
// Age out of bounds: 2
$invalid_age_low = gv_members_validate_payload([
    'player_name' => 'Alex Victorino',
    'player_age' => '2',
    'training_interest' => 'small_group',
    'contact_alt' => '@parent',
    'note' => 'First consultation',
    'member_opt_in' => 'yes',
]);
check('age 2 fails validation', count($invalid_age_low['errors']) > 0);

// Age out of bounds: 100
$invalid_age_high = gv_members_validate_payload([
    'player_name' => 'Alex Victorino',
    'player_age' => '100',
    'training_interest' => 'small_group',
    'contact_alt' => '@parent',
    'note' => 'First consultation',
    'member_opt_in' => 'yes',
]);
check('age 100 fails validation', count($invalid_age_high['errors']) > 0);

// Unknown interest
$invalid_interest = gv_members_validate_payload([
    'player_name' => 'Alex Victorino',
    'player_age' => '12',
    'training_interest' => 'super_elite',
    'contact_alt' => '@parent',
    'note' => 'First consultation',
    'member_opt_in' => 'yes',
]);
check('unknown interest fails validation', count($invalid_interest['errors']) > 0);

// Name over 100 characters
$long_name = str_repeat('A', 101);
$invalid_name = gv_members_validate_payload([
    'player_name' => $long_name,
    'player_age' => '12',
    'training_interest' => 'small_group',
    'contact_alt' => '@parent',
    'note' => 'First consultation',
    'member_opt_in' => 'yes',
]);
check('name over 100 chars fails validation', count($invalid_name['errors']) > 0);

// Contact over 120 characters
$long_contact = str_repeat('C', 121);
$invalid_contact = gv_members_validate_payload([
    'player_name' => 'Alex Victorino',
    'player_age' => '12',
    'training_interest' => 'small_group',
    'contact_alt' => $long_contact,
    'note' => 'First consultation',
    'member_opt_in' => 'yes',
]);
check('contact over 120 chars fails validation', count($invalid_contact['errors']) > 0);

// Note over 500 characters
$long_note = str_repeat('N', 501);
$invalid_note = gv_members_validate_payload([
    'player_name' => 'Alex Victorino',
    'player_age' => '12',
    'training_interest' => 'small_group',
    'contact_alt' => '@parent',
    'note' => $long_note,
    'member_opt_in' => 'yes',
]);
check('note over 500 chars fails validation', count($invalid_note['errors']) > 0);

// 4. Token expiration tests
$now = time();
$future = $now + 10;
$past = $now - 10;
gv_assert_true(gv_members_token_expired($past, $now), 'expired token is detected');
gv_assert_false(gv_members_token_expired($future, $now), 'unexpired token is detected');
gv_assert_true(gv_members_token_expired($now - 30 * DAY_IN_SECONDS, $now), 'token older than 30 days is expired');
gv_assert_false(gv_members_token_expired($now + 30 * DAY_IN_SECONDS - 1, $now), 'token within 30 days is not expired');

echo $failures ? "\n$failures FAILED\n" : "\nPASS: GV Members core\n";
exit($failures ? 1 : 0);
