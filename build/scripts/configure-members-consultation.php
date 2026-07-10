<?php
/**
 * Idempotent targeted LatePoint consultation configuration.
 * Run via: wp eval-file configure-members-consultation.php
 */

// Safety checks
if (!class_exists('LatePoint\Misc\BookingRequest') && !class_exists('OsServiceModel')) {
    fwrite(STDERR, "LatePoint classes not found. Make sure this runs under wp eval-file.\n");
    exit(1);
}

// Find existing Player Consultation service
$service = (new OsServiceModel())->where(['name' => 'Player Consultation'])->set_limit(1)->get_results_as_models();
if (!$service || $service->is_new_record()) {
    fwrite(STDERR, "Player Consultation not found\n");
    exit(1);
}

// Set duration and timeblock interval, and default booking status to pending
$service->duration = 45;
$service->timeblock_interval = 180;
$service->override_default_booking_status = LATEPOINT_BOOKING_STATUS_PENDING;

if (!$service->save()) {
    fwrite(STDERR, implode(', ', (array) $service->get_error_messages()) . "\n");
    exit(1);
}

// Update relevant LatePoint settings
$settings = [
    'notifications_email_processor' => 'wp_mail',
    'selected_customer_authentication_method' => 'otp',
    'default_customer_authentication_method' => 'otp',
    'selected_customer_authentication_field_type' => 'email',
    'require_otp_for_new_contacts' => 'on',
    'page_url_customer_dashboard' => '/members/',
    'page_url_customer_login' => '/members/',
    'enable_payments_local' => 'off',
];

foreach ($settings as $name => $value) {
    OsSettingsHelper::save_setting_by_name($name, $value);
}

// Set up customer fields: first_name, last_name, email active and required.
// phone, notes inactive.
$customer_fields = OsSettingsHelper::get_default_fields_for_customer();
foreach (['first_name', 'last_name', 'email'] as $name) {
    $customer_fields[$name]['active'] = true;
    $customer_fields[$name]['required'] = true;
}
$customer_fields['phone']['active'] = false;
$customer_fields['phone']['required'] = false;
$customer_fields['notes']['active'] = false;
$customer_fields['notes']['required'] = false;

OsSettingsHelper::save_setting_by_name('default_fields_for_customer', wp_json_encode($customer_fields));

// Hide other paid training services from the public wizard
foreach (['Private Training', 'Small Group Training', 'Elite Performance'] as $private_name) {
    $private_service = (new OsServiceModel())->where(['name' => $private_name])->set_limit(1)->get_results_as_models();
    if ($private_service && !$private_service->is_new_record()) {
        $private_service->visibility = 'hidden';
        $private_service->save();
    }
}

// NOTE on venues: the wizard's `booking__locations` step is a LatePoint PRO
// addon feature. Core strips it from the saved step order on the next request
// (cleanup_steps vs get_step_codes_with_rules), so it CANNOT be enabled here.
// Venue selection is instead handled by gv-members.php: one hidden
// [latepoint_book_button selected_location=N] trigger per active venue behind
// a GV venue chooser (see gv_members_hidden_booking_trigger). The
// `selected_location` preset sets booking.location_id (fixes "Location Id can
// not be blank") and scopes availability to that venue's 15:00-18:00 work
// period, which with timeblock_interval=180 yields exactly one public
// "Request this day" slot per day.
$steps_settings = OsSettingsHelper::get_settings_value('steps_settings', []);
if (is_array($steps_settings) && isset($steps_settings['booking__locations'])) {
    unset($steps_settings['booking__locations']);
    OsSettingsHelper::save_setting_by_name('steps_settings', $steps_settings);
}

// Disable the default (agent-wide) weekly schedule so availability comes ONLY
// from venue-specific work periods (15:00-18:00 on each venue's days).
// LatePoint falls back to the default schedule for any weekday that has no
// location-specific row, which would expose 8:00-17:00 slots (3 per day) at
// venues that are closed that day. A zero-length period (start == end) is
// LatePoint's "day off".
if (class_exists('OsWorkPeriodModel')) {
    $default_periods = (new OsWorkPeriodModel())
        ->where(['agent_id' => 0, 'service_id' => 0, 'location_id' => 0])
        ->get_results_as_models();
    if ($default_periods && !is_array($default_periods)) {
        $default_periods = [$default_periods];
    }
    foreach ((array) $default_periods as $period) {
        if ((int) $period->start_time !== 0 || (int) $period->end_time !== 0) {
            $period->start_time = 0;
            $period->end_time = 0;
            $period->save();
        }
    }
}

printf("consultation=%d duration=%d interval=%d status=%s\n", $service->id, $service->duration, $service->timeblock_interval, $service->override_default_booking_status);
