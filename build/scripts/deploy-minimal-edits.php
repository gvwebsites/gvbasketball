<?php
// Run on host: wp eval-file deploy-minimal-edits.php
// Requires home.html, about.html, training-programs.html, faq.html, elite-academy.html scp'd to $HOME/pages.
$dir = getenv('HOME') . '/pages';
$aid = gv_ensure_page('elite-academy', 'GV Elite Academy');
if (!$aid) { echo "FAILED to ensure elite-academy page\n"; return; }
$map = array(
  2887 => 'home.html',
  26   => 'about.html',
  2981 => 'training-programs.html',
  2988 => 'faq.html',
  $aid => 'elite-academy.html',
);
foreach ($map as $id => $f) {
    $p = "$dir/$f";
    if (!file_exists($p)) { echo "MISSING $f\n"; continue; }
    echo "$f: " . gv_set_page_html($id, file_get_contents($p)) . "\n";
}
echo "elite-academy=$aid\n";
