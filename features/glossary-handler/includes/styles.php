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
            text-decoration: none;
            border-bottom: 1px dashed var(--wp--preset--color--primary-accent);
            cursor: help;
            position: relative;
            display: inline-block;
        }

        /* Create pseudo-element for tooltip */
        dfn::after {
            content: attr(title);
            position: absolute;
            left: 50%;
            bottom: 100%;
            transform: translateX(-50%);
            padding: 8px 12px;
            background: var(--wp--preset--color--primary-accent);
            color: black;
            font-size: 14px;
            line-height: 1.4;
            white-space: normal;
            min-width: 150px;
            max-width: 280px;
            border-radius: 6px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease-in-out;
            z-index: 1000;
            
            /* Prevent tooltip from being too wide on mobile */
            @media (max-width: 480px) {
                min-width: 120px;
                max-width: 220px;
                font-size: 13px;
            }
        }

        /* Create arrow */
        dfn::before {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 100%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-top-color: var(--wp--preset--color--primary-accent);
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease-in-out;
            z-index: 1000;
        }

        /* Show tooltip on hover */
        dfn:hover::after,
        dfn:hover::before {
            opacity: 1;
            visibility: visible;
        }

        /* Position tooltip and arrow */
        dfn::after {
            bottom: calc(100% + 10px);
            margin-bottom: 0;
        }

        dfn::before {
            bottom: calc(100% + 4px);
        }

        /* Add animation */
        dfn:hover::after {
            transform: translateX(-50%) translateY(-2px);
        }

        /* Handle tooltip positioning at screen edges */
        @media (max-width: 480px) {
            dfn::after {
                left: 0;
                transform: translateX(0);
            }
            
            dfn::before {
                left: 15px;
                transform: translateX(0);
            }
            
            dfn:hover::after {
                transform: translateY(-2px);
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'glossary_dfn_styles'); 