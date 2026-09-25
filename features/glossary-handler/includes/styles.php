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

        /* Tooltip: shows on hover, keyboard focus and tap (the term is focusable).
           Colors, radius and type use theme presets when the theme has them. */
        dfn.glossary-term {
            position: relative;
            text-underline-offset: 3px;
            outline: none;
        }
        dfn.glossary-term::after {
            content: attr(data-definition);
            display: none;
            position: absolute;
            z-index: 20;
            left: 50%;
            bottom: calc(100% + 0.5rem);
            transform: translateX(-50%);
            width: max-content;
            max-width: min(20rem, 70vw);
            padding: 0.625rem 0.75rem;
            border-radius: var(--wp--preset--border-radius--sm, 6px);
            background: var(--wp--preset--color--main, #1a1a1a);
            color: var(--wp--preset--color--base, #fff);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            font-size: var(--wp--preset--font-size--x-small, 0.8125rem);
            font-weight: 400;
            font-style: normal;
            line-height: 1.45;
            letter-spacing: 0;
            text-align: left;
            text-transform: none;
            white-space: normal;
            pointer-events: none;
        }
        dfn.glossary-term:hover::after,
        dfn.glossary-term:focus::after {
            display: block;
        }
        dfn.glossary-term:focus-visible {
            border-radius: 2px;
            box-shadow: 0 0 0 2px var(--wp--preset--color--primary, currentColor);
        }
        /* Phones: a tooltip above a word near the edge would leave the screen,
           so the definition shows as a bar at the bottom of the screen. */
        @media (max-width: 781px) {
            dfn.glossary-term::after {
                position: fixed;
                left: 1rem;
                right: 1rem;
                bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
                transform: none;
                width: auto;
                max-width: none;
                font-size: var(--wp--preset--font-size--small, 0.9375rem);
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'glossary_dfn_styles'); 