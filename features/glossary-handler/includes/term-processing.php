<?php
/**
 * Term processing and content filtering for glossary
 */

if (!defined('ABSPATH')) {
    exit;
}

// Process content to add dfn tags for glossary terms
function process_glossary_terms($content) {
    // At the start of process_glossary_terms(), add:
    error_log('=== SINGLE FAQ DEBUG ===');
    error_log('Current URL: ' . $_SERVER['REQUEST_URI']);
    error_log('Post ID: ' . get_the_ID());
    error_log('Post type: ' . get_post_type());
    error_log('Is singular: ' . (is_singular() ? 'yes' : 'no'));
    error_log('Is included: ' . (is_glossary_processing_included() ? 'yes' : 'no'));
    error_log('Is excluded: ' . (is_glossary_processing_excluded() ? 'yes' : 'no'));
    
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
    
    // Debug glossary terms
    error_log('Glossary terms count: ' . count($glossary_terms));
    if (!empty($glossary_terms)) {
        error_log('First few terms: ' . print_r(array_slice($glossary_terms, 0, 3), true));
    }
    
    if (empty($glossary_terms)) {
        return $content;
    }
    
    // Debug: show what content we're processing
    error_log('Content length: ' . strlen($content));
    error_log('Content preview: ' . substr($content, 0, 200));
    
    // Process each term
    foreach ($glossary_terms as $term_data) {
        $term = $term_data['term'];
        $definition = $term_data['definition'];
        
        // Skip if we've already processed this term on this page
        if (in_array($term, $processed_terms)) {
            continue;
        }
        
        // Debug: check if term exists in content
        if (stripos($content, $term) !== false) {
            error_log('Found term "' . $term . '" in content');
        }
        
        // Create pattern for whole word matching (case-insensitive)
        $pattern = '/\b' . preg_quote($term, '/') . '\b/i';
        
        // Replace first occurrence only to avoid over-tagging
        $replacement_made = preg_replace(
            $pattern,
            '<dfn title="' . esc_attr($definition) . '">$0</dfn>',
            $content,
            1, // limit to 1 replacement
            $count
        );
        
        // If replacement was made, mark term as processed and update content
        if ($count > 0) {
            $processed_terms[] = $term;
            $content = $replacement_made;
        }
    }
    
    return $content;
}
add_filter('the_content', 'process_glossary_terms', 20); 