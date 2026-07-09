<?php
/**
 * Deploy training-programs page (2981) as blocks layout:
 *  - Main page content (HTML widget with modal markup)
 *  - [gv_request_form] shortcode (separate widget, rendered at runtime with fresh nonces)
 *
 * Run: scp build/scripts/deploy-training-programs.php gvweb:~/
 * Then: ssh gvweb 'cd /home/u907133977/domains/gvbasketball.com/public_html && wp eval-file ~/deploy-training-programs.php && wp elementor flush-css && wp litespeed-purge all && rm ~/deploy-training-programs.php'
 */

$page_html = file_get_contents(getenv('HOME') . '/training-programs.html');
if (!$page_html) {
    echo "ERROR: training-programs.html not found in HOME\n";
    exit(1);
}

echo gv_set_page_blocks(2981, array(
    array('type' => 'html', 'content' => $page_html),
    array('type' => 'shortcode', 'content' => '[gv_request_form]', 'css' => 'gv-bookform-wrap'),
)) . "\n";
