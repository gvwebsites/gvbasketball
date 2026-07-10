<?php
/*
Plugin Name: GV Basketball — Request Training Form
Description: Branded training-request form ([gv_request_form]) with Cloudflare Turnstile, honeypot + nonce, and two branded HTML emails (admin notification + submitter auto-reply). Replaces the public LatePoint booking form on /book-a-consultation/.
Version: 1.0
*/
if (!defined('ABSPATH')) exit;

if (!defined('GV_RF_RECIPIENT')) define('GV_RF_RECIPIENT', 'gvbasketballcoaching@gmail.com');

function gv_rf_training_programs_url($open_modal = false) {
    $url = home_url('/training-programs/');
    if ($open_modal) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . 'gv_open_modal=1';
    }
    return $url;
}

/* Redirect /book-a-consultation/ (2982) → /training-programs/?gv_open_modal=1 (302) */
function gv_rf_redirect_book_page() {
    if (is_page(2982)) {
        wp_redirect(gv_rf_training_programs_url(true), 302);
        exit;
    }
}
add_action('template_redirect', 'gv_rf_redirect_book_page');

function gv_rf_types() {
    return array('Private Training', 'Small Group', 'Elite Performance');
}

/* ---------------- Locations & day model (single source of truth) ---------------- */
function gv_rf_locations() {
    return array(
        'dasma'    => array('label' => 'Dasma, Makati',       'days_label' => 'Mon, Wed & Thu', 'days' => array('Mon','Wed','Thu')),
        'urdaneta' => array('label' => 'Urdaneta Village',    'days_label' => 'Fri & Sun',      'days' => array('Fri','Sun')),
        'corinth'  => array('label' => 'Corinthian Gardens',  'days_label' => 'Sun',            'days' => array('Sun')),
        'any'      => array('label' => 'Open to any location','days_label' => '',               'days' => array('Mon','Tue','Wed','Thu','Fri','Sat','Sun')),
    );
}

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
// Short weekday name -> ISO-8601 numeric (Mon=1 .. Sun=7); 0 if unknown.
function gv_rf_weekday_num($abbr) {
    $map = array('Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7);
    return isset($map[$abbr]) ? $map[$abbr] : 0;
}

// Soonest strictly-future date matching any selected weekday.
// $today is injectable (DateTime) for deterministic tests; defaults to now in the site/Manila tz.
// Returns 'Y-m-d' or '' when no valid weekday is given.
function gv_rf_next_weekday_date($days_in, $today = null) {
    if (!is_array($days_in) || !$days_in) return '';
    if (!$today instanceof DateTime) {
        $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('Asia/Manila');
        $today = new DateTime('now', $tz);
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

// Build a Google Calendar "add event" template URL for an all-day event on $args['date'] (Y-m-d).
// $args keys: title, date, guest (email, prefilled as attendee), details, location. Returns '' if date invalid.
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
function gv_rf_email_shell($heading, $intro, $inner) {
    $logo   = 'https://gvbasketball.com/wp-content/uploads/2025/07/GV_Logo_Main.png';
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
      <img src="{$logo}" width="64" height="64" alt="GV Basketball" style="display:block;width:64px;height:auto;">
      <div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:700;letter-spacing:3px;color:{$char};margin-top:12px;">GV BASKETBALL</div>
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

/* ---------------- Submission handler ---------------- */
function gv_rf_handle() {
    $ref = wp_get_referer();
    if (!$ref) $ref = gv_rf_training_programs_url();
    $back = function ($status) use ($ref) {
        $url = add_query_arg('gv_request', $status, remove_query_arg('gv_request', $ref)) . '#gv-request-form';
        wp_safe_redirect($url);
        exit;
    };

    if (!isset($_POST['gv_rf_nonce']) || !wp_verify_nonce($_POST['gv_rf_nonce'], 'gv_request_form')) $back('err');
    if (!empty($_POST['gv_website'])) $back('ok'); // honeypot tripped -> fake success

    $token = isset($_POST['cf-turnstile-response']) ? sanitize_text_field(wp_unslash($_POST['cf-turnstile-response'])) : '';
    if (!gv_rf_verify_turnstile($token, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')) $back('spam');

    $parent = sanitize_text_field(wp_unslash($_POST['parent_name'] ?? ''));
    $player = sanitize_text_field(wp_unslash($_POST['player_name'] ?? ''));
    $age    = intval($_POST['player_age'] ?? 0);
    $email  = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $alt    = sanitize_text_field(wp_unslash($_POST['contact_alt'] ?? ''));
    $type   = sanitize_text_field(wp_unslash($_POST['training_type'] ?? ''));
    $times  = sanitize_textarea_field(wp_unslash($_POST['preferred_times'] ?? ''));

    $location = sanitize_key(wp_unslash($_POST['location'] ?? ''));
    $days_in  = (isset($_POST['preferred_days']) && is_array($_POST['preferred_days']))
        ? array_map('sanitize_text_field', wp_unslash($_POST['preferred_days']))
        : array();

    if (!$parent || !$player || !is_email($email) || $age < 4 || $age > 25 || !in_array($type, gv_rf_types(), true)) $back('err');
    if (!gv_rf_validate_location_days($location, $days_in)) $back('err');

    // ---- Admin notification ----
    $locs      = gv_rf_locations();
    $loc_label = $locs[$location]['label'];
    $days_str  = implode(', ', $days_in);
    $rows = array(
        'Parent / Guardian'      => esc_html($parent),
        'Player'                 => esc_html($player),
        'Player age'             => esc_html((string) $age),
        'Email'                  => esc_html($email),
        'Phone / Instagram'      => $alt ? esc_html($alt) : '&mdash;',
        'Training type'          => esc_html($type),
        'Preferred location'     => esc_html($loc_label),
        'Preferred day(s)'       => esc_html($days_str),
        'Preferred time / notes' => $times ? nl2br(esc_html($times)) : '&mdash;',
    );
    $tbl = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1C1C1E;border-collapse:collapse;">';
    foreach ($rows as $k => $v) {
        $tbl .= '<tr>'
              . '<td style="padding:9px 12px;background:#f0f4fa;border:1px solid #E6E7E9;font-weight:700;color:#123B78;width:42%;vertical-align:top;">' . $k . '</td>'
              . '<td style="padding:9px 12px;border:1px solid #E6E7E9;vertical-align:top;">' . $v . '</td>'
              . '</tr>';
    }
    $tbl .= '</table>';

    // "Add to Google Calendar" button — all-day event on the soonest preferred day (coach adjusts).
    $gcal_date = gv_rf_next_weekday_date($days_in);
    if ($gcal_date) {
        $gcal_details = "Player: {$player} (age {$age})\nParent/Guardian: {$parent}\nEmail: {$email}\n"
            . 'Phone/IG: ' . ($alt ?: '—') . "\nTraining type: {$type}\n"
            . "Preferred location: {$loc_label}\nPreferred day(s): {$days_str}"
            . ($times ? "\nNotes: {$times}" : '');
        $gcal_url = gv_rf_gcal_url(array(
            'title'    => 'GV Consultation — ' . $player,
            'date'     => $gcal_date,
            'guest'    => $email,
            'details'  => $gcal_details,
            'location' => ($location !== 'any') ? $loc_label . ', Metro Manila' : '',
        ));
        $nice_date = DateTime::createFromFormat('Y-m-d', $gcal_date)->format('l, M j, Y');
        $tbl .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px;"><tr><td align="center">'
            . '<a href="' . esc_url($gcal_url) . '" style="display:inline-block;background:#F47B20;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-weight:700;font-size:14px;text-decoration:none;padding:13px 26px;border-radius:8px;">Add to Google Calendar &mdash; ' . esc_html($nice_date) . '</a>'
            . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#6B6F76;margin-top:9px;">Opens a prefilled event with <strong>' . esc_html($email) . '</strong> as a guest. Adjust the day if needed, then save to send the invite.</div>'
            . '</td></tr></table>';
    }

    $admin_html = gv_rf_email_shell('New training request', 'A new request came in from the website.', $tbl);
    $admin_headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $parent . ' <' . $email . '>',
    );
    wp_mail(GV_RF_RECIPIENT, 'New training request — ' . $player, $admin_html, $admin_headers);

    // ---- Auto-reply to submitter ----
    $reply_inner =
        '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1C1C1E;">'
        . 'Thanks for reaching out about <strong>' . esc_html($player) . '</strong>! We\'ve received your request for '
        . '<strong>' . esc_html($type) . '</strong> and Coach Gino\'s team will get back to you to confirm days, times, and the best-fit plan.</p>'
        . '<p style="margin:14px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1C1C1E;">'
        . 'Location: <em>' . esc_html($loc_label) . '</em><br>'
        . 'Preferred day(s): <em>' . esc_html($days_str) . '</em>'
        . ($times ? '<br>Notes: <em>' . nl2br(esc_html($times)) . '</em>' : '') . '</p>'
        . '<p style="margin:18px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#6B6F76;">'
        . 'Want to chat sooner? Message us on Instagram: '
        . '<a href="https://ig.me/m/gvbasketballl" style="color:#123B78;">@gvbasketballl</a>.</p>';
    $reply_html = gv_rf_email_shell('We got your request', 'Welcome to GV Basketball, ' . esc_html($parent) . '.', $reply_inner);
    wp_mail($email, 'We got your request — GV Basketball', $reply_html, array('Content-Type: text/html; charset=UTF-8'));

    $back('ok');
}
add_action('admin_post_nopriv_gv_request_form', 'gv_rf_handle');
add_action('admin_post_gv_request_form', 'gv_rf_handle');

/* ---------------- Scoped styles (emitted once) ---------------- */
function gv_rf_styles() {
    static $done = false;
    if ($done) return '';
    $done = true;
    return '<style>
    .gv-rform-wrap{max-width:680px;margin:0 auto;}
    .gv-rform-note{padding:14px 18px;border-radius:10px;margin-bottom:22px;font:600 15px/1.5 Montserrat,Arial,sans-serif;}
    .gv-rform-note--ok{background:#e8f5ec;border:1px solid #b8e0c4;color:#1d6b3a;}
    .gv-rform-note--err{background:#fdecea;border:1px solid #f5c2bb;color:#a3271a;}
    .gv-rform-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
    .gv-rform-field{display:flex;flex-direction:column;gap:7px;}
    .gv-rform-field--full{grid-column:1/-1;}
    .gv-rform-field>span{font:700 13px/1.2 Montserrat,Arial,sans-serif;letter-spacing:.02em;color:#123B78;text-transform:uppercase;}
    .gv-rform-field i{color:#F47B20;font-style:normal;}
    .gv-rform-field small{color:#6B6F76;font-weight:600;text-transform:none;letter-spacing:0;}
    .gv-rform-field input,.gv-rform-field select,.gv-rform-field textarea{
      width:100%;padding:13px 14px;border:1px solid #d6d9de;border-radius:10px;
      font:400 15px/1.4 Inter,Arial,sans-serif;color:#1C1C1E;background:#fff;box-sizing:border-box;}
    .gv-rform-field input:focus,.gv-rform-field select:focus,.gv-rform-field textarea:focus{
      outline:none;border-color:#F47B20;box-shadow:0 0 0 3px rgba(244,123,32,.15);}
    .gv-rform-field textarea{resize:vertical;}
    .gv-rform-days{display:flex;flex-wrap:wrap;gap:10px;margin-top:2px;}
    .gv-rform-day{display:inline-flex;align-items:center;gap:7px;padding:9px 14px;border:1px solid #d6d9de;border-radius:999px;font:600 14px/1 Inter,Arial,sans-serif;color:#1C1C1E;cursor:pointer;user-select:none;}
    .gv-rform-day input{width:auto;margin:0;accent-color:#F47B20;}
    .gv-rform-day--hidden{display:none;}
    .gv-rform-dayhint{font:600 13px/1.4 Inter,Arial,sans-serif;color:#6B6F76;margin-top:2px;}
    .gv-rform .cf-turnstile{margin:22px 0 4px;}
    .gv-rform-submit{margin-top:22px;width:100%;justify-content:center;}
    .gv-rform-fine{margin:14px 0 0;font:400 12.5px/1.5 Inter,Arial,sans-serif;color:#6B6F76;text-align:center;}
    .gv-rform-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;}
    @media(max-width:560px){.gv-rform-grid{grid-template-columns:1fr;}}
    </style>';
}

/* ---------------- Shortcode: the branded form ---------------- */
function gv_rf_shortcode() {
    $sitekey = defined('GV_TURNSTILE_SITEKEY') ? GV_TURNSTILE_SITEKEY : '';
    $action  = esc_url(admin_url('admin-post.php'));
    $nonce   = wp_create_nonce('gv_request_form');
    $status  = isset($_GET['gv_request']) ? sanitize_key($_GET['gv_request']) : '';

    $banner = '';
    if ($status === 'ok') {
        $banner = '<div class="gv-rform-note gv-rform-note--ok">Thanks! Your request is in — we\'ll be in touch shortly. Check your inbox for a confirmation.</div>';
    } elseif ($status === 'spam') {
        $banner = '<div class="gv-rform-note gv-rform-note--err">We couldn\'t verify you weren\'t a robot. Please try again.</div>';
    } elseif ($status === 'err') {
        $banner = '<div class="gv-rform-note gv-rform-note--err">Please check your entries and try again — all required fields are needed.</div>';
    }

    $opts = '<option value="" disabled selected>Choose a program…</option>';
    foreach (gv_rf_types() as $t) {
        $opts .= '<option value="' . esc_attr($t) . '">' . esc_html($t) . '</option>';
    }

    $ts_script = $sitekey ? '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>' : '';
    $ts_widget = $sitekey ? '<div class="cf-turnstile" data-sitekey="' . esc_attr($sitekey) . '" data-theme="light"></div>' : '';

    $html  = gv_rf_styles();
    $html .= '<div class="gv-rform-wrap" id="gv-request-form">' . $banner;
    $html .= '<form class="gv-rform" method="post" action="' . $action . '" novalidate>';
    $html .= '<input type="hidden" name="action" value="gv_request_form">';
    $html .= '<input type="hidden" name="gv_rf_nonce" value="' . esc_attr($nonce) . '">';
    $html .= '<div class="gv-rform-hp" aria-hidden="true"><label>Website<input type="text" name="gv_website" tabindex="-1" autocomplete="off"></label></div>';
    $html .= '<div class="gv-rform-grid">';
    $html .= '<label class="gv-rform-field"><span>Your name <i>*</i></span><input type="text" name="parent_name" required autocomplete="name"></label>';
    $html .= '<label class="gv-rform-field"><span>Player name <i>*</i></span><input type="text" name="player_name" required></label>';
    $html .= '<label class="gv-rform-field"><span>Player age <i>*</i></span><input type="number" name="player_age" min="4" max="25" required></label>';
    $html .= '<label class="gv-rform-field"><span>Email <i>*</i></span><input type="email" name="email" required autocomplete="email"></label>';
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Phone or Instagram handle <small>(optional)</small></span><input type="text" name="contact_alt"></label>';
    // Location <option>s
    $locs = gv_rf_locations();
    $loc_opts = '<option value="" disabled selected>Choose a location…</option>';
    foreach ($locs as $key => $info) {
        $label = $info['label'];
        if ($info['days_label'] !== '') $label .= ' — ' . $info['days_label'];
        $loc_opts .= '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
    }

    // All 7 day checkboxes (JS shows/hides per location)
    $all_days = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
    $day_boxes = '';
    foreach ($all_days as $d) {
        $day_boxes .= '<label class="gv-rform-day gv-rform-day--hidden" data-day="' . esc_attr($d) . '">'
                    . '<input type="checkbox" name="preferred_days[]" value="' . esc_attr($d) . '">'
                    . '<span>' . esc_html($d) . '</span></label>';
    }

    // location -> valid days map for the client
    $loc_days_json = wp_json_encode(array_map(function ($i) { return $i['days']; }, $locs));

    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Training type <i>*</i></span><select name="training_type" required>' . $opts . '</select></label>';
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Preferred location <i>*</i></span><select name="location" id="gv-rf-location" required>' . $loc_opts . '</select></label>';
    $html .= '<div class="gv-rform-field gv-rform-field--full"><span>Preferred day(s) <i>*</i></span>'
           . '<div class="gv-rform-days" id="gv-rf-days">' . $day_boxes . '</div>'
           . '<p class="gv-rform-dayhint" id="gv-rf-dayhint">Choose a location to see available days.</p></div>';
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Preferred time of day / notes <small>(optional)</small></span><textarea name="preferred_times" rows="2" placeholder="e.g. after 4pm on weekdays, or Sunday mornings"></textarea></label>';
    $html .= '</div>';
    $html .= $ts_widget;
    $html .= '<button type="submit" class="gv-btn gv-btn--primary gv-rform-submit">Send Request</button>';
    $html .= '<p class="gv-rform-fine">We\'ll only use your details to follow up about training. Pricing is shared during your consultation.</p>';
    $filter_script = '<script>(function(){'
        . 'var MAP=' . $loc_days_json . ';'
        . 'var sel=document.getElementById("gv-rf-location");'
        . 'var wrap=document.getElementById("gv-rf-days");'
        . 'var hint=document.getElementById("gv-rf-dayhint");'
        . 'if(!sel||!wrap)return;'
        . 'function apply(){'
        . 'var allowed=MAP[sel.value]||[];'
        . 'var boxes=wrap.querySelectorAll(".gv-rform-day");'
        . 'var any=false;'
        . 'boxes.forEach(function(b){'
        . 'var day=b.getAttribute("data-day");'
        . 'var ok=allowed.indexOf(day)!==-1;'
        . 'b.classList.toggle("gv-rform-day--hidden",!ok);'
        . 'var cb=b.querySelector("input");'
        . 'if(ok){any=true;}else if(cb){cb.checked=false;}'
        . '});'
        . 'if(hint)hint.style.display=any?"none":"block";'
        . '}'
        . 'sel.addEventListener("change",apply);apply();'
        . '})();</script>';
    $html .= '</form></div>' . $ts_script . $filter_script;
    return $html;
}
add_shortcode('gv_request_form', 'gv_rf_shortcode');

/* ---------------- Global consultation modal (injected on every page via wp_footer) ---------------- */
function gv_rf_global_modal() {
    $form = gv_rf_shortcode();
    ?>
<div class="gv-modal-overlay" id="gv-consult-modal">
  <div class="gv-modal" role="dialog" aria-modal="true" aria-labelledby="gv-modal-title">
    <button class="gv-modal__close" aria-label="Close">&times;</button>
    <div class="gv-modal__header">
      <span class="gv-eyebrow">Book a Consultation</span>
      <h2 id="gv-modal-title" class="gv-section-title">Start Your Player's Journey</h2>
      <p class="gv-lead">Share a few details about your athlete and the team will follow up to confirm the consultation.</p>
    </div>
    <div class="gv-modal__body"><?php echo $form; ?></div>
  </div>
</div>
<script>
(function(){
  var modal=document.getElementById('gv-consult-modal');
  if(!modal)return;
  var closeBtn=modal.querySelector('.gv-modal__close');
  var lastTrigger=null;

  function openModal(trigger){
    lastTrigger=trigger||null;
    modal.classList.add('gv-modal-overlay--open');
    document.body.style.overflow='hidden';
    var first=modal.querySelector('input,select,textarea,button:not(.gv-modal__close)');
    if(first)setTimeout(function(){first.focus();},100);
  }
  function closeModal(){
    modal.classList.remove('gv-modal-overlay--open');
    document.body.style.overflow='';
    if(lastTrigger)lastTrigger.focus();
  }

  function isLegacyConsultLink(el){
    if(!el||el.tagName!=='A')return false;
    try{
      var url=new URL(el.getAttribute('href')||'',window.location.href);
      var pathname=url.pathname.replace(/\/+$/,'/')||'/';
      if(pathname==='/book-a-consultation/')return true;
      return pathname==='/training-programs/'&&el.textContent.trim().toLowerCase()==='book a consultation';
    }catch(err){
      return false;
    }
  }

  document.addEventListener('click',function(e){
    var t=e.target.closest('[data-gv-open-modal], a');
    if(t&&!t.hasAttribute('data-gv-open-modal')&&!isLegacyConsultLink(t))t=null;
    if(t){e.preventDefault();openModal(t);}
  });
  if(closeBtn)closeBtn.addEventListener('click',closeModal);
  modal.addEventListener('click',function(e){
    if(e.target===modal)closeModal();
  });
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape'&&modal.classList.contains('gv-modal-overlay--open'))closeModal();
  });

  /* Auto-open modal when redirected back with ?gv_request= param */
  function checkAutoOpen(){
    var params=new URLSearchParams(window.location.search);
    if(params.has('gv_request')||params.has('gv_open_modal')){
      openModal();
      if(window.history&&window.history.replaceState){
        var u=new URL(window.location.href);
        u.searchParams.delete('gv_request');
        u.searchParams.delete('gv_open_modal');
        window.history.replaceState({},'',u.toString());
      }
    }
  }
  if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded',checkAutoOpen);
  }else{
    checkAutoOpen();
  }
})();
</script>
<?php
}
add_action('wp_footer', 'gv_rf_global_modal', 50);
