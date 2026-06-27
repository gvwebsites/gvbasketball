<?php
$dir = getenv('HOME') . '/pages';
$map = array(
  26   => 'about.html',
  2981 => 'training-programs.html',
  2984 => 'athlete-development.html',
  2985 => 'success-stories.html',
  2986 => 'testimonials.html',
  2987 => 'gallery.html',
  2988 => 'faq.html',
);
foreach ($map as $id => $f) {
    $p = "$dir/$f";
    if (!file_exists($p)) { echo "MISSING $f\n"; continue; }
    echo gv_set_page_html($id, file_get_contents($p)) . "\n";
}
