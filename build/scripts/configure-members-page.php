<?php
/**
 * Target page-2983 (Members) migration.
 * Run via: wp eval-file configure-members-page.php
 */

if (!function_exists('gv_set_page_blocks')) {
    fwrite(STDERR, "gv_set_page_blocks helper not found. Make sure this runs under wp eval-file.\n");
    exit(1);
}

$page_id = 2983;
$post = get_post($page_id);
if (!$post) {
    fwrite(STDERR, "Page 2983 is absent.\n");
    exit(1);
}

// Update title, slug, and status
$post_data = array(
    'ID'          => $page_id,
    'post_title'  => 'Members',
    'post_name'   => 'members',
    'post_status' => 'publish',
);
wp_update_post($post_data);

// Elements of page 2983
$port_a = <<<'HTML'
<div class="gv-page">
<section class="gv-hero"><div class="gv-hero__bg" style="background-image:url('https://gvbasketball.com/wp-content/uploads/2026/07/gv-about-hero-real.webp');"></div><div class="gv-hero__overlay"></div>
<div class="gv-wrap"><div class="gv-hero__inner" style="padding:88px 0 72px;max-width:760px;">
<span class="gv-eyebrow">Member Portal</span>
<h1 class="gv-h1">Members Portal</h1>
<div class="gv-hero__rule" style="margin-top:24px;"></div>
<p class="gv-lead">Log in to view your consultation schedule and session history. Need to change a day? Just message us and the team will take care of it. New here? Start with a consultation.</p>
<div class="gv-btn-row"><a class="gv-btn gv-btn--primary" href="/book-a-consultation/" data-gv-consultation>Book a Consultation</a></div>
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

$blocks = array(
    array('type' => 'html', 'content' => $port_a),
    array('type' => 'shortcode', 'content' => '[gv_members_portal]', 'css' => 'gv-dash-wrap'),
    array('type' => 'html', 'content' => $port_c),
);

$res = gv_set_page_blocks($page_id, $blocks);
echo $res . "\n";
