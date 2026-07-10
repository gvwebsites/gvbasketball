<?php
/**
 * Deploy revamp (non-image): apply changed marketing pages + footer template.
 * Lightweight per-target backups to ~/backups/ (avoids mysqldump).
 * Run on server:  wp eval-file ~/deploy-revamp.php
 * Requires the page HTML files + footer.html already scp'd to $HOME.
 */
$HOME = getenv('HOME');
$ts   = date('Y-m-d-Hi');
$bdir = $HOME . '/backups';
if (!is_dir($bdir)) mkdir($bdir, 0755, true);

function bak_post($id, $bdir, $ts) {
    $p = get_post($id);
    if (!$p) { echo "  ! page $id not found\n"; return false; }
    file_put_contents("$bdir/$id-content-$ts.html", (string)$p->post_content);
    $ed = get_post_meta($id, '_elementor_data', true);
    if ($ed) file_put_contents("$bdir/$id-elementor_data-$ts.json", is_string($ed)?$ed:json_encode($ed));
    return true;
}

$pages = array(
    2887 => 'home.html',
    26   => 'about.html',
    2981 => 'training-programs.html',
    2984 => 'athlete-development.html',
    2985 => 'success-stories.html',
);

echo "=== PAGES ===\n";
foreach ($pages as $id => $file) {
    $path = "$HOME/$file";
    if (!file_exists($path)) { echo "  ! missing $path — skipped\n"; continue; }
    bak_post($id, $bdir, $ts);
    $html = file_get_contents($path);
    echo "  [$id] $file: ";
    echo gv_set_page_html($id, $html);
    echo "\n";
}

echo "=== FOOTER ===\n";
$fpath = "$HOME/footer.html";
if (file_exists($fpath)) {
    // backup current footer template
    $existing = get_posts(array('post_type'=>'elementor_library','title'=>'GV Footer','numberposts'=>1,'post_status'=>'any','fields'=>'ids'));
    if ($existing) { $fid0=$existing[0]; $ed=get_post_meta($fid0,'_elementor_data',true); if($ed) file_put_contents("$bdir/footer-$fid0-elementor_data-$ts.json", is_string($ed)?$ed:json_encode($ed)); }
    $footer_html = file_get_contents($fpath);
    echo "  ";
    echo gv_set_theme_part_blocks('GV Footer', 'footer', array(
        array('type'=>'html','content'=>$footer_html),
    ));
    echo "\n";
    // keep footer registered in theme builder conditions
    $fid = get_posts(array('post_type'=>'elementor_library','title'=>'GV Footer','numberposts'=>1,'post_status'=>'any','fields'=>'ids'));
    if ($fid) { $fid=$fid[0]; $c=get_option('elementor_pro_theme_builder_conditions',array()); $c['footer']=array($fid=>array('include/general')); update_option('elementor_pro_theme_builder_conditions',$c); echo "  footer_id=$fid conditions ok\n"; }
} else {
    echo "  ! footer.html missing — skipped\n";
}

echo "=== DONE (flush separately) ===\n";
