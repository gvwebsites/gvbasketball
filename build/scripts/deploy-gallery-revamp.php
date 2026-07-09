<?php
/**
 * Deploy gallery revamp: update header, footer, gallery page, and menu.
 * Run on server: wp eval-file ~/deploy-gallery-revamp.php
 */
$HOME = getenv('HOME');

// 1. Deploy header
$hpath = "$HOME/header.html";
if (file_exists($hpath)) {
    echo "Deploying Header...\n";
    echo gv_set_theme_part_blocks('GV Header','header', array(array('type'=>'html','content'=>file_get_contents($hpath)))) . "\n";
    $hid = get_posts(array('post_type'=>'elementor_library','title'=>'GV Header','numberposts'=>1,'post_status'=>'any','fields'=>'ids'));
    if ($hid) {
        $hid=$hid[0];
        $c=get_option('elementor_pro_theme_builder_conditions',array());
        if(!is_array($c))$c=array();
        $c['header']=array($hid=>array('include/general'));
        update_option('elementor_pro_theme_builder_conditions',$c);
        echo "  header conditions updated (ID=$hid).\n";
    }
} else {
    echo "  ! header.html missing.\n";
}

// 2. Deploy footer
$fpath = "$HOME/footer.html";
if (file_exists($fpath)) {
    echo "Deploying Footer...\n";
    $news_id = 3005;
    $nf = get_posts(array('post_type'=>'wpforms','numberposts'=>-1,'post_status'=>'any','fields'=>'ids'));
    foreach ($nf as $fid) { if (stripos(get_the_title($fid),'newsletter')!==false){ $news_id=$fid; break; } }
    echo "  Using newsletter WPForms ID = $news_id\n";
    echo gv_set_theme_part_blocks('GV Footer', 'footer', array(
        array('type'=>'shortcode','content'=>'[wpforms id="'.$news_id.'" title="true" description="true"]','css'=>'gv-newsletter-band'),
        array('type'=>'html','content'=>file_get_contents($fpath)),
    )) . "\n";
    $fid = get_posts(array('post_type'=>'elementor_library','title'=>'GV Footer','numberposts'=>1,'post_status'=>'any','fields'=>'ids'));
    if ($fid) {
        $fid=$fid[0];
        $c=get_option('elementor_pro_theme_builder_conditions',array());
        if(!is_array($c))$c=array();
        $c['footer']=array($fid=>array('include/general'));
        update_option('elementor_pro_theme_builder_conditions',$c);
        echo "  footer conditions updated (ID=$fid).\n";
    }
} else {
    echo "  ! footer.html missing.\n";
}

// 3. Deploy Gallery Page
$gpath = "$HOME/gallery.html";
if (file_exists($gpath)) {
    echo "Deploying Gallery Page (ID 2987)...\n";
    echo gv_set_page_html(2987, file_get_contents($gpath)) . "\n";
} else {
    echo "  ! gallery.html missing.\n";
}

// 4. Rebuild Astra Primary Menu
$mpath = "$HOME/build-menu.php";
if (file_exists($mpath)) {
    echo "Rebuilding menu...\n";
    include $mpath;
} else {
    echo "  ! build-menu.php missing.\n";
}

echo "=== Deployment script complete ===\n";
