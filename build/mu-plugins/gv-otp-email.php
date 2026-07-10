<?php
/*
Plugin Name: GV Basketball — Branded OTP Email
Description: Replaces LatePoint's plain-text member login OTP email with a branded HTML version. LatePoint builds the OTP body inline with no pre-send filter, so we intercept at wp_mail.
Version: 1.0
*/
if (!defined('ABSPATH')) exit;

/**
 * Build the branded HTML body for a member login code email.
 * Table-based + inline CSS for broad email-client compatibility.
 */
function gv_otp_email_html($code) {
    $logo   = 'https://gvbasketball.com/wp-content/uploads/2026/07/gv-logo-crest.png';
    $navy   = '#123B78';
    $deep   = '#021F51';
    $orange = '#F47B20';
    $char   = '#1C1C1E';
    $steel  = '#6B6F76';
    $code   = preg_replace('/\s+/', '', $code);
    $codesp = chunk_split($code, 1, ' '); // space digits for readability
    $ig     = 'https://instagram.com/gvbasketballl';

    return <<<HTML
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f5f7;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:32px 12px;">
<tr><td align="center">
  <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px;width:100%;background:#ffffff;border:1px solid #E6E7E9;border-radius:14px;overflow:hidden;">
    <tr><td style="border-top:4px solid {$orange};"></td></tr>
    <tr><td align="center" style="padding:32px 32px 8px;">
      <img src="{$logo}" width="80" height="86" alt="GV Basketball" style="display:block;width:80px;height:auto;">
    </td></tr>
    <tr><td align="center" style="padding:14px 36px 0;">
      <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:800;color:{$navy};">Your login code</h1>
      <p style="margin:10px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.5;color:{$steel};">Enter this code to sign in to your GV Basketball member account.</p>
    </td></tr>
    <tr><td align="center" style="padding:24px 36px 4px;">
      <div style="display:inline-block;background:#f0f4fa;border:1px solid #d9e2f0;border-radius:10px;padding:16px 26px;font-family:'Courier New',Courier,monospace;font-size:34px;font-weight:700;letter-spacing:6px;color:{$deep};">{$codesp}</div>
    </td></tr>
    <tr><td align="center" style="padding:14px 36px 0;">
      <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:{$steel};">This code expires in <strong>10 minutes</strong>.</p>
    </td></tr>
    <tr><td style="padding:26px 36px 0;"><hr style="border:none;border-top:1px solid #E6E7E9;margin:0;"></td></tr>
    <tr><td align="center" style="padding:18px 36px 30px;">
      <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:{$steel};">
        Didn't try to sign in? You can safely ignore this email.<br>
        GV Basketball · Metro Manila · <a href="{$ig}" style="color:{$navy};text-decoration:none;">@gvbasketballl</a>
      </p>
    </td></tr>
  </table>
</td></tr>
</table>
</body></html>
HTML;
}

/**
 * Intercept LatePoint's OTP email at wp_mail and swap in the branded HTML version.
 * Scoped tightly to the OTP email only (subject contains "OTP") so other site
 * email (contact form, booking, newsletter) is untouched.
 */
add_filter('wp_mail', function ($args) {
    $subject = isset($args['subject']) ? $args['subject'] : '';
    $message = isset($args['message']) ? $args['message'] : '';
    if (stripos($subject, 'OTP') === false) return $args;

    // Pull the numeric code out of "Your OTP code is: 123456".
    if (!preg_match('/(\d{4,8})/', wp_strip_all_tags($message), $m)) return $args;

    $args['subject'] = 'Your GV Basketball login code';
    $args['message'] = gv_otp_email_html($m[1]);

    // Force HTML content type for this message (normalize whatever headers came in).
    $headers = isset($args['headers']) ? $args['headers'] : array();
    if (is_string($headers)) {
        $headers = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $headers)));
    }
    $headers = array_values(array_filter((array) $headers, function ($h) {
        return stripos($h, 'content-type') === false;
    }));
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $args['headers'] = $headers;

    return $args;
}, 99);
