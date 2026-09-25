<?php
/**
 * Term processing and content filtering for glossary
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the list of HTML tags where term replacement should be skipped
 */
function get_ignored_tags() {
    return [
        'dfn',  // Already contains definitions
        'a',    // Preserve link text
        'code', // Code snippets
        'pre',  // Preformatted text
        'script', // JavaScript
        'style',  // CSS
        'textarea', // Form input
        'button',   // Button text
        'input'     // Form elements
    ];
}

// Process content to add dfn tags for glossary terms
function process_glossary_terms($content) {
    // Track which terms we've already processed on this page load
    static $processed_terms = [];
    
    // Early exit for admin area
    if (is_admin()) {
        return $content;
    }
    
    // Check if current URL/post should be excluded
    if (is_glossary_processing_excluded()) {
        return $content;
    }
    
    // Check if current URL should be included (overrides post type restrictions)
    if (is_glossary_processing_included()) {
        // Process glossary terms regardless of post type
    } else {
        // Get supported post types from settings
        $supported_post_types = get_option('glossary_post_types', ['post', 'page', 'use-case']);
        
        // Only process on configured post types
        if (!is_singular($supported_post_types)) {
            return $content;
        }
    }
    
    // Get glossary terms
    $glossary_terms = get_glossary_terms();
    
    if (empty($glossary_terms)) {
        return $content;
    }

    // Load content into DOM. The XML encoding hint keeps UTF-8 intact without
    // mb_convert_encoding('HTML-ENTITIES'), which is deprecated and, on the way
    // back, decoded &lt; &gt; &amp; too: escaped code samples became live HTML.
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8"><div>' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Get ignored tags for quick lookup
    $ignored_tags = array_map('strtolower', get_ignored_tags());

    // Process text nodes
    $wrapper = $dom->getElementsByTagName('div')->item(0);
    if (!$wrapper) {
        return $content;
    }
    $before = count($processed_terms);
    process_text_nodes($wrapper, $glossary_terms, $processed_terms, $ignored_tags);

    // No term found: return the content untouched rather than re-serialized.
    if (count($processed_terms) === $before) {
        return $content;
    }

    // Serialize the wrapper's children (saveHTML keeps entities escaped)
    $processed_content = '';
    foreach ($wrapper->childNodes as $child) {
        $processed_content .= $dom->saveHTML($child);
    }

    return $processed_content;
}

/**
 * Process text nodes recursively, skipping ignored tags
 */
function process_text_nodes($node, $glossary_terms, &$processed_terms, $ignored_tags) {
    if (!$node) {
        return;
    }

    // Skip if this is an ignored tag
    if ($node->nodeType === XML_ELEMENT_NODE && in_array(strtolower($node->nodeName), $ignored_tags)) {
        return;
    }

    // Process text node
    if ($node->nodeType === XML_TEXT_NODE) {
        $text = $node->nodeValue;
        $modified = false;

        foreach ($glossary_terms as $term_data) {
            $term = $term_data['term'];
            $definition = $term_data['definition'];

            // Skip if already processed
            if (in_array($term, $processed_terms)) {
                continue;
            }

            // Create pattern for whole word matching (case-insensitive)
            $pattern = '/\b' . preg_quote($term, '/') . '\b/i';

            // Check if term exists in this text node
            if (preg_match($pattern, $text)) {
                // Replace first occurrence only
                $text = preg_replace($pattern, "<dfn title=\"$definition\">$0</dfn>", $text, 1);
                $processed_terms[] = $term;
                $modified = true;
                break; // Process one term at a time
            }
        }

        // If text was modified, replace the text node with the new HTML
        if ($modified) {
            $fragment = $node->ownerDocument->createDocumentFragment();
            @$fragment->appendXML($text);
            $node->parentNode->replaceChild($fragment, $node);
        }
    }

    // Process child nodes
    if ($node->hasChildNodes()) {
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            process_text_nodes($child, $glossary_terms, $processed_terms, $ignored_tags);
        }
    }
}

add_filter('the_content', 'process_glossary_terms', 20); 