<?php
/**
 * Target page-2982 (Consultation landing page) fallback configuration.
 * Run via: wp eval-file configure-consultation-page.php
 */

if (!function_exists('gv_set_page_blocks') || !class_exists('OsServiceModel')) {
    fwrite(STDERR, "Required helpers or classes not found. Make sure this runs under wp eval-file.\n");
    exit(1);
}

$page_id = 2982;
$post = get_post($page_id);
if (!$post) {
    fwrite(STDERR, "Page 2982 is absent.\n");
    exit(1);
}

// Find existing Player Consultation service
$service = (new OsServiceModel())->where(['name' => 'Player Consultation'])->set_limit(1)->get_results_as_models();
if (!$service || $service->is_new_record()) {
    fwrite(STDERR, "Player Consultation service not found.\n");
    exit(1);
}

// Ensure the page status is publish and slug is book-a-consultation
wp_update_post(array(
    'ID'          => $page_id,
    'post_status' => 'publish',
    'post_name'   => 'book-a-consultation',
));

$book_a = <<<'HTML'
<div class="gv-page">
<section class="gv-hero"><div class="gv-hero__bg" style="background-image:url('https://gvbasketball.com/wp-content/uploads/2026/07/gv-about-hero-real.webp');"></div><div class="gv-hero__overlay"></div>
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

$booking_flow = <<<'HTML'
<section class="gv-section gv-section--light"><div class="gv-wrap">
<div class="gv-head-block gv-center"><span class="gv-eyebrow">How Booking Works</span><h2 class="gv-section-title">Simple, Personal, Secure</h2><p class="gv-lead">Booking starts here on the site — everything else we handle with you directly.</p></div>
<div class="gv-flow">
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Book Online</h4><p>Choose your session or consultation and submit your details.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>We Confirm</h4><p>GV Basketball reaches out to finalize your slot and answer questions.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Reserve Your Spot</h4><p>Payment is arranged directly with GV Basketball — handled personally, not on this site.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Booking Confirmed</h4><p>Once payment is received, your session is locked in.</p></div>
<div class="gv-flow__step"><span class="gv-flow__num"></span><h4>Train</h4><p>Show up and get to work — your development starts.</p></div>
</div>
<p class="gv-flow__note">Payments are handled directly with GV Basketball — no payment or bank details are collected on this website.</p>
</div></section>
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

$shortcode = '[latepoint_book_form selected_service="' . (int)$service->id . '" hide_side_panel="yes"]';

$blocks = array(
    array('type' => 'html', 'content' => $book_a),
    array('type' => 'html', 'content' => $booking_flow),
    array('type' => 'shortcode', 'content' => $shortcode, 'css' => 'gv-bookform-wrap'),
    array('type' => 'html', 'content' => $book_c),
);

$res = gv_set_page_blocks($page_id, $blocks);
echo $res . "\n";
