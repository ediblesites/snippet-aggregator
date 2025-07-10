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

    // Load content into DOM
    $dom = new DOMDocument();
    
    // Preserve UTF-8 encoding
    $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
    
    // Wrap in temporary div to handle content fragments
    $content = '<div>' . $content . '</div>';
    
    // Suppress warnings from malformed HTML
    @$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
    // Get ignored tags for quick lookup
    $ignored_tags = array_map('strtolower', get_ignored_tags());
    
    // Process text nodes
    process_text_nodes($dom->getElementsByTagName('div')->item(0), $glossary_terms, $processed_terms, $ignored_tags);
    
    // Get processed content (remove wrapper div)
    $processed_content = preg_replace('/^<div>|<\/div>$/', '', $dom->saveHTML());
    
    // Fix UTF-8 encoding
    $processed_content = mb_convert_encoding($processed_content, 'UTF-8', 'HTML-ENTITIES');
    
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