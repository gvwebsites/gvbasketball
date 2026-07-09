<?php
// ===== Shared CTA band =====
$cta = <<<'HTML'
<div class="gv-page"><section class="gv-section gv-section--tight"><div class="gv-wrap"><div class="gv-cta">
<span class="gv-eyebrow">Start Your Development Journey</span>
<h2 class="gv-section-title">Ready To Build A Better Player?</h2>
<p class="gv-lead">Book a consultation and we'll map out the right path for your athlete.</p>
<div class="gv-btn-row" style="justify-content:center;">
<a class="gv-btn gv-btn--gold" href="/training-programs/">Book a Consultation</a>
<a class="gv-btn gv-btn--ghost" href="https://ig.me/m/gvbasketballl" target="_blank" rel="noopener">Message on Instagram</a>
</div></div></div></section></div>
HTML;

// ===== 1) WPForms contact form =====
$form = array(
  'id' => 0,
  'field_id' => 5,
  'fields' => array(
    '1' => array('id'=>'1','type'=>'name','label'=>'Name','format'=>'simple','required'=>'1','size'=>'medium'),
    '2' => array('id'=>'2','type'=>'email','label'=>'Email','required'=>'1','size'=>'medium'),
    '3' => array('id'=>'3','type'=>'text','label'=>'Phone','required'=>'0','size'=>'medium'),
    '4' => array('id'=>'4','type'=>'textarea','label'=>'Message','required'=>'1','size'=>'medium'),
  ),
  'settings' => array(
    'form_title' => 'Contact GV Basketball',
    'submit_text' => 'Send Message',
    'submit_text_processing' => 'Sending...',
    'antispam_v3' => '1',
    'notification_enable' => '1',
    'notifications' => array('1'=>array(
      'notification_name'=>'Admin Notification',
      'email'=>'gvbasketballcoaching@gmail.com',
      'subject'=>'New contact — GV Basketball website',
      'sender_name'=>'GV Basketball',
      'sender_address'=>'info@gvbasketball.com',
      'replyto'=>'{field_id="2"}',
      'message'=>'{all_fields}',
    )),
    'confirmations' => array('1'=>array(
      'type'=>'message',
      'message'=>'<p>Thanks for reaching out! We\'ll get back to you shortly.</p>',
      'message_scroll'=>'1',
    )),
  ),
  'meta' => array('template'=>'blank'),
);
$existing = get_posts(array('post_type'=>'wpforms','title'=>'Contact GV Basketball','post_status'=>'any','numberposts'=>1,'fields'=>'ids'));
if ($existing) { $form_id = $existing[0]; }
else { $form_id = wp_insert_post(array('post_title'=>'Contact GV Basketball','post_type'=>'wpforms','post_status'=>'publish','post_content'=>'')); }
$form['id'] = $form_id;
wp_update_post(array('ID'=>$form_id,'post_content'=>wp_slash(wp_json_encode($form))));
echo "wpforms_id=$form_id\n";

