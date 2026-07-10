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

// --- gv_rf_shortcode() rendered markup ---
$html = gv_rf_shortcode();
check('renders a location select', strpos($html, 'name="location"') !== false);
check('location select has dasma option', strpos($html, 'value="dasma"') !== false);
check('location option shows its days', strpos($html, 'Mon, Wed &amp; Thu') !== false
    || strpos($html, 'Mon, Wed & Thu') !== false);
check('renders day checkboxes', strpos($html, 'name="preferred_days[]"') !== false);
check('day checkbox carries data-day', strpos($html, 'data-day="Mon"') !== false);
check('exposes location-days JSON map', strpos($html, '"dasma":["Mon","Wed","Thu"]') !== false);
check('time field relabeled to optional note',
    stripos($html, 'time of day') !== false);

// --- legacy consultation redirects should auto-open the modal ---
check('training programs modal URL adds open flag',
    gv_rf_training_programs_url(true) === 'https://example.test/training-programs/?gv_open_modal=1');
$modal = (function () {
    ob_start();
    gv_rf_global_modal();
    return ob_get_clean();
})();
check('modal script auto-opens for gv_open_modal flag',
    strpos($modal, "params.has('gv_open_modal')") !== false);
check('modal script intercepts legacy consultation links',
    strpos($modal, "pathname==='/book-a-consultation/'") !== false);

echo $failures ? "\n$failures FAILED\n" : "\nALL PASS\n";
exit($failures ? 1 : 0);
