<?php
/**
 * GV Members — Emails Workflow
 */

defined('ABSPATH') || exit;

/**
 * Extract email data from an OsBookingModel.
 */
function gv_members_booking_email_data(OsBookingModel $booking): array {
    $parent_name = '';
    $parent_email = '';
    $parent_phone = '';
    
    if (class_exists('OsCustomerModel') && !empty($booking->customer_id)) {
        $customer = new OsCustomerModel($booking->customer_id);
        if ($customer && (method_exists($customer, 'is_new_record') ? !$customer->is_new_record() : true)) {
            $parent_name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $parent_email = $customer->email ?? '';
            $parent_phone = $customer->phone ?? '';
        }
    }
    
    $venue_name = '';
    if (class_exists('OsLocationModel') && !empty($booking->location_id)) {
        $location = new OsLocationModel($booking->location_id);
        if ($location && (method_exists($location, 'is_new_record') ? !$location->is_new_record() : true)) {
            $venue_name = $location->name ?? '';
        }
    }
    
    $day_str = '';
    if (!empty($booking->start_date)) {
        try {
            $dt = new DateTime($booking->start_date, new DateTimeZone('Asia/Manila'));
            $day_str = $dt->format('l, F j, Y');
        } catch (Exception $e) {
            $day_str = $booking->start_date;
        }
    }

    return [
        'booking_code' => $booking->booking_code ?? '',
        'player_name' => $booking->get_meta_by_key('gv_player_name'),
        'player_age' => (int) $booking->get_meta_by_key('gv_player_age'),
        'training_interest' => $booking->get_meta_by_key('gv_training_interest'),
        'contact_alt' => $booking->get_meta_by_key('gv_contact_alt'),
        'note' => $booking->get_meta_by_key('gv_note'),
        'member_opt_in' => $booking->get_meta_by_key('gv_member_opt_in'),
        'venue_name' => $venue_name,
        'day' => $day_str,
        'parent_name' => $parent_name,
        'parent_email' => $parent_email,
        'parent_phone' => $parent_phone,
    ];
}

/**
 * Branded email shell.
 */
