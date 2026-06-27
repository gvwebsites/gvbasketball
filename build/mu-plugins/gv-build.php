<?php
/*
Plugin Name: GV Basketball Build Helpers
Description: Helper to inject hand-crafted HTML into an Elementor page as a full-width HTML widget. Build-time use.
Version: 1.0
*/
if (!defined('ABSPATH')) exit;

/**
 * Set an Elementor page's content to a single full-width HTML widget containing $html.
 * Keeps the page Elementor-native (editable) with Theme Builder header/footer.
 */
function gv_set_page_html($page_id, $html, $template = 'elementor_header_footer') {
    $page_id = (int) $page_id;
    if (!$page_id || !get_post($page_id)) return "ERR: page $page_id not found";

    $cid = 'gvc' . substr(md5($page_id . 'container'), 0, 6);
    $hid = 'gvh' . substr(md5($page_id . 'html'), 0, 6);

    $data = array(array(
        'id'      => $cid,
        'elType'  => 'container',
        'settings'=> array(
            'content_width' => 'full',
            'padding' => array('unit'=>'px','top'=>'0','right'=>'0','bottom'=>'0','left'=>'0','isLinked'=>true),
            'margin'  => array('unit'=>'px','top'=>'0','right'=>'0','bottom'=>'0','left'=>'0','isLinked'=>true),
        ),
        'elements'=> array(array(
            'id'        => $hid,
            'elType'    => 'widget',
            'widgetType'=> 'html',
            'settings'  => array('html' => $html),
            'elements'  => array(),
        )),
        'isInner' => false,
    ));

    update_post_meta($page_id, '_elementor_data', wp_slash(wp_json_encode($data)));
    update_post_meta($page_id, '_elementor_edit_mode', 'builder');
    update_post_meta($page_id, '_elementor_template_type', 'wp-page');
    update_post_meta($page_id, '_elementor_version', '3.30.0');
    if ($template) update_post_meta($page_id, '_wp_page_template', $template);
    delete_post_meta($page_id, '_elementor_css');

    return "OK: page $page_id set (" . strlen($html) . " bytes)";
}

/**
 * Set an Elementor page from an ordered list of blocks (html or shortcode widgets).
 * Each block: array('type'=>'html'|'shortcode', 'content'=>'...', 'css'=>'optional-class')
 */
function gv_set_page_blocks($page_id, $blocks, $template = 'elementor_header_footer') {
    $page_id = (int) $page_id;
    if (!$page_id || !get_post($page_id)) return "ERR: page $page_id not found";
    $els = array();
    foreach ($blocks as $i => $b) {
        $wid = 'gvb' . substr(md5($page_id . '_' . $i), 0, 6);
        $type = isset($b['type']) ? $b['type'] : 'html';
        $settings = array();
        if ($type === 'shortcode') { $wt = 'shortcode'; $settings['shortcode'] = $b['content']; }
        else { $wt = 'html'; $settings['html'] = $b['content']; }
        if (!empty($b['css'])) $settings['_css_classes'] = $b['css'];
        $els[] = array('id'=>$wid,'elType'=>'widget','widgetType'=>$wt,'settings'=>$settings,'elements'=>array());
    }
    $cid = 'gvc' . substr(md5($page_id . '_cb'), 0, 6);
    $data = array(array(
        'id'=>$cid,'elType'=>'container',
        'settings'=>array('content_width'=>'full','padding'=>array('unit'=>'px','top'=>'0','right'=>'0','bottom'=>'0','left'=>'0','isLinked'=>true)),
        'elements'=>$els,'isInner'=>false,
    ));
    update_post_meta($page_id, '_elementor_data', wp_slash(wp_json_encode($data)));
    update_post_meta($page_id, '_elementor_edit_mode', 'builder');
    update_post_meta($page_id, '_elementor_template_type', 'wp-page');
    update_post_meta($page_id, '_elementor_version', '3.30.0');
    if ($template) update_post_meta($page_id, '_wp_page_template', $template);
    delete_post_meta($page_id, '_elementor_css');
    return "OK blocks: page $page_id (" . count($blocks) . " widgets)";
}

/**
 * Create/update an Elementor Pro Theme Builder part (header/footer) from HTML, applied site-wide.
 */
