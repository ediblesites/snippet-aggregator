<?php
/**
 * Cache management for glossary terms
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get glossary terms with caching
function get_glossary_terms() {
    // Check for cached version
    $cache_key = 'glossary_terms_data';
    $cached_data = get_transient($cache_key);
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    // Query glossary items
    $glossary_posts = get_posts([
        'post_type' => 'glossary-item',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    $glossary_data = [];
    
    foreach ($glossary_posts as $post) {
        $glossary_data[] = [
            'term' => $post->post_title,
            'definition' => wp_strip_all_tags($post->post_content)
        ];
    }
    
    // Cache for 1 hour
    set_transient($cache_key, $glossary_data, HOUR_IN_SECONDS);
    
    return $glossary_data;
}

// Clear cache when glossary items are updated
function clear_glossary_terms_cache($post_id) {
    if (get_post_type($post_id) === 'glossary-item') {
        delete_transient('glossary_terms_data');
    }
}
add_action('save_post', 'clear_glossary_terms_cache');
add_action('delete_post', 'clear_glossary_terms_cache');
add_action('wp_trash_post', 'clear_glossary_terms_cache');
add_action('untrash_post', 'clear_glossary_terms_cache'); 