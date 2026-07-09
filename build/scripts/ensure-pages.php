<?php
$pages = array(
  'about'                => 'About Coach Gino',
  'training-programs'    => 'Training Programs',
  'book-a-consultation'  => 'Book a Consultation',
  'booking'              => 'Member Booking',
  'athlete-development'  => 'Athlete Development System',
  'success-stories'      => 'Success Stories',
  'testimonials'         => 'Testimonials',
  'gallery'              => 'Gallery',
  'faq'                  => 'FAQ',
  'contact'              => 'Contact',
);
$out = array();
foreach ($pages as $slug => $title) {
    $out[$slug] = gv_ensure_page($slug, $title);
}
// Retitle the existing About page properly
if (!empty($out['about'])) {
    wp_update_post(array('ID'=>$out['about'], 'post_title'=>'About Coach Gino'));
}
wp_update_post(array('ID'=>2887, 'post_title'=>'Home'));
$out['home'] = 2887;
foreach ($out as $s => $i) echo "$s=$i\n";
