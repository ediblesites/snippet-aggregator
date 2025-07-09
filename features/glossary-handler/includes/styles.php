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
    error_log('CSS function running on: ' . $_SERVER['REQUEST_URI']);
    
    // Skip if processing is excluded
    if (is_glossary_processing_excluded()) {
        error_log('CSS skipped - excluded');
        return;
    }
    
    // Check if current URL should be included (overrides post type restrictions)
    if (is_glossary_processing_included()) {
        // Add styles regardless of post type
        error_log('CSS added - included URL');
    } else {
        // Get supported post types from settings
        $supported_post_types = get_option('glossary_post_types', ['post', 'page', 'use-case']);
        
        if (!is_singular($supported_post_types)) {
            error_log('CSS skipped - not singular');
            return;
        }
        error_log('CSS added - singular post type');
    }
    ?>
    <style>
        dfn {
            font-style: normal;
            text-decoration: underline;
            text-decoration-style: dashed;
            cursor: help;
        }
    </style>
    <?php
}
add_action('wp_head', 'glossary_dfn_styles'); 