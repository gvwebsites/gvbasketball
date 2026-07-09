<?php
// Helper: create/update a WPForms form idempotently by title; returns post id.
function gv_make_form($title, $fields, $settings) {
    $existing = get_posts(array('post_type'=>'wpforms','title'=>$title,'post_status'=>'any','numberposts'=>1,'fields'=>'ids'));
    if ($existing) { $id = $existing[0]; }
    else { $id = wp_insert_post(array('post_title'=>$title,'post_type'=>'wpforms','post_status'=>'publish','post_content'=>'')); }
    $form = array('id'=>$id, 'field_id'=>count($fields)+1, 'fields'=>$fields, 'settings'=>$settings, 'meta'=>array('template'=>'blank'));
    wp_update_post(array('ID'=>$id,'post_content'=>wp_slash(wp_json_encode($form))));
    return $id;
}

// ===== Newsletter form =====
$news_id = gv_make_form('GV Newsletter',
  array('1'=>array('id'=>'1','type'=>'email','label'=>'Email','required'=>'1','size'=>'large','placeholder'=>'Your email address','label_hide'=>'1')),
  array(
    'form_title'=>'Get Training Tips & Updates',
    'form_desc'=>'Join the GV Basketball newsletter for drills, tips, and announcements.',
    'submit_text'=>'Subscribe',
    'submit_text_processing'=>'Subscribing...',
    'antispam_v3'=>'1',
    'notification_enable'=>'1',
    'notifications'=>array('1'=>array('notification_name'=>'New Subscriber','email'=>'gvbasketballcoaching@gmail.com','subject'=>'New newsletter subscriber','sender_name'=>'GV Basketball','sender_address'=>'info@gvbasketball.com','replyto'=>'{field_id="1"}','message'=>'{all_fields}')),
    'confirmations'=>array('1'=>array('type'=>'message','message'=>'<p>You\'re on the list — see you on the court!</p>','message_scroll'=>'1')),
  )
);
echo "newsletter_id=$news_id\n";

// ===== Waiver form =====
$waiver_id = gv_make_form('GV Player Waiver',
  array(
    '1'=>array('id'=>'1','type'=>'name','label'=>'Athlete Full Name','format'=>'simple','required'=>'1','size'=>'medium'),
    '2'=>array('id'=>'2','type'=>'text','label'=>'Athlete Date of Birth','required'=>'1','size'=>'medium','placeholder'=>'MM/DD/YYYY'),
    '3'=>array('id'=>'3','type'=>'text','label'=>'Parent / Guardian Name','required'=>'1','size'=>'medium'),
    '4'=>array('id'=>'4','type'=>'email','label'=>'Email','required'=>'1','size'=>'medium'),
    '5'=>array('id'=>'5','type'=>'text','label'=>'Phone','required'=>'1','size'=>'medium'),
    '6'=>array('id'=>'6','type'=>'checkbox','label'=>'Agreement','required'=>'1','size'=>'medium','choices'=>array(
        '1'=>array('label'=>'I have read and agree to the GV Basketball liability waiver, assumption of risk, and training policies, and I consent to emergency medical treatment for my child if needed.'))),
    '7'=>array('id'=>'7','type'=>'checkbox','label'=>'Media Release (optional)','required'=>'0','size'=>'medium','choices'=>array(
        '1'=>array('label'=>'I grant permission for GV Basketball to use photos/video of my child for promotional purposes.'))),
    '8'=>array('id'=>'8','type'=>'text','label'=>'Signature (type full name)','required'=>'1','size'=>'medium'),
    '9'=>array('id'=>'9','type'=>'text','label'=>'Date','required'=>'1','size'=>'medium','placeholder'=>'MM/DD/YYYY'),
  ),
  array(
    'form_title'=>'GV Player Waiver',
    'submit_text'=>'Submit Waiver',
    'submit_text_processing'=>'Submitting...',
    'antispam_v3'=>'1',
    'notification_enable'=>'1',
    'notifications'=>array('1'=>array('notification_name'=>'New Waiver','email'=>'gvbasketballcoaching@gmail.com','subject'=>'New signed waiver — GV Basketball','sender_name'=>'GV Basketball','sender_address'=>'info@gvbasketball.com','replyto'=>'{field_id="4"}','message'=>'{all_fields}')),
    'confirmations'=>array('1'=>array('type'=>'message','message'=>'<p>Thank you — your waiver has been received. See you at training!</p>','message_scroll'=>'1')),
  )
);
echo "waiver_id=$waiver_id\n";

// ===== Waiver page =====
$waiver_page = gv_ensure_page('waiver', 'Player Waiver & Consent');
$waiver_a = <<<'HTML'
<div class="gv-page">
<section class="gv-hero">
<div class="gv-wrap"><div class="gv-hero__inner" style="padding:88px 0 60px;max-width:760px;">
<span class="gv-eyebrow">Before You Train</span>
<h1 class="gv-h1">Player Waiver &amp; Consent</h1>
<div class="gv-hero__rule" style="margin-top:20px;"></div>
<p class="gv-lead">Please complete this form before your athlete's first session. It confirms consent and helps us keep every player safe.</p>
</div></div></section>
<section class="gv-section" style="padding-bottom:14px;"><div class="gv-wrap">
<div class="gv-head-block gv-center" style="margin-bottom:0;"><span class="gv-eyebrow">Consent Form</span><h2 class="gv-section-title">Complete the Waiver</h2><p class="gv-lead">Fill in the details below. A copy is sent to our team automatically.</p></div>
</div></section>
</div>
HTML;
$waiver_cta = <<<'HTML'
<div class="gv-page"><section class="gv-section gv-section--tight"><div class="gv-wrap"><div class="gv-cta">
<span class="gv-eyebrow">Questions?</span><h2 class="gv-section-title">We're Here To Help</h2>
<p class="gv-lead">Not sure about anything on the form? Reach out before your first session.</p>
<div class="gv-btn-row" style="justify-content:center;"><a class="gv-btn gv-btn--primary" href="https://ig.me/m/gvbasketballl" target="_blank" rel="noopener">Message on Instagram</a><a class="gv-btn gv-btn--ghost" href="/contact/">Contact</a></div>
</div></div></section></div>
HTML;
echo gv_set_page_blocks($waiver_page, array(
  array('type'=>'html','content'=>$waiver_a),
  array('type'=>'shortcode','content'=>'[wpforms id="'.$waiver_id.'"]','css'=>'gv-waiverform-wrap'),
  array('type'=>'html','content'=>$waiver_cta),
)) . "\n";

// ===== Rebuild footer with newsletter band on top =====
$footer_html = file_get_contents(getenv('HOME') . '/footer.html');
echo gv_set_theme_part_blocks('GV Footer', 'footer', array(
  array('type'=>'shortcode','content'=>'[wpforms id="'.$news_id.'" title="true" description="true"]','css'=>'gv-newsletter-band'),
  array('type'=>'html','content'=>$footer_html),
)) . "\n";

// keep footer registered in conditions
$fid = get_posts(array('post_type'=>'elementor_library','title'=>'GV Footer','numberposts'=>1,'post_status'=>'any','fields'=>'ids'));
$fid = $fid ? $fid[0] : 0;
$c = get_option('elementor_pro_theme_builder_conditions', array());
if (!is_array($c)) $c = array();
$c['footer'] = array($fid => array('include/general'));
update_option('elementor_pro_theme_builder_conditions', $c);
echo "footer_id=$fid conditions ok\n";
