<?php
echo gv_set_page_html(2887, file_get_contents(getenv('HOME').'/home.html')) . "\n";
echo gv_set_page_html(2985, file_get_contents(getenv('HOME').'/success-stories.html')) . "\n";
wp_update_post(array('ID'=>2986, 'post_status'=>'draft'));
echo "testimonials page 2986 -> draft\n";

$news = get_posts(array('post_type'=>'wpforms','title'=>'GV Newsletter','numberposts'=>1,'post_status'=>'any','fields'=>'ids'));
$news = $news ? $news[0] : 0;
echo gv_set_theme_part_blocks('GV Footer','footer', array(
  array('type'=>'shortcode','content'=>'[wpforms id="'.$news.'" title="true" description="true"]','css'=>'gv-newsletter-band'),
  array('type'=>'html','content'=>file_get_contents(getenv('HOME').'/footer.html')),
)) . "\n";
$fid = get_posts(array('post_type'=>'elementor_library','title'=>'GV Footer','numberposts'=>1,'post_status'=>'any','fields'=>'ids'));
$fid = $fid ? $fid[0] : 0;
$c = get_option('elementor_pro_theme_builder_conditions', array()); if(!is_array($c)) $c=array();
$c['footer'] = array($fid => array('include/general'));
update_option('elementor_pro_theme_builder_conditions', $c);
echo "footer rebuilt $fid\n";
