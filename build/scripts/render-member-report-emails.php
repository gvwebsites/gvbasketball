<?php
// Render the three GV Members / consultation email templates to static HTML
// for the client report screenshots. Uses fictional identities and a future
// sample date. NEVER sends mail — writes files only.
//
// Run with: wp eval-file build/scripts/render-member-report-emails.php
//
// See docs/superpowers/plans/2026-07-10-members-self-service-consultation-merged.md
// Task 13, Step 2.

$missing = array();
foreach (array('gv_otp_email_html', 'gv_members_parent_receipt_html', 'gv_members_coach_request_html') as $fn) {
    if (!function_exists($fn)) {
        $missing[] = $fn;
    }
}
if (!empty($missing)) {
    fwrite(STDERR, "Missing required function(s): " . implode(', ', $missing) . ". Are the gv-members and gv-otp-email mu-plugins loaded?\n");
    exit(1);
}

$out_dir = rtrim(getenv('HOME'), '/') . '/gv-report-emails/';
if (!is_dir($out_dir) && !mkdir($out_dir, 0755, true) && !is_dir($out_dir)) {
    fwrite(STDERR, "Could not create output directory: {$out_dir}\n");
    exit(1);
}

// --- Fictional sample data -------------------------------------------------

$sample_day = 'Saturday, August 15, 2026'; // future sample date, matches gv_members_booking_email_data() day format

// Minimal booking_data view-model matching gv_members_booking_email_data()'s
// return shape (build/mu-plugins/gv-members/emails.php:11-57). Both email
// builders only read from this associative array, never a live OsBookingModel.
$booking_data = array(
    'booking_code'      => 'GV-SAMPLE-482731',
    'player_name'       => 'Miguel Santos',
    'player_age'        => 12,
    'training_interest' => 'small_group', // valid key from gv_members_interest_options()
    'contact_alt'       => '@miguelsantosbball',
    'note'              => "Miguel is left-handed and has played rec league for 2 years.",
    'member_opt_in'     => 'yes',
    'venue_name'        => 'Dasma Makati',
    'day'               => $sample_day,
    'parent_name'       => 'Maria Santos',
    'parent_email'      => 'maria@example.invalid',
    'parent_phone'      => '+63 917 000 0000',
);

// $meta mirrors the raw submitted-form $data array passed alongside
// $booking_email_data in build/mu-plugins/gv-members/booking.php:332,343.
// gv_members_parent_receipt_html() reads $meta['member_opt_in'] as an
// override of $booking_data['member_opt_in']; gv_members_coach_request_html()
// accepts $meta but does not read from it directly.
$meta = array(
    'member_opt_in' => 'yes',
);

$finalize_url = 'https://example.invalid/members/finalize/?gv_finalize_consultation=1&booking_code=GV-SAMPLE-482731&token=sample-finalize-token';

// --- Render ------------------------------------------------------------

$files = array();

$files['member-otp-email.html'] = gv_otp_email_html('482731');
$files['consultation-parent-receipt-email.html'] = gv_members_parent_receipt_html($booking_data, $meta);
$files['consultation-coach-email.html'] = gv_members_coach_request_html($booking_data, $meta, $finalize_url);

foreach ($files as $filename => $html) {
    $path = $out_dir . $filename;
    if (file_put_contents($path, $html) === false) {
        fwrite(STDERR, "Failed to write {$path}\n");
        exit(1);
    }
    echo $path . "\n";
}
