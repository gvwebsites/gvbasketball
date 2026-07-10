<?php
/*
Plugin Name: GV Basketball — Request Form Legacy Helpers
Description: DEPRECATED compatibility layer. The email-only consultation modal (its shortcode, admin-post handlers, footer-injected markup, and the /book-a-consultation/ redirect) has been retired in favor of the native LatePoint wizard driven by gv-members.php. This file now only backs legacy helper functions (locations, weekday/date math, Google Calendar URL, branded email shell, Turnstile verification) that remain tested and reusable. Do not add new UI, hooks, or request handling here.
Version: 2.0
*/
if (!defined('ABSPATH')) exit;

/**
 * @deprecated 2.0 Retained only as the default coach inbox for legacy helpers
 * and the gv-members consultation workflow (see gv-members/booking.php).
 */
if (!defined('GV_RF_RECIPIENT')) define('GV_RF_RECIPIENT', 'gvbasketballcoaching@gmail.com');

/* ---------------- Locations & day model (single source of truth) ----------------
 * @deprecated 2.0 Legacy helper kept for compatibility; the native wizard owns
 * venue selection now. Safe to consume read-only.
 */
function gv_rf_locations() {
    return array(
        'dasma'    => array('label' => 'Dasma, Makati',       'days_label' => 'Mon, Wed & Thu', 'days' => array('Mon','Wed','Thu')),
        'urdaneta' => array('label' => 'Urdaneta Village',    'days_label' => 'Fri & Sun',      'days' => array('Fri','Sun')),
        'corinth'  => array('label' => 'Corinthian Gardens',  'days_label' => 'Sun',            'days' => array('Sun')),
        'any'      => array('label' => 'Open to any location','days_label' => '',               'days' => array('Mon','Tue','Wed','Thu','Fri','Sat','Sun')),
    );
}

/**
 * @deprecated 2.0 Legacy validation helper for the retired request form.
 */
function gv_rf_validate_location_days($location, $days) {
    $locs = gv_rf_locations();
    if (!is_string($location) || !isset($locs[$location])) return false;
    if (!is_array($days) || count($days) === 0) return false;
    $allowed = $locs[$location]['days'];
    foreach ($days as $d) {
        if (!in_array($d, $allowed, true)) return false;
    }
    return true;
}

/* ---------------- Consultation date + Google Calendar helpers ---------------- */
/**
 * Short weekday name -> ISO-8601 numeric (Mon=1 .. Sun=7); 0 if unknown.
 * @deprecated 2.0 Legacy helper kept for compatibility.
 */
function gv_rf_weekday_num($abbr) {
    $map = array('Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7);
    return isset($map[$abbr]) ? $map[$abbr] : 0;
}

/**
 * Soonest strictly-future date matching any selected weekday.
 * $today is injectable (DateTime) for deterministic tests; defaults to now in the site/Manila tz.
 * Returns 'Y-m-d' or '' when no valid weekday is given.
 * @deprecated 2.0 Legacy helper kept for compatibility.
 */
function gv_rf_next_weekday_date($days_in, $today = null) {
    if (!is_array($days_in) || !$days_in) return '';
    if (!$today instanceof DateTime) {
        // Pin to the business timezone: WordPress on this host is left at UTC, but all
        // venues/scheduling are Manila — computing "today" in UTC would land the next
        // preferred weekday a day off during Manila 00:00–08:00.
        $today = new DateTime('now', new DateTimeZone('Asia/Manila'));
    }
    $today = clone $today;
    $today->setTime(0, 0, 0);
    $today_n = (int) $today->format('N');
    $best = null;
    foreach ($days_in as $abbr) {
        $n = gv_rf_weekday_num($abbr);
        if (!$n) continue;
        $ahead = ($n - $today_n + 7) % 7;
        if ($ahead === 0) $ahead = 7; // next occurrence, never "today"
        if ($best === null || $ahead < $best) $best = $ahead;
    }
    if ($best === null) return '';
    return (clone $today)->modify("+{$best} day")->format('Y-m-d');
}

/**
 * Build a Google Calendar "add event" template URL for an all-day event on $args['date'] (Y-m-d).
 * $args keys: title, date, guest (email, prefilled as attendee), details, location. Returns '' if date invalid.
 * @deprecated 2.0 Legacy helper kept for compatibility.
 */
function gv_rf_gcal_url($args) {
    $date = isset($args['date']) ? $args['date'] : '';
    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return '';
    $end_dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$end_dt) return '';
    $end_dt->modify('+1 day'); // all-day events: end date is exclusive (next day)
    $params = array(
        'action' => 'TEMPLATE',
        'text'   => isset($args['title']) ? $args['title'] : 'GV Consultation',
        'dates'  => str_replace('-', '', $date) . '/' . $end_dt->format('Ymd'),
    );
    if (!empty($args['details']))  $params['details']  = $args['details'];
    if (!empty($args['location'])) $params['location'] = $args['location'];
    if (!empty($args['guest']))    $params['add']      = $args['guest']; // prefill client as guest
    return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
}

/* ---------------- Branded email shell (mirrors gv-otp-email.php) ---------------- */
/**
 * @deprecated 2.0 Legacy helper kept for compatibility with existing callers/tests.
 */
function gv_rf_email_shell($heading, $intro, $inner) {
    $logo   = 'https://gvbasketball.com/wp-content/uploads/2026/07/gv-logo-crest.png';
    $navy   = '#123B78'; $orange = '#F47B20'; $char = '#1C1C1E'; $steel = '#6B6F76';
    $ig     = 'https://instagram.com/gvbasketballl';
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f5f7;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 12px;">
<tr><td align="center">
  <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="max-width:520px;width:100%;background:#ffffff;border:1px solid #E6E7E9;border-radius:14px;overflow:hidden;">
    <tr><td style="border-top:4px solid {$orange};"></td></tr>
    <tr><td align="center" style="padding:30px 32px 6px;">
      <img src="{$logo}" width="80" height="86" alt="GV Basketball" style="display:block;width:80px;height:auto;">
    </td></tr>
    <tr><td align="center" style="padding:12px 36px 0;">
      <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:800;color:{$navy};">{$heading}</h1>
      <p style="margin:10px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:{$steel};">{$intro}</p>
    </td></tr>
    <tr><td style="padding:22px 36px 0;">{$inner}</td></tr>
    <tr><td style="padding:24px 36px 0;"><hr style="border:none;border-top:1px solid #E6E7E9;margin:0;"></td></tr>
    <tr><td align="center" style="padding:16px 36px 30px;">
      <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:{$steel};">
        GV Basketball &middot; Metro Manila &middot; <a href="{$ig}" style="color:{$navy};text-decoration:none;">@gvbasketballl</a>
      </p>
    </td></tr>
  </table>
</td></tr>
</table>
</body></html>
HTML;
}

/* ---------------- Turnstile server-side verification ---------------- */
/**
 * @deprecated 2.0 Legacy helper kept for compatibility with existing callers/tests.
 */
function gv_rf_verify_turnstile($token, $ip) {
    if (!defined('GV_TURNSTILE_SECRET') || !GV_TURNSTILE_SECRET) return true; // not configured: don't lock out
    if (empty($token)) return false;
    $resp = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
        'timeout' => 10,
        'body'    => array('secret' => GV_TURNSTILE_SECRET, 'response' => $token, 'remoteip' => $ip),
    ));
    if (is_wp_error($resp)) return false;
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    return !empty($data['success']);
}
