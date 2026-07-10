<?php
/**
 * Retire page-2982 (Consultation landing page).
 * Booking is modal-only now: every "Book a Consultation" CTA opens the native
 * LatePoint wizard via gv-members.js, and /book-a-consultation/ 301s to the
 * Training Programs page (see gv_members_legacy_redirect() in gv-members.php).
 * This script idempotently drafts page 2982 so it no longer resolves publicly.
 * Run via: wp eval-file configure-consultation-page.php
 */

if (!function_exists('wp_update_post')) {
    fwrite(STDERR, "wp_update_post helper not found. Make sure this runs under wp eval-file.\n");
    exit(1);
}

$page_id = 2982;
$post = get_post($page_id);
if (!$post) {
    fwrite(STDERR, "Page 2982 is absent.\n");
    exit(1);
}

if ($post->post_status === 'draft') {
    echo "page_2982_status=draft (already drafted)\n";
    exit(0);
}

$result = wp_update_post(array(
    'ID'          => $page_id,
    'post_status' => 'draft',
));

if (!$result || is_wp_error($result)) {
    fwrite(STDERR, "Failed to draft page 2982.\n");
    exit(1);
}

echo "page_2982_status=draft\n";
