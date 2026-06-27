<?php
// Configure Elementor Global Kit: GV Basketball brand colors + fonts
$kit_id = 5;
$s = get_post_meta($kit_id, '_elementor_page_settings', true);
if (!is_array($s)) $s = array();

$s['site_name'] = 'GV Basketball';
$s['site_description'] = 'Build Better Players. Build Better People.';

$s['system_colors'] = array(
  array('_id'=>'primary',   'title'=>'Navy',        'color'=>'#123B78'),
  array('_id'=>'secondary', 'title'=>'Orange',      'color'=>'#F47B20'),
  array('_id'=>'text',      'title'=>'Charcoal',    'color'=>'#1C1C1E'),
  array('_id'=>'accent',    'title'=>'Steel Gray',  'color'=>'#6B6F76'),
);
$s['custom_colors'] = array(
  array('_id'=>'deepnavy',  'title'=>'Deep Navy',   'color'=>'#021F51'),
  array('_id'=>'lightgray', 'title'=>'Light Gray',  'color'=>'#E6E7E9'),
  array('_id'=>'silver',    'title'=>'Silver',      'color'=>'#A7A9AC'),
  array('_id'=>'white',     'title'=>'White',       'color'=>'#FFFFFF'),
);

$mk = function($id,$title,$family,$weight,$extra=array()){
  return array_merge(array(
    '_id'=>$id, 'title'=>$title,
    'typography_typography'=>'custom',
    'typography_font_family'=>$family,
    'typography_font_weight'=>$weight,
  ), $extra);
};
$s['system_typography'] = array(
  $mk('primary','Headline','Bebas Neue','400', array(
    'typography_letter_spacing'=>array('unit'=>'px','size'=>1,'sizes'=>array()),
    'typography_text_transform'=>'uppercase',
  )),
  $mk('secondary','Sub-headline','Montserrat','600'),
  $mk('text','Body','Inter','400'),
  $mk('accent','Accent','Montserrat','600'),
);

// Default body + link defaults
$s['body_typography_typography']  = 'custom';
$s['body_typography_font_family'] = 'Inter';
$s['body_typography_font_weight'] = '400';
$s['body_color'] = '#1C1C1E';
$s['link_normal_color'] = '#123B78';
$s['link_hover_color']  = '#F47B20';

update_post_meta($kit_id, '_elementor_page_settings', $s);
echo "KIT UPDATED: ".count($s['system_colors'])." sys colors, ".count($s['system_typography'])." sys fonts\n";
