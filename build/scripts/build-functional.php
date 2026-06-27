<?php
// ===== Shared CTA band =====
$cta = <<<'HTML'
<div class="gv-page"><section class="gv-section gv-section--tight"><div class="gv-wrap"><div class="gv-cta">
<span class="gv-eyebrow">Start Your Development Journey</span>
<h2 class="gv-section-title">Ready To Build A Better Player?</h2>
<p class="gv-lead">Book a consultation and we'll map out the right path for your athlete.</p>
<div class="gv-btn-row" style="justify-content:center;">
<a class="gv-btn gv-btn--primary" href="/book-a-consultation/">Book a Consultation</a>
<a class="gv-btn gv-btn--ghost" href="https://wa.me/639178824466" target="_blank" rel="noopener">WhatsApp Us</a>
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
      'email'=>'info@gvbasketball.com',
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
<section class="gv-hero"><div class="gv-hero__bg" style="background-image:url('https://gvbasketball.com/wp-content/uploads/2025/07/GV-Basketball-Hero.jpeg');"></div><div class="gv-hero__overlay"></div>
<div class="gv-wrap"><div class="gv-hero__inner" style="padding:88px 0 64px;max-width:760px;">
<span class="gv-eyebrow">Book a Consultation</span>
<h1 class="gv-h1">Start Your Player's Journey</h1>
<div class="gv-hero__rule" style="margin-top:20px;"></div>
<p class="gv-lead">Reserve a Player Consultation with Coach Gino. We'll talk through your athlete's goals and map the right path forward.</p>
</div></div></section>
<section class="gv-section"><div class="gv-wrap">
<div class="gv-head-block gv-center"><span class="gv-eyebrow">What We'll Discuss</span><h2 class="gv-section-title">Your Player Consultation</h2><p class="gv-lead">A focused conversation to understand your athlete and recommend the best-fit program.</p></div>
<div class="gv-grid gv-grid--3">
<div class="gv-card"><h3 class="gv-card__title">Goals &amp; Aspirations</h3><p>What your athlete wants to achieve — and how we'll get there.</p></div>
<div class="gv-card"><h3 class="gv-card__title">Current Skill Level</h3><p>Where your athlete is today and the gaps to close first.</p></div>
<div class="gv-card"><h3 class="gv-card__title">Training History</h3><p>Past experience so we build on the right foundation.</p></div>
<div class="gv-card"><h3 class="gv-card__title">Recommended Plan</h3><p>A clear, personalized development direction.</p></div>
<div class="gv-card"><h3 class="gv-card__title">Schedule Options</h3><p>Times and locations (Makati &amp; Ortigas) that fit your week.</p></div>
<div class="gv-card"><h3 class="gv-card__title">Best-Fit Program</h3><p>Private, Small Group, or Elite Performance.</p></div>
</div>
<div class="gv-head-block gv-center" style="margin-top:54px;margin-bottom:0;"><span class="gv-eyebrow">Reserve Your Slot</span><h2 class="gv-section-title">Pick a Date &amp; Time</h2><p class="gv-lead">Choose a slot below. You'll receive a confirmation by email — pricing is shared during the consultation.</p></div>
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
<p class="gv-center gv-lead" style="margin:30px auto 0;">Prefer to chat first? <a href="https://wa.me/639178824466" target="_blank" rel="noopener" style="color:#fff;text-decoration:underline;">Message us on WhatsApp</a>.</p>
</div></section>
</div>
HTML;
echo gv_set_page_blocks(2982, array(
  array('type'=>'html','content'=>$book_a),
  array('type'=>'shortcode','content'=>'[latepoint_book_form]','css'=>'gv-bookform-wrap'),
  array('type'=>'html','content'=>$book_c),
)) . "\n";

