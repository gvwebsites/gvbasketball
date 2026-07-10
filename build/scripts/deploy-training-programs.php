<?php
/**
 * Deploy training-programs page (2981) as a single HTML block.
 * The old email-only consultation modal (shortcode widget and open-modal
 * triggers) is retired: every "Book a Consultation" CTA is a crawlable
 * /book-a-consultation/ link with data-gv-consultation, which gv-members.js
 * bridges into the native LatePoint wizard.
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
)) . "\n";
