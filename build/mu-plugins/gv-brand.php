<?php
/*
Plugin Name: GV Basketball Brand Styles
Description: Loads the GV Basketball design-system CSS site-wide. Managed by build.
Version: 1.0
*/
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
    $file = WPMU_PLUGIN_DIR . '/gv-assets/gv-brand.css';
    $url  = content_url('mu-plugins/gv-assets/gv-brand.css');
    $ver  = file_exists($file) ? filemtime($file) : '1.0';
    wp_enqueue_style('gv-brand', $url, array(), $ver);
}, 20);