function gv_set_theme_part($title, $type, $html) { // $type: 'header' | 'footer'
    $existing = get_posts(array(
        'post_type'   => 'elementor_library',
        'title'       => $title,
        'post_status' => 'any',
        'numberposts' => 1,
    ));
    $cid = 'gvc' . substr(md5($title . 'c'), 0, 6);
    $hid = 'gvh' . substr(md5($title . 'h'), 0, 6);
    $data = array(array(
        'id'      => $cid,
        'elType'  => 'container',
        'settings'=> array('content_width'=>'full','padding'=>array('unit'=>'px','top'=>'0','right'=>'0','bottom'=>'0','left'=>'0','isLinked'=>true)),
        'elements'=> array(array('id'=>$hid,'elType'=>'widget','widgetType'=>'html','settings'=>array('html'=>$html),'elements'=>array())),
        'isInner' => false,
    ));
    if ($existing) {
        $id = $existing[0]->ID;
        wp_update_post(array('ID'=>$id,'post_status'=>'publish'));
    } else {
        $id = wp_insert_post(array('post_title'=>$title,'post_type'=>'elementor_library','post_status'=>'publish'));
    }
    update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode($data)));
    update_post_meta($id, '_elementor_edit_mode', 'builder');
    update_post_meta($id, '_elementor_template_type', $type);
    update_post_meta($id, '_elementor_version', '3.30.0');
    update_post_meta($id, '_elementor_conditions', array('include/general'));
    wp_set_object_terms($id, $type, 'elementor_library_type');
    delete_post_meta($id, '_elementor_css');
    return "theme part '$title' ($type) = $id";
}

/**
 * Like gv_set_theme_part but builds the part from ordered blocks (html/shortcode widgets).
 */
function gv_set_theme_part_blocks($title, $type, $blocks) {
    $existing = get_posts(array('post_type'=>'elementor_library','title'=>$title,'post_status'=>'any','numberposts'=>1));
    $els = array();
    foreach ($blocks as $i => $b) {
        $wid = 'gtb' . substr(md5($title . '_' . $i), 0, 6);
        $bt = isset($b['type']) ? $b['type'] : 'html';
        $settings = array();
        if ($bt === 'shortcode') { $wt = 'shortcode'; $settings['shortcode'] = $b['content']; }
        else { $wt = 'html'; $settings['html'] = $b['content']; }
        if (!empty($b['css'])) $settings['_css_classes'] = $b['css'];
        $els[] = array('id'=>$wid,'elType'=>'widget','widgetType'=>$wt,'settings'=>$settings,'elements'=>array());
    }
    $cid = 'gtc' . substr(md5($title . 'c'), 0, 6);
    $data = array(array(
        'id'=>$cid,'elType'=>'container',
        'settings'=>array('content_width'=>'full','padding'=>array('unit'=>'px','top'=>'0','right'=>'0','bottom'=>'0','left'=>'0','isLinked'=>true)),
        'elements'=>$els,'isInner'=>false,
    ));
    if ($existing) { $id = $existing[0]->ID; wp_update_post(array('ID'=>$id,'post_status'=>'publish')); }
    else { $id = wp_insert_post(array('post_title'=>$title,'post_type'=>'elementor_library','post_status'=>'publish')); }
    update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode($data)));
    update_post_meta($id, '_elementor_edit_mode', 'builder');
    update_post_meta($id, '_elementor_template_type', $type);
    update_post_meta($id, '_elementor_version', '3.30.0');
    update_post_meta($id, '_elementor_conditions', array('include/general'));
    wp_set_object_terms($id, $type, 'elementor_library_type');
    delete_post_meta($id, '_elementor_css');
    return "theme part blocks '$title' ($type) = $id";
}

/** Ensure a published page exists with a given slug+title; returns its ID. */
function gv_ensure_page($slug, $title) {
    $existing = get_page_by_path($slug);
    if ($existing) {
        if (get_post_field('post_status', $existing->ID) !== 'publish') {
            wp_update_post(array('ID'=>$existing->ID,'post_status'=>'publish'));
        }
        return $existing->ID;
    }
    return wp_insert_post(array(
        'post_title'  => $title,
        'post_name'   => $slug,
        'post_status' => 'publish',
        'post_type'   => 'page',
    ));
}