// ===== 2) Book a Consultation (2982) =====
$book_a = <<<'HTML'
<div class="gv-page">
<section class="gv-hero">
<div class="gv-wrap"><div class="gv-hero__inner" style="padding:88px 0 72px;max-width:760px;">
<span class="gv-eyebrow">Book a Consultation</span>
<h1 class="gv-h1">Start Your Player's Journey</h1>
<div class="gv-hero__rule" style="margin-top:24px;"></div>
<p class="gv-lead">Tell Coach Gino about your athlete and your preferred days and times. We'll follow up to confirm and map the right path forward.</p>
</div></div></section>
<section class="gv-section"><div class="gv-wrap">
<div class="gv-head-block gv-center"><span class="gv-eyebrow">What We'll Discuss</span><h2 class="gv-section-title">Your Player Consultation</h2><p class="gv-lead">A focused conversation to understand your athlete and recommend the best-fit program.</p></div>
<div class="gv-grid gv-grid--3">
<div class="gv-card"><h3 class="gv-card__title">Goals &amp; Aspirations</h3><p>What your athlete wants to achieve — and how we'll get there.</p></div>
<div class="gv-card"><h3 class="gv-card__title">Current Skill Level</h3><p>Where your athlete is today and the gaps to close first.</p></div>
<div class="gv-card"><h3 class="gv-card__title">Training History</h3><p>Past experience so we build on the right foundation.</p></div>
<div class="gv-card"><h3 class="gv-card__title">Recommended Plan</h3><p>A clear, personalized development direction.</p></div>
<div class="gv-card"><h3 class="gv-card__title">Schedule Options</h3><p>Small-group days across Metro Manila (Dasma Makati, Urdaneta Village, Corinthian Gardens); Private &amp; Elite by appointment.</p></div>
<div class="gv-card"><h3 class="gv-card__title">Best-Fit Program</h3><p>Private, Small Group, or Elite Performance.</p></div>
</div>
<div class="gv-head-block gv-center" style="margin-top:54px;margin-bottom:0;"><span class="gv-eyebrow">Tell Us About Your Player</span><h2 class="gv-section-title">Book a Consultation</h2><p class="gv-lead">Send us a few details and your preferred days and times. Coach Gino's team will follow up to confirm — pricing is shared during your consultation.</p></div>
</div></section>
</div>
HTML;
$book_c = <<<'HTML'
<div class="gv-page">
<section class="gv-section gv-section--navy"><div class="gv-wrap">
<div class="gv-head-block gv-center"><span class="gv-eyebrow">What Happens Next</span><h2 class="gv-section-title">From Consultation To Court</h2></div>
<div class="gv-steps">
<div class="gv-step"><h3 class="gv-step__title">Consultation</h3><p>We confirm your slot and discuss goals, level, and the right program.</p></div>
<div class="gv-step"><h3 class="gv-step__title">First Session &amp; Evaluation</h3><p>Your first paid training session includes a full player evaluation — development starts day one.</p></div>
<div class="gv-step"><h3 class="gv-step__title">Your Plan</h3><p>A personalized development plan and your ongoing training schedule.</p></div>
</div>
<p class="gv-center gv-lead" style="margin:30px auto 0;">Prefer to chat first? <a href="https://ig.me/m/gvbasketballl" target="_blank" rel="noopener" style="color:#fff;text-decoration:underline;">Message us on Instagram</a>.</p>
</div></section>
</div>
HTML;
$booking_flow = '<section class="gv-section gv-section--light"><div class="gv-wrap">
<div class="gv-head-block gv-center"><span class="gv-eyebrow">How Booking Works</span><h2 class="gv-section-title">Simple, Personal, Secure</h2><p class="gv-lead">Booking starts here on the site — everything else we handle with you directly.</p></div>
<div class="gv-flow">
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Book Online</h4><p>Choose your session or consultation and submit your details.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>We Confirm</h4><p>GV Basketball reaches out to finalize your slot and answer questions.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Reserve Your Spot</h4><p>Payment is arranged directly with GV Basketball — handled personally, not on this site.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Booking Confirmed</h4><p>Once payment is received, your session is locked in.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Train</h4><p>Show up and get to work — your development starts.</p></div>
</div>
<p class="gv-flow__note">Payments are handled directly with GV Basketball — no payment or bank details are collected on this website.</p>
</div></section>';

// NOTE: Page 2982 (/book-a-consultation/) is 302-redirected to /training-programs/
// by gv-request-form.php. The LIVE consultation form + modal lives on page 2981
// and is owned SOLELY by build/scripts/deploy-training-programs.php.
// Do NOT deploy the training-programs form/modal from here (this HTML has no modal).
echo gv_set_page_blocks(2982, array(
  array('type'=>'html','content'=>$book_a),
  array('type'=>'html','content'=>$booking_flow),
  array('type'=>'shortcode','content'=>'[gv_request_form]','css'=>'gv-bookform-wrap'),
  array('type'=>'html','content'=>$book_c),
)) . "\n";

