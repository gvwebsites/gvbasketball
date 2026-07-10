<?php
/**
 * Targeted header/footer theme-part deploy for the members/consultation launch.
 * Requires header.html + footer.html scp'd to $HOME. Run via: wp eval-file
 * Backs up current _elementor_data JSON next to the sources before writing.
 */
$HOME = getenv('HOME');
$bdir = "$HOME/gv-backup-members-20260710";
if (!is_dir($bdir)) mkdir($bdir, 0755, true);
$ts = date('Ymd-His');

foreach (array('GV Header' => array('header', "$HOME/header.html"), 'GV Footer' => array('footer', "$HOME/footer.html")) as $title => $part) {
    list($type, $path) = $part;
    if (!file_exists($path)) { echo "  ! " . basename($path) . " missing\n"; continue; }
    $ids = get_posts(array('post_type' => 'elementor_library', 'title' => $title, 'numberposts' => 1, 'post_status' => 'any', 'fields' => 'ids'));
    if ($ids) {
        $ed = get_post_meta($ids[0], '_elementor_data', true);
        if ($ed) file_put_contents("$bdir/$type-{$ids[0]}-ed-$ts.json", is_string($ed) ? $ed : json_encode($ed));
    }
    echo "  " . gv_set_theme_part_blocks($title, $type, array(array('type' => 'html', 'content' => file_get_contents($path)))) . "\n";
    // Elementor caches rendered element HTML per post; without clearing it the
    // theme part keeps serving the previous markup even after _elementor_data
    // is updated (and after LiteSpeed/Cloudflare purges).
    $ids = get_posts(array('post_type' => 'elementor_library', 'title' => $title, 'numberposts' => 1, 'post_status' => 'any', 'fields' => 'ids'));
    if ($ids) delete_post_meta($ids[0], '_elementor_element_cache');
}
if (class_exists('\\Elementor\\Plugin')) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
}
