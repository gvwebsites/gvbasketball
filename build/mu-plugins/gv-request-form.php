<?php
/*
Plugin Name: GV Basketball — Request Training Form
Description: Branded training-request form ([gv_request_form]) with Cloudflare Turnstile, honeypot + nonce, and two branded HTML emails (admin notification + submitter auto-reply). Replaces the public LatePoint booking form on /book-a-consultation/.
Version: 1.0
*/
if (!defined('ABSPATH')) exit;

if (!defined('GV_RF_RECIPIENT')) define('GV_RF_RECIPIENT', 'gvbasketballcoaching@gmail.com');

/* Redirect /book-a-consultation/ (2982) → /training-programs/ (302) */
function gv_rf_redirect_book_page() {
    if (is_page(2982)) {
        wp_redirect(home_url('/training-programs/'), 302);
        exit;
    }
}
add_action('template_redirect', 'gv_rf_redirect_book_page');

function gv_rf_types() {
    return array('Private Training', 'Small Group', 'Elite Performance');
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
    if (!$ref) $ref = home_url('/training-programs/');
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

    if (!$parent || !$player || !is_email($email) || $age < 4 || $age > 25 || !in_array($type, gv_rf_types(), true) || !$times) $back('err');

    // ---- Admin notification ----
    $rows = array(
        'Parent / Guardian'      => esc_html($parent),
        'Player'                 => esc_html($player),
        'Player age'             => esc_html((string) $age),
        'Email'                  => esc_html($email),
        'Phone / Instagram'      => $alt ? esc_html($alt) : '&mdash;',
        'Training type'          => esc_html($type),
        'Preferred days &amp; times' => nl2br(esc_html($times)),
    );
    $tbl = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1C1C1E;border-collapse:collapse;">';
    foreach ($rows as $k => $v) {
        $tbl .= '<tr>'
              . '<td style="padding:9px 12px;background:#f0f4fa;border:1px solid #E6E7E9;font-weight:700;color:#123B78;width:42%;vertical-align:top;">' . $k . '</td>'
              . '<td style="padding:9px 12px;border:1px solid #E6E7E9;vertical-align:top;">' . $v . '</td>'
              . '</tr>';
    }
    $tbl .= '</table>';
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
        . 'Your preferred times: <em>' . nl2br(esc_html($times)) . '</em></p>'
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
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Training type <i>*</i></span><select name="training_type" required>' . $opts . '</select></label>';
    $html .= '<label class="gv-rform-field gv-rform-field--full"><span>Preferred days &amp; times to meet <i>*</i></span><textarea name="preferred_times" rows="3" placeholder="e.g. Weekday afternoons after 4pm, or Saturday mornings" required></textarea></label>';
    $html .= '</div>';
    $html .= $ts_widget;
    $html .= '<button type="submit" class="gv-btn gv-btn--primary gv-rform-submit">Send Request</button>';
    $html .= '<p class="gv-rform-fine">We\'ll only use your details to follow up about training. Pricing is shared during your consultation.</p>';
    $html .= '</form></div>' . $ts_script;
    return $html;
}
add_shortcode('gv_request_form', 'gv_rf_shortcode');
