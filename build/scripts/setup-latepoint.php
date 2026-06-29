<?php
global $wpdb;
$p = $wpdb->prefix;

// Fresh start on config tables (no real bookings yet). Do NOT touch steps/step_settings.
foreach (array('latepoint_agents_services','latepoint_work_periods','latepoint_services','latepoint_service_meta','latepoint_agents','latepoint_agent_meta','latepoint_locations') as $t) {
    $wpdb->query("TRUNCATE TABLE {$p}{$t}");
}

// --- Agent ---
$agent = new OsAgentModel();
$agent->first_name  = 'Coach';
$agent->last_name   = 'Gino';
$agent->display_name= 'Coach Gino';
$agent->email       = 'gvbasketballcoaching@gmail.com';
$agent->status      = 'active';
$agent->save();
$agent_id = $agent->id;

// --- Locations ---
function gv_loc($name, $addr) {
    $l = new OsLocationModel();
    $l->name = $name; $l->full_address = $addr; $l->status = 'active';
    $l->save(); return $l->id;
}
$makati  = gv_loc('Makati', 'Makati, Metro Manila');
$ortigas = gv_loc('Ortigas', 'Ortigas, Metro Manila');

// --- Services ---
function gv_svc($name, $dur, $capmin, $capmax, $desc) {
    $s = new OsServiceModel();
    $s->name = $name;
    $s->duration = $dur;
    $s->capacity_min = $capmin;
    $s->capacity_max = $capmax;
    $s->charge_amount = 0;
    $s->deposit_amount = 0;
    $s->is_price_variable = 0;
    $s->price_min = 0; $s->price_max = 0;
    $s->status = 'active';
    $s->visibility = 'public';
    $s->short_description = $desc;
    $s->timeblock_interval = 60;
    $s->save(); return $s->id;
}
$consult = gv_svc('Player Consultation', 45, 1, 1, 'Discuss goals, current level, and the best-fit program.');
$private = gv_svc('Private Training', 60, 1, 1, '1-on-1 individualized development.');
$group   = gv_svc('Small Group Training', 90, 1, 5, 'Maximum 4-5 athletes, competitive reps.');
$elite   = gv_svc('Elite Performance', 90, 1, 5, 'Basketball + strength, conditioning & recovery.');
$services  = array($consult, $private, $group, $elite);
$locations = array($makati, $ortigas);

// --- Connectors (agent x service x location) ---
foreach ($services as $sid) {
    foreach ($locations as $lid) {
        $c = new OsConnectorModel();
        $c->agent_id = $agent_id; $c->service_id = $sid; $c->location_id = $lid;
        $c->save();
    }
}

// --- Work periods: Mon(1) Tue(2) Fri(5) Sun(7), 15:00-18:00 (900-1080), per location, all services ---
$days = array(1, 2, 5, 7);
foreach ($locations as $lid) {
    foreach ($days as $d) {
        $w = new OsWorkPeriodModel();
        $w->agent_id = $agent_id;
        $w->location_id = $lid;
        $w->service_id = 0;
        $w->week_day = $d;
        $w->start_time = 900;
        $w->end_time = 1080;
        $w->chain_id = 0;
        $w->save();
    }
}

// --- Settings: hide empty category steps + timezone selector for a clean flow ---
$set = array(
    'steps_show_service_categories'  => 'off',
    'steps_show_location_categories' => 'off',
    'steps_show_timezone_selector'   => 'off',
    'enable_payments_local'          => 'off',
    'currency_iso_code'              => 'PHP',
    'currency_symbol_before'         => '₱',
    'currency_symbol_after'          => '',
    'steps_support_text'             => 'Questions? Message us on Instagram @gvbasketballl',
    'support_phone'                  => '',
);
foreach ($set as $k => $v) {
    $wpdb->query($wpdb->prepare("DELETE FROM {$p}latepoint_settings WHERE name=%s", $k));
    $wpdb->query($wpdb->prepare("INSERT INTO {$p}latepoint_settings (name, value) VALUES (%s,%s)", $k, $v));
}

echo "agent=$agent_id makati=$makati ortigas=$ortigas consult=$consult private=$private group=$group elite=$elite\n";
echo "connectors=" . $wpdb->get_var("SELECT COUNT(*) FROM {$p}latepoint_agents_services") . " work_periods=" . $wpdb->get_var("SELECT COUNT(*) FROM {$p}latepoint_work_periods") . "\n";
