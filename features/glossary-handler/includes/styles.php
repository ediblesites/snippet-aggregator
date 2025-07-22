<?php
/**
 * Frontend styles for glossary terms
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add CSS for dfn styling
function glossary_dfn_styles() {
    // Early exit for admin area
    if (is_admin()) {
        return;
    }
    
    // Debug CSS function
    snippet_aggregator_log('glossary-handler', 'CSS function running on: ' . $_SERVER['REQUEST_URI'], 'debug');
    
    // Skip if processing is excluded
    if (is_glossary_processing_excluded()) {
        snippet_aggregator_log('glossary-handler', 'CSS skipped - excluded', 'debug');
        return;
    }
    
    // Check if current URL should be included (overrides post type restrictions)
    if (is_glossary_processing_included()) {
        // Add styles regardless of post type
        snippet_aggregator_log('glossary-handler', 'CSS added - included URL', 'debug');
    } else {
        // Get supported post types from settings
        $supported_post_types = get_option('glossary_post_types', ['post', 'page', 'use-case']);
        
        if (!is_singular($supported_post_types)) {
            snippet_aggregator_log('glossary-handler', 'CSS skipped - not singular', 'debug');
            return;
        }
        snippet_aggregator_log('glossary-handler', 'CSS added - singular post type', 'debug');
    }
    ?>
    <style>
        dfn {
            font-style: normal;
            text-decoration-line: underline;
            text-decoration-style: dashed;
            text-decoration-color: var(--wp--preset--color--primary);
            cursor: help;
        }
    </style>
    <?php
}
add_action('wp_head', 'glossary_dfn_styles'); 