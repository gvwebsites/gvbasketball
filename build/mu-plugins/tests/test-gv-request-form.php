<?php
// Framework-free CLI test. Run: php build/mu-plugins/tests/test-gv-request-form.php
// Stub the WordPress functions the plugin calls at include time.
define('ABSPATH', __DIR__);
function add_action() {}
function add_shortcode() {}
function add_filter() {}
function esc_attr($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_html($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function esc_url($s){ return (string)$s; }
function home_url($p=''){ return 'https://example.test'.$p; }
function wp_create_nonce($a=''){ return 'testnonce'; }
function admin_url($p=''){ return 'https://example.test/wp-admin/'.$p; }
function sanitize_key($s){ return strtolower(preg_replace('/[^a-z0-9_]/','', (string)$s)); }
function wp_json_encode($d){ return json_encode($d); }

require __DIR__ . '/../gv-request-form.php';

$failures = 0;
function check($label, $cond) {
    global $failures;
    if ($cond) { echo "ok   - $label\n"; }
    else { echo "FAIL - $label\n"; $failures++; }
}

// --- gv_rf_locations() shape ---
$locs = gv_rf_locations();
check('has all four location keys',
    isset($locs['dasma'], $locs['urdaneta'], $locs['corinth'], $locs['any']));
check('dasma days are Mon/Wed/Thu',
    $locs['dasma']['days'] === array('Mon','Wed','Thu'));
check('urdaneta days are Fri/Sun',
    $locs['urdaneta']['days'] === array('Fri','Sun'));
check('corinth days are Sun',
    $locs['corinth']['days'] === array('Sun'));
check('any days are all 7 in order',
    $locs['any']['days'] === array('Mon','Tue','Wed','Thu','Fri','Sat','Sun'));
check('dasma label is human-readable',
    $locs['dasma']['label'] === 'Dasma, Makati');

// --- gv_rf_validate_location_days() ---
check('valid: dasma + [Mon,Wed]',
    gv_rf_validate_location_days('dasma', array('Mon','Wed')) === true);
check('invalid: dasma + [Fri] (Fri not a dasma day)',
    gv_rf_validate_location_days('dasma', array('Fri')) === false);
check('invalid: unknown location',
    gv_rf_validate_location_days('nowhere', array('Mon')) === false);
check('invalid: empty day list',
    gv_rf_validate_location_days('dasma', array()) === false);
check('valid: any + [Tue,Sat] (only valid under any)',
    gv_rf_validate_location_days('any', array('Tue','Sat')) === true);
check('invalid: days not an array',
    gv_rf_validate_location_days('dasma', 'Mon') === false);

// --- gv_rf_next_weekday_date() ---
$fixed = new DateTime('2026-07-13 15:30', new DateTimeZone('Asia/Manila'));
function gv_days_between($from, $ymd) {
    $a = (clone $from); $a->setTime(0,0,0);
    $b = DateTime::createFromFormat('Y-m-d', $ymd); $b->setTime(0,0,0);
    return (int) $a->diff($b)->format('%r%a');
}
check('empty days -> empty string', gv_rf_next_weekday_date(array(), $fixed) === '');
check('unknown day -> empty string', gv_rf_next_weekday_date(array('Xyz'), $fixed) === '');
$all_ok = true; $window_ok = true;
foreach (array('Mon','Tue','Wed','Thu','Fri','Sat','Sun') as $d) {
    $res = gv_rf_next_weekday_date(array($d), $fixed);
    $dt  = DateTime::createFromFormat('Y-m-d', $res);
    if (!$dt || $dt->format('D') !== $d) $all_ok = false;
    $diff = gv_days_between($fixed, $res);
    if ($diff < 1 || $diff > 7) $window_ok = false;
}
check('single weekday resolves to that weekday', $all_ok);
check('resolved date is 1..7 days ahead (strictly future)', $window_ok);
$today_wd = $fixed->format('D'); // requesting today's weekday -> +7
check('same-weekday request lands 7 days out',
    gv_days_between($fixed, gv_rf_next_weekday_date(array($today_wd), $fixed)) === 7);
check('multi-day picks the soonest',
    gv_rf_next_weekday_date(array('Sat','Sun'), $fixed)
    === min(gv_rf_next_weekday_date(array('Sat'), $fixed), gv_rf_next_weekday_date(array('Sun'), $fixed)));

// --- gv_rf_gcal_url() ---
check('gcal: invalid date -> empty', gv_rf_gcal_url(array('date' => 'nope')) === '');
$gc = gv_rf_gcal_url(array(
    'title' => 'GV Consultation — Test', 'date' => '2026-07-13',
    'guest' => 'parent@example.com', 'details' => "Player: Test", 'location' => 'Dasma, Makati, Metro Manila',
));
check('gcal: points at Google Calendar', strpos($gc, 'calendar.google.com/calendar/render') !== false);
check('gcal: is a TEMPLATE action', strpos($gc, 'action=TEMPLATE') !== false);
check('gcal: all-day range is date/next-day', strpos($gc, 'dates=20260713%2F20260714') !== false
    || strpos($gc, 'dates=20260713/20260714') !== false);
check('gcal: prefills guest email', strpos(urldecode($gc), 'add=parent@example.com') !== false);
check('gcal: carries title', strpos(urldecode($gc), 'GV Consultation') !== false);
check('gcal: carries location', strpos(urldecode($gc), 'Metro Manila') !== false);

// --- Task 9: legacy modal retirement (source contracts) ---
// gv-request-form.php must be compatibility helpers only: no POST handlers,
// no shortcode, no footer modal, no /book-a-consultation/ -> modal redirect.
$plugin_src = file_get_contents(__DIR__ . '/../gv-request-form.php');
check('legacy: no admin_post_nopriv_gv_request_form registration',
    strpos($plugin_src, 'admin_post_nopriv_gv_request_form') === false);
check('legacy: no admin_post_gv_request_form registration',
    strpos($plugin_src, "'admin_post_gv_request_form'") === false);
check('legacy: no wp_footer modal hook',
    strpos($plugin_src, 'wp_footer') === false);
check('legacy: no [gv_request_form] shortcode registration',
    strpos($plugin_src, 'add_shortcode') === false);
check('legacy: no gv_open_modal redirect or URL flag',
    strpos($plugin_src, 'gv_open_modal') === false);

// Deployed page sources must not be able to restore the modal.
$legacy_sources = array(
    'training-programs.html'        => __DIR__ . '/../../pages/training-programs.html',
    'deploy-training-programs.php'  => __DIR__ . '/../../scripts/deploy-training-programs.php',
    'build-functional.php'          => __DIR__ . '/../../scripts/build-functional.php',
);
foreach ($legacy_sources as $name => $path) {
    $src = file_exists($path) ? file_get_contents($path) : '';
    check("legacy: $name exists", $src !== '');
    check("legacy: $name has no [gv_request_form] shortcode",
        strpos($src, '[gv_request_form]') === false);
    check("legacy: $name has no data-gv-open-modal trigger",
        strpos($src, 'data-gv-open-modal') === false);
}

echo $failures ? "\n$failures FAILED\n" : "\nALL PASS\n";
exit($failures ? 1 : 0);
