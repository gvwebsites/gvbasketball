<?php
/**
 * Deploy image revamp: apply new WebP imagery across all pages.
 *  - Marketing pages: full HTML re-apply via gv_set_page_html (files scp'd to $HOME).
 *  - Functional pages: swap the old hero URL inside _elementor_data + post_content.
 * Per-target backups to ~/backups/. Run on server:  wp eval-file ~/deploy-images.php
 */
$HOME = getenv('HOME');
$ts   = date('Y-m-d-Hi');
$bdir = $HOME . '/backups';
if (!is_dir($bdir)) mkdir($bdir, 0755, true);

function bak($id, $bdir, $ts) {
    $p = get_post($id);
    if (!$p) { echo "  ! page $id not found\n"; return false; }
    file_put_contents("$bdir/$id-content-$ts.html", (string)$p->post_content);
    $ed = get_post_meta($id, '_elementor_data', true);
    if ($ed) file_put_contents("$bdir/$id-elementor_data-$ts.json", is_string($ed)?$ed:json_encode($ed));
    return true;
}

// recursive string replace over a decoded elementor data tree
function deep_replace($node, $from, $to) {
    if (is_array($node)) { foreach ($node as $k=>$v) $node[$k]=deep_replace($v,$from,$to); return $node; }
    if (is_string($node)) return str_replace($from, $to, $node);
    return $node;
}

$OLD = 'uploads/2025/07/GV-Basketball-Hero.jpeg';

$pages = array(
    2887 => 'home.html',
    26   => 'about.html',
    2981 => 'training-programs.html',
    2984 => 'athlete-development.html',
    2985 => 'success-stories.html',
    2988 => 'faq.html',
    2987 => 'gallery.html',
    2986 => 'testimonials.html',
);

echo "=== MARKETING PAGES ===\n";
foreach ($pages as $id => $file) {
    $path = "$HOME/$file";
    if (!file_exists($path)) { echo "  ! missing $path — skipped\n"; continue; }
    bak($id, $bdir, $ts);
    echo "  [$id] $file: " . gv_set_page_html($id, file_get_contents($path)) . "\n";
}

echo "=== FUNCTIONAL PAGES (hero swap) ===\n";
$func = array(
    2982 => 'uploads/2026/06/gv-about-hero.webp',    // Book a Consultation
    2983 => 'uploads/2026/06/gv-about-hero.webp',    // Member Portal
    2989 => 'uploads/2026/06/gv-contact-hero.webp',  // Contact
    3009 => 'uploads/2026/06/gv-about-hero.webp',    // Waiver
);
foreach ($func as $id => $new) {
    $p = get_post($id);
    if (!$p) { echo "  ! page $id not found\n"; continue; }
    bak($id, $bdir, $ts);
    // _elementor_data
    $raw = get_post_meta($id, '_elementor_data', true);
    $data = is_string($raw) ? json_decode($raw, true) : $raw;
    if (is_array($data)) {
        $data = deep_replace($data, $OLD, $new);
        update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode($data)));
    }
    // post_content (fallback render)
    $newc = str_replace($OLD, $new, (string)$p->post_content);
    if ($newc !== $p->post_content) wp_update_post(array('ID'=>$id,'post_content'=>wp_slash($newc)));
    $hit = (is_array($data) && strpos(wp_json_encode($data), $new) !== false) ? 'ok' : 'NO-MATCH';
    echo "  [$id] -> $new : $hit\n";
}

echo "=== DONE (flush separately) ===\n";