function gv_members_email_shell($heading, $intro, $inner) {
    $logo   = 'https://gvbasketball.com/wp-content/uploads/2026/07/gv-logo-crest.png';
    $navy   = '#123B78'; $orange = '#F47B20'; $steel = '#6B6F76';
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
    <tr><td style="padding:22px 36px 0;font-family:Arial,Helvetica,sans-serif;">{$inner}</td></tr>
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

/**
 * Build HTML for parent receipt email.
 */
function gv_members_parent_receipt_html(array $booking_data, array $meta): string {
    $player = esc_html($booking_data['player_name'] ?? '');
    $interest = esc_html(gv_members_interest_label($booking_data['training_interest'] ?? ''));
    $venue = esc_html($booking_data['venue_name'] ?? '');
    $day = esc_html($booking_data['day'] ?? '');
    $ref = esc_html($booking_data['booking_code'] ?? '');
    
    $status_label = 'Request received';
    $opt_in = $meta['member_opt_in'] ?? ($booking_data['member_opt_in'] ?? 'no');
    
    $cta = '';
    if ($opt_in === 'yes') {
        $members_url = esc_url(home_url('/members/'));
        $cta = <<<HTML
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px;margin-bottom:10px;">
            <tr><td align="center">
                <a href="{$members_url}" style="display:inline-block;background:#F47B20;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-weight:700;font-size:14px;text-decoration:none;padding:13px 26px;border-radius:8px;">Access Members Site</a>
                <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#6B6F76;margin-top:9px;">
                    You can view the status of your request at any time using your email to log in.
                </div>
            </td></tr>
        </table>
HTML;
    }

    $inner = <<<HTML
    <div style="font-size:15px;line-height:1.6;color:#1C1C1E;">
        <p style="margin:0 0 16px;">We have received your consultation request! Coach Gino will review your request and coordinate the exact time with you shortly.</p>
        
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#1C1C1E;border-collapse:collapse;margin-top:16px;width:100%;">
            <tr>
                <td style="padding:9px 12px;background:#f0f4fa;border:1px solid #E6E7E9;font-weight:700;color:#123B78;width:40%;">Status</td>
                <td style="padding:9px 12px;border:1px solid #E6E7E9;font-weight:700;color:#F47B20;">{$status_label}</td>
            </tr>
            <tr>
                <td style="padding:9px 12px;background:#f0f4fa;border:1px solid #E6E7E9;font-weight:700;color:#123B78;">Player</td>
                <td style="padding:9px 12px;border:1px solid #E6E7E9;">{$player} (Age {$booking_data['player_age']})</td>
            </tr>
            <tr>
                <td style="padding:9px 12px;background:#f0f4fa;border:1px solid #E6E7E9;font-weight:700;color:#123B78;">Training Interest</td>
                <td style="padding:9px 12px;border:1px solid #E6E7E9;">{$interest}</td>
            </tr>
            <tr>
                <td style="padding:9px 12px;background:#f0f4fa;border:1px solid #E6E7E9;font-weight:700;color:#123B78;">Venue</td>
                <td style="padding:9px 12px;border:1px solid #E6E7E9;">{$venue}</td>
            </tr>
            <tr>
                <td style="padding:9px 12px;background:#f0f4fa;border:1px solid #E6E7E9;font-weight:700;color:#123B78;">Preferred Day</td>
                <td style="padding:9px 12px;border:1px solid #E6E7E9;">{$day}</td>
            </tr>
            <tr>
                <td style="padding:9px 12px;background:#f0f4fa;border:1px solid #E6E7E9;font-weight:700;color:#123B78;">Reference Code</td>
                <td style="padding:9px 12px;border:1px solid #E6E7E9;font-family:monospace;font-weight:bold;">{$ref}</td>
            </tr>
        </table>
        
        {$cta}
        
        <p style="margin:20px 0 0;font-size:13px;line-height:1.5;color:#6B6F76;">
            Note: This request is not yet confirmed. Coach Gino will coordinate the exact time with you, which will then appear on your member dashboard.
        </p>
    </div>
HTML;

    return gv_members_email_shell('Consultation Request Received', 'We are reviewing your request details.', $inner);
}

/**
 * Build HTML for Coach Gino operational email.
 */
function gv_members_coach_request_html(array $booking_data, array $meta, string $finalize_url): string {
    $player = esc_html($booking_data['player_name'] ?? '');
    $age = (int) ($booking_data['player_age'] ?? 0);
    $interest = esc_html(gv_members_interest_label($booking_data['training_interest'] ?? ''));
    $venue = esc_html($booking_data['venue_name'] ?? '');
    $day = esc_html($booking_data['day'] ?? '');
    $ref = esc_html($booking_data['booking_code'] ?? '');
    $parent = esc_html($booking_data['parent_name'] ?? '');
    $email = esc_html($booking_data['parent_email'] ?? '');
    $phone_ig = esc_html($booking_data['contact_alt'] ?? '');
    $note = esc_html($booking_data['note'] ?? '');
    
    $rows = [
        'Parent / Guardian' => $parent,
        'Email'             => $email,
        'Phone / Instagram' => $phone_ig ? $phone_ig : '&mdash;',
        'Player'            => "{$player} (Age {$age})",
        'Training Interest' => $interest,
        'Venue'             => $venue,
        'Preferred Day'     => $day,
        'Reference Code'    => "<strong style=\"font-family:monospace;\">{$ref}</strong>",
        'Note'              => $note ? nl2br($note) : '&mdash;',
    ];
    
    $tbl = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#1C1C1E;border-collapse:collapse;margin-bottom:20px;width:100%;">';
    foreach ($rows as $k => $v) {
        $tbl .= '<tr>'
              . '<td style="padding:9px 12px;background:#f0f4fa;border:1px solid #E6E7E9;font-weight:700;color:#123B78;width:40%;vertical-align:top;">' . $k . '</td>'
              . '<td style="padding:9px 12px;border:1px solid #E6E7E9;vertical-align:top;">' . $v . '</td>'
              . '</tr>';
    }
    $tbl .= '</table>';
    
    $finalize_url_esc = esc_url($finalize_url);
    
    $btn = <<<HTML
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px;margin-bottom:22px;">
        <tr><td align="center">
            <a href="{$finalize_url_esc}" style="display:inline-block;background:#F47B20;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-weight:700;font-size:14px;text-decoration:none;padding:13px 26px;border-radius:8px;">Finalize Consultation</a>
        </td></tr>
    </table>
HTML;

    $inner = <<<HTML
    <div style="font-size:15px;line-height:1.6;color:#1C1C1E;">
        <p style="margin:0 0 16px;">A new consultation request has been submitted. Follow the workflow below to finalize the time with the parent:</p>
        
        {$tbl}
        
        <h3 style="color:#123B78;margin:16px 0 8px;font-size:16px;">Operational Workflow:</h3>
        <ol style="margin:0 0 20px;padding-left:20px;line-height:1.6;">
            <li><strong>1. Review</strong> the requested venue, day, player, and training interest.</li>
            <li><strong>2. Contact</strong> the parent using email or the optional phone/Instagram detail.</li>
            <li><strong>3. Agree</strong> on the exact 45-minute consultation time.</li>
            <li><strong>4. Open</strong> the <a href="{$finalize_url_esc}" style="color:#123B78;text-decoration:underline;">Finalize Consultation</a> screen.</li>
            <li><strong>5. Select</strong> an available exact time and click <strong>Confirm Booking</strong>.</li>
            <li><strong>6. Personally send</strong> the final schedule to the parent.</li>
        </ol>
        
        {$btn}
        
        <p style="margin:20px 0 0;font-size:13px;line-height:1.5;color:#6B6F76;border-left:3px solid #F47B20;padding-left:10px;">
            <strong>Warning:</strong> Confirming the time on the website updates the member portal but <strong>does not send an automatic final confirmation</strong> email to the parent. You must contact them personally.
        </p>
    </div>
HTML;

    return gv_members_coach_request_html_shell_wrap('New Consultation Request', 'A new request is waiting for your finalization.', $inner);
}

function gv_members_coach_request_html_shell_wrap($heading, $intro, $inner) {
    return gv_members_email_shell($heading, $intro, $inner);
}
