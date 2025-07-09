<?php
/**
 * Add excerpt support to pages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add excerpt support to pages
add_action('init', 'snippet_aggregator_add_page_excerpt_support');

function snippet_aggregator_add_page_excerpt_support() {
    add_post_type_support('page', 'excerpt');
} 