// ===== 3) Member Booking portal (2983) =====
$port_a = <<<'HTML'
<div class="gv-page">
<section class="gv-hero"><div class="gv-hero__bg" style="background-image:url('https://gvbasketball.com/wp-content/uploads/2025/07/GV-Basketball-Hero.jpeg');"></div><div class="gv-hero__overlay"></div>
<div class="gv-wrap"><div class="gv-hero__inner" style="padding:88px 0 64px;max-width:760px;">
<span class="gv-eyebrow">Member Portal</span>
<h1 class="gv-h1">Member Booking</h1>
<div class="gv-hero__rule" style="margin-top:20px;"></div>
<p class="gv-lead">Log in to book sessions, reschedule within policy, and view your upcoming training. New here? Start with a consultation.</p>
<div class="gv-btn-row"><a class="gv-btn gv-btn--primary" href="/book-a-consultation/">Book a Consultation</a></div>
</div></div></section>
</div>
HTML;
$port_c = <<<'HTML'
<div class="gv-page">
<section class="gv-section"><div class="gv-wrap"><div class="gv-head-block gv-center">
<span class="gv-eyebrow">Need Help?</span><h2 class="gv-section-title">We're Here For You</h2>
<p class="gv-lead">Questions about your sessions or schedule? Reach out anytime.</p>
<div class="gv-btn-row" style="justify-content:center;">
<a class="gv-btn gv-btn--outline" href="https://wa.me/639178824466" target="_blank" rel="noopener">WhatsApp Us</a>
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
<section class="gv-hero"><div class="gv-hero__bg" style="background-image:url('https://gvbasketball.com/wp-content/uploads/2025/07/GV-Basketball-Hero.jpeg');"></div><div class="gv-hero__overlay"></div>
<div class="gv-wrap"><div class="gv-hero__inner" style="padding:88px 0 64px;max-width:760px;">
<span class="gv-eyebrow">Get In Touch</span>
<h1 class="gv-h1">Contact GV Basketball</h1>
<div class="gv-hero__rule" style="margin-top:20px;"></div>
<p class="gv-lead">Questions about training, schedules, or programs? We'd love to hear from you.</p>
</div></div></section>
<section class="gv-section"><div class="gv-wrap">
<div class="gv-contact-grid">
<div class="gv-contact-item"><div class="gv-contact-item__ic">✆</div><div><b>WhatsApp</b><br><a href="https://wa.me/639178824466" target="_blank" rel="noopener">+63 917 882 4466</a></div></div>
<div class="gv-contact-item"><div class="gv-contact-item__ic">✉</div><div><b>Email</b><br><a href="mailto:info@gvbasketball.com">info@gvbasketball.com</a></div></div>
<div class="gv-contact-item"><div class="gv-contact-item__ic">◎</div><div><b>Instagram</b><br><a href="https://instagram.com/gvbasketballl" target="_blank" rel="noopener">@gvbasketballl</a></div></div>
<div class="gv-contact-item"><div class="gv-contact-item__ic">f</div><div><b>Facebook</b><br><a href="https://facebook.com/GvBasketball" target="_blank" rel="noopener">GV Basketball</a></div></div>
</div>
</div></section>
<section class="gv-section gv-section--light"><div class="gv-wrap">
<div class="gv-head-block gv-center"><span class="gv-eyebrow">Where We Train</span><h2 class="gv-section-title">Our Locations</h2><p class="gv-lead">Sessions run in Makati and Ortigas, Metro Manila. Your exact venue is confirmed at your consultation.</p></div>
<div class="gv-grid gv-grid--2">
<div class="gv-card"><div class="gv-card__icon">◎</div><h3 class="gv-card__title">Makati</h3><p>Skills training in Makati, Metro Manila — convenient for families across the city.</p><div style="margin-top:14px;"><a class="gv-btn gv-btn--outline" href="https://www.google.com/maps/search/?api=1&query=Makati%2C%20Metro%20Manila" target="_blank" rel="noopener">View on Google Maps</a></div></div>
<div class="gv-card"><div class="gv-card__icon">◎</div><h3 class="gv-card__title">Ortigas</h3><p>Skills training in Ortigas Center, Pasig — accessible from across Metro Manila.</p><div style="margin-top:14px;"><a class="gv-btn gv-btn--outline" href="https://www.google.com/maps/search/?api=1&query=Ortigas%20Center%2C%20Pasig" target="_blank" rel="noopener">View on Google Maps</a></div></div>
</div>
</div></section>
<section class="gv-section" style="padding-bottom:14px;"><div class="gv-wrap">
<div class="gv-head-block gv-center" style="margin-bottom:0;"><span class="gv-eyebrow">Send a Message</span><h2 class="gv-section-title">Drop Us a Line</h2></div>
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
