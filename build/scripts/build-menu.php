<?php
$menu_name = 'Primary Menu';
$existing = wp_get_nav_menu_object($menu_name);
if ($existing) wp_delete_nav_menu($existing->term_id);
$menu_id = wp_create_nav_menu($menu_name);

function gv_item($menu_id, $title, $url, $parent = 0, $classes = '') {
    return wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title'     => $title,
        'menu-item-url'       => home_url($url),
        'menu-item-status'    => 'publish',
        'menu-item-parent-id' => $parent,
        'menu-item-classes'   => $classes,
    ));
}

gv_item($menu_id, 'Home', '/');
gv_item($menu_id, 'About', '/about/');
gv_item($menu_id, 'Programs', '/training-programs/');
gv_item($menu_id, 'Gallery', '/gallery/');
gv_item($menu_id, 'FAQ', '/faq/');
gv_item($menu_id, 'Contact', '/contact/');
gv_item($menu_id, 'Book a Consultation', '/training-programs/', 0, 'gv-navcta');

$locs = get_theme_mod('nav_menu_locations', array());
$locs['primary'] = $menu_id;
set_theme_mod('nav_menu_locations', $locs);
echo "menu $menu_id created & assigned to 'primary'\n";
