<?php
// Enable passwordless email-OTP customer auth for the Member Login (/booking/).
// Idempotent: upserts LatePoint settings (DELETE-then-INSERT per name).
// See docs/superpowers/specs/2026-06-29-member-signup-verified-email-design.md
global $wpdb;
$p = $wpdb->prefix;

$settings = array(
    // Required so LatePoint can actually send the OTP email (Default WP Mailer → FluentSMTP).
    'notifications_email_processor'                => 'wp_mail',
    // Part A — passwordless email-OTP: every signup has a verified email.
    'selected_customer_authentication_method'     => 'otp',
    'default_customer_authentication_method'       => 'otp',
    'selected_customer_authentication_field_type'  => 'email',
    // Part B — land logged-in members on the branded /members/ page.
    'page_url_customer_dashboard'                  => '/members/',
    'page_url_customer_login'                       => '/members/',
);

foreach ($settings as $name => $value) {
    $wpdb->query($wpdb->prepare("DELETE FROM {$p}latepoint_settings WHERE name=%s", $name));
    $wpdb->query($wpdb->prepare("INSERT INTO {$p}latepoint_settings (name, value) VALUES (%s, %s)", $name, $value));
}

// Verify
foreach (array_keys($settings) as $name) {
    $v = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$p}latepoint_settings WHERE name=%s", $name));
    echo "{$name} = {$v}\n";
}