// ===== 3) Member Booking portal (2983) =====
$port_a = <<<'HTML'
<div class="gv-page">
<section class="gv-hero">
<div class="gv-wrap"><div class="gv-hero__inner" style="padding:88px 0 72px;max-width:760px;">
<span class="gv-eyebrow">Member Portal</span>
<h1 class="gv-h1">Member Booking</h1>
<div class="gv-hero__rule" style="margin-top:24px;"></div>
<p class="gv-lead">Log in to book sessions, reschedule within policy, and view your upcoming training. New here? Start with a consultation.</p>
<div class="gv-btn-row"><a class="gv-btn gv-btn--primary" href="/training-programs/">Book a Consultation</a></div>
</div></div></section>
</div>
HTML;
$port_c = <<<'HTML'
<div class="gv-page">
<section class="gv-section"><div class="gv-wrap"><div class="gv-head-block gv-center">
<span class="gv-eyebrow">Need Help?</span><h2 class="gv-section-title">We're Here For You</h2>
<p class="gv-lead">Questions about your sessions or schedule? Reach out anytime.</p>
<div class="gv-btn-row" style="justify-content:center;">
<a class="gv-btn gv-btn--outline" href="https://ig.me/m/gvbasketballl" target="_blank" rel="noopener">Message on Instagram</a>
<a class="gv-btn gv-btn--outline" href="/contact/">Contact</a>
</div></div></div></section>
</div>
HTML;
echo gv_set_page_blocks(2983, array(
  array('type'=>'html','content'=>$port_a),
  array('type'=>'shortcode','content'=>'[latepoint_customer_dashboard]','css'=>'gv-dash-wrap'),
  array('type'=>'html','content'=>$port_c),
)) . "\n";

// ===== 4) Contact (2989) =====
$contact_a = <<<'HTML'
<div class="gv-page">
<section class="gv-hero">
<div class="gv-wrap"><div class="gv-hero__inner" style="padding:88px 0 72px;max-width:760px;">
<span class="gv-eyebrow">Get In Touch</span>
<h1 class="gv-h1">Contact GV Basketball</h1>
<div class="gv-hero__rule" style="margin-top:24px;"></div>
<p class="gv-lead">Questions about training, schedules, or programs? We'd love to hear from you.</p>
</div></div></section>
<section class="gv-section"><div class="gv-wrap">
<div class="gv-contact-grid">
<div class="gv-contact-item"><div class="gv-contact-item__ic"><svg viewBox="0 0 24 24" aria-hidden="true"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg></div><div><b>Instagram</b><br><a href="https://ig.me/m/gvbasketballl" target="_blank" rel="noopener">Message @gvbasketballl</a></div></div>
<div class="gv-contact-item"><div class="gv-contact-item__ic"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></div><div><b>Email</b><br><a href="mailto:gvbasketballcoaching@gmail.com">gvbasketballcoaching@gmail.com</a></div></div>
</div></section>
<section class="gv-section" style="padding-bottom:14px;"><div class="gv-wrap">
<div class="gv-head-block gv-center" style="margin-bottom:0;"><span class="gv-eyebrow">Send a Message</span><h2 class="gv-section-title">Contact Us</h2></div>
</div></section>
</div>
HTML;
echo gv_set_page_blocks(2989, array(
  array('type'=>'html','content'=>$contact_a),
  array('type'=>'shortcode','content'=>'[wpforms id="'.$form_id.'"]','css'=>'gv-contactform-wrap'),
  array('type'=>'html','content'=>$cta),
)) . "\n";

// ===== 5) LatePoint accent color =====
global $wpdb; $p=$wpdb->prefix;
$wpdb->query($wpdb->prepare("DELETE FROM {$p}latepoint_settings WHERE name=%s",'accent_color'));
$wpdb->query($wpdb->prepare("INSERT INTO {$p}latepoint_settings (name,value) VALUES (%s,%s)",'accent_color','#F47B20'));
echo "accent_color set\n";
