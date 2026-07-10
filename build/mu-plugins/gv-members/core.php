<?php
/**
 * GV Members — Pure Domain Helpers & Validation
 */

defined('ABSPATH') || exit;

defined('GV_MEMBERS_FINALIZE_TTL') || define('GV_MEMBERS_FINALIZE_TTL', 30 * DAY_IN_SECONDS);

/**
 * Normalize email string.
 */
function gv_members_normalize_email($email) {
    return strtolower(trim((string) $email));
}

/**
 * Returns available training interest options.
 */
function gv_members_interest_options() {
    return [
        'private'     => 'Private',
        'small_group' => 'Small Group',
        'elite'       => 'Elite Performance'
    ];
}

/**
 * Returns label for a training interest.
 */
function gv_members_interest_label($key) {
    $all = gv_members_interest_options();
    return $all[$key] ?? '';
}

/**
 * Returns label for booking status.
 */
function gv_members_status_label($status) {
    if ($status === 'approved') {
        return 'Confirmed';
    }
    if ($status === 'pending') {
        return 'Submitted';
    }
    return ucfirst((string) $status);
}

/**
 * Returns candidate start times (in military minutes, e.g., 900 is 15:00/3:00 PM).
 * range(900, 1035, 15) -> 900, 915, 930, 945, 960, 975, 990, 1005, 1020, 1035.
 */
function gv_members_candidate_start_times() {
    return range(900, 1035, 15);
}

/**
 * Hashes a token securely using HMAC.
 */
function gv_members_token_hash($raw) {
    $salt = function_exists('wp_salt') ? wp_salt('auth') : 'gv-members-test-salt';
    return hash_hmac('sha256', (string) $raw, $salt);
}

/**
 * Secure string comparison.
 */
function gv_members_secure_equals($known, $given) {
    return is_string($known) && is_string($given) && hash_equals($known, $given);
}

/**
 * Check if token is expired.
 */
function gv_members_token_expired($expires, $now = null) {
    $now = $now ?? time();
    return (int) $expires < (int) $now;
}

/**
 * Generates change mailto link.
 */
function gv_members_change_mailto($reference) {
    return 'mailto:gvbasketballcoaching@gmail.com?subject=' . rawurlencode('GV Basketball booking ' . $reference) .
        '&body=' . rawurlencode('Hi Coach Gino, I need to request a change for booking ' . $reference . '.');
}

/**
 * Validates consultation request payload.
 *
 * Requirements:
 * - name: required, max 100 chars
 * - age: required, between 3 and 99 (inclusive)
 * - interest: required, in interest options
 * - contact_alt: optional, max 120 chars
 * - note: optional, max 500 chars
 * - member_opt_in: yes/no, defaults to no
 *
 * Returns ['errors' => [...], 'data' => [...]]
 */
function gv_members_validate_payload($payload) {
    $errors = [];
    $data = [];

    // Player Name
    $player_name = isset($payload['player_name']) ? trim((string)$payload['player_name']) : '';
    if ($player_name === '') {
        $errors['player_name'] = 'Player name is required.';
    } elseif (mb_strlen($player_name) > 100) {
        $errors['player_name'] = 'Player name cannot exceed 100 characters.';
    } else {
        $data['player_name'] = $player_name;
    }

    // Player Age
    $player_age_raw = isset($payload['player_age']) ? trim((string)$payload['player_age']) : '';
    if ($player_age_raw === '') {
        $errors['player_age'] = 'Player age is required.';
    } else {
        $player_age = filter_var($player_age_raw, FILTER_VALIDATE_INT);
        if ($player_age === false || $player_age < 3 || $player_age > 99) {
            $errors['player_age'] = 'Player age must be between 3 and 99.';
        } else {
            $data['player_age'] = $player_age;
        }
    }

    // Training Interest
    $interest = isset($payload['training_interest']) ? trim((string)$payload['training_interest']) : '';
    $valid_interests = gv_members_interest_options();
    if ($interest === '') {
        $errors['training_interest'] = 'Training interest is required.';
    } elseif (!array_key_exists($interest, $valid_interests)) {
        $errors['training_interest'] = 'Invalid training interest selected.';
    } else {
        $data['training_interest'] = $interest;
    }

    // Contact Alt (e.g. Phone, Instagram)
    $contact_alt = isset($payload['contact_alt']) ? trim((string)$payload['contact_alt']) : '';
    if (mb_strlen($contact_alt) > 120) {
        $errors['contact_alt'] = 'Contact info cannot exceed 120 characters.';
    } else {
        $data['contact_alt'] = $contact_alt;
    }

    // Note
    $note = isset($payload['note']) ? trim((string)$payload['note']) : '';
    if (mb_strlen($note) > 500) {
        $errors['note'] = 'Note cannot exceed 500 characters.';
    } else {
        $data['note'] = $note;
    }

    // Member Opt-in
    $opt_in = isset($payload['member_opt_in']) ? trim((string)$payload['member_opt_in']) : 'no';
    $opt_in = ($opt_in === 'yes' || $opt_in === '1' || $opt_in === 'on' || $opt_in === true) ? 'yes' : 'no';
    $data['member_opt_in'] = $opt_in;

    // Day Request is yes
    $data['day_request'] = 'yes';

    return [
        'errors' => $errors,
        'data'   => $errors ? [] : $data
    ];
}
