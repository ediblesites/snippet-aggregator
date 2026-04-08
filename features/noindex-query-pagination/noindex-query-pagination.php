<?php
/**
 * Adds noindex to pages with query pagination parameters (e.g. ?query-1-page=6)
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', function () {
    foreach (array_keys($_GET) as $key) {
        if (preg_match('/^query-\d+-page$/', $key)) {
            echo '<meta name="robots" content="noindex, follow">' . "\n";
            return;
        }
    }
});
