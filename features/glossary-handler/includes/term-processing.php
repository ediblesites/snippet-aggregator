<?php
/**
 * Term processing and content filtering for glossary
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define tags where we skip term replacement (both inside tag attributes and wrapped content)
$IGNORED_TAGS = [
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

// Process content to add dfn tags for glossary terms
function process_glossary_terms($content) {
    global $IGNORED_TAGS;
    
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

    // Split content into processable chunks, preserving ignored tags
    $chunks = split_content_preserve_tags($content, $IGNORED_TAGS);
    
    // Process each term
    foreach ($glossary_terms as $term_data) {
        $term = $term_data['term'];
        $definition = $term_data['definition'];
        
        // Skip if we've already processed this term on this page
        if (in_array($term, $processed_terms)) {
            continue;
        }
        
        // Process only non-tag chunks
        foreach ($chunks as &$chunk) {
            if ($chunk['type'] === 'text') {
                // Create pattern for whole word matching (case-insensitive)
                $pattern = '/\b' . preg_quote($term, '/') . '\b/i';
                
                // Replace first occurrence only in this chunk
                $replacement_made = preg_replace(
                    $pattern,
                    '<dfn title="' . esc_attr($definition) . '">$0</dfn>',
                    $chunk['content'],
                    1, // limit to 1 replacement
                    $count
                );
                
                // If replacement was made, mark term as processed and update chunk
                if ($count > 0) {
                    $processed_terms[] = $term;
                    $chunk['content'] = $replacement_made;
                    break; // Stop after first replacement across all chunks
                }
            }
        }
    }
    
    // Reconstruct content from chunks
    $processed_content = '';
    foreach ($chunks as $chunk) {
        $processed_content .= $chunk['content'];
    }
    
    return $processed_content;
}

/**
 * Split content into chunks of text and ignored tags
 * 
 * @param string $content The content to split
 * @param array $ignored_tags Array of tag names to ignore
 * @return array Array of chunks with 'type' (text|tag) and 'content'
 */
function split_content_preserve_tags($content, $ignored_tags) {
    $chunks = [];
    $current_pos = 0;
    $content_length = strlen($content);
    
    // Create pattern for matching any ignored tag
    $tags_pattern = implode('|', array_map('preg_quote', $ignored_tags));
    $tag_regex = "/<(?:$tags_pattern)(?:\s[^>]*)?>/i";
    
    while ($current_pos < $content_length) {
        // Find next ignored tag
        if (preg_match($tag_regex, $content, $tag_match, PREG_OFFSET_CAPTURE, $current_pos)) {
            $tag_start = $tag_match[0][1];
            $tag_name = strtolower(trim($tag_match[0][0], "<> "));
            $tag_name = preg_replace('/\s.*$/', '', $tag_name); // Remove attributes
            
            // Add text chunk before tag if exists
            if ($tag_start > $current_pos) {
                $chunks[] = [
                    'type' => 'text',
                    'content' => substr($content, $current_pos, $tag_start - $current_pos)
                ];
            }
            
            // Find closing tag
            $closing_tag = "</$tag_name>";
            $tag_end = stripos($content, $closing_tag, $tag_start);
            if ($tag_end === false) {
                // No closing tag found, treat rest as text
                $chunks[] = [
                    'type' => 'text',
                    'content' => substr($content, $current_pos)
                ];
                break;
            }
            
            // Add tag chunk (including content between tags)
            $tag_length = $tag_end + strlen($closing_tag) - $tag_start;
            $chunks[] = [
                'type' => 'tag',
                'content' => substr($content, $tag_start, $tag_length)
            ];
            
            $current_pos = $tag_start + $tag_length;
        } else {
            // No more tags, add remaining content as text
            $chunks[] = [
                'type' => 'text',
                'content' => substr($content, $current_pos)
            ];
            break;
        }
    }
    
    return $chunks;
}

add_filter('the_content', 'process_glossary_terms', 20); 