<?php
/**
 * Deploy refine pass: GV Header (trimmed nav + login icon) + all marketing pages.
 * Functional pages + footer are handled by re-running build-functional.php / build-extras.php.
 * Per-target backups to ~/backups/. Run: wp eval-file ~/deploy-refine.php
 * Requires header.html + the page *.html scp'd to $HOME.
 */
$HOME = getenv('HOME');
$ts   = date('Y-m-d-Hi');
$bdir = "$HOME/backups";
if (!is_dir($bdir)) mkdir($bdir, 0755, true);

function bak($id, $bdir, $ts) {
    $p = get_post($id);
    if (!$p) { echo "  ! $id not found\n"; return; }
    file_put_contents("$bdir/$id-content-$ts.html", (string)$p->post_content);
    $ed = get_post_meta($id, '_elementor_data', true);
    if ($ed) file_put_contents("$bdir/$id-ed-$ts.json", is_string($ed)?$ed:json_encode($ed));
}

echo "=== HEADER ===\n";
$hpath = "$HOME/header.html";
if (file_exists($hpath)) {
    $hx = get_posts(array('post_type'=>'elementor_library','title'=>'GV Header','numberposts'=>1,'post_status'=>'any','fields'=>'ids'));
    if ($hx) { $ed = get_post_meta($hx[0],'_elementor_data',true); if ($ed) file_put_contents("$bdir/header-{$hx[0]}-ed-$ts.json", is_string($ed)?$ed:json_encode($ed)); }
    echo "  " . gv_set_theme_part_blocks('GV Header','header', array(array('type'=>'html','content'=>file_get_contents($hpath)))) . "\n";
    $hid = get_posts(array('post_type'=>'elementor_library','title'=>'GV Header','numberposts'=>1,'post_status'=>'any','fields'=>'ids'));
    if ($hid) { $hid=$hid[0]; $c=get_option('elementor_pro_theme_builder_conditions',array()); if(!is_array($c))$c=array(); $c['header']=array($hid=>array('include/general')); update_option('elementor_pro_theme_builder_conditions',$c); echo "  header_id=$hid conditions ok\n"; }
} else { echo "  ! header.html missing\n"; }

echo "=== MARKETING PAGES ===\n";
$pages = array(
    2887 => 'home.html', 26 => 'about.html', 2981 => 'training-programs.html',
    2984 => 'athlete-development.html', 2985 => 'success-stories.html',
    2988 => 'faq.html', 2987 => 'gallery.html', 2986 => 'testimonials.html',
);
foreach ($pages as $id => $f) {
    $p = "$HOME/$f";
    if (!file_exists($p)) { echo "  ! missing $f\n"; continue; }
    bak($id, $bdir, $ts);
    echo "  [$id] $f: " . gv_set_page_html($id, file_get_contents($p)) . "\n";
}
echo "=== DONE (flush separately) ===\n";
