<?php
/**
 * REST API endpoints for the puzzle feature
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register custom REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('puzzle/v1', '/images', array(
        'methods' => 'GET',
        'callback' => 'get_puzzle_images',
        'permission_callback' => '__return_true', // Public endpoint
    ));
});

function get_puzzle_images($request) {
    // Get images from integrations custom post type
    $args = array(
        'post_type' => 'integration',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS'
            )
        )
    );
    
    $integrations = get_posts($args);
    
    if (empty($integrations)) {
        return new WP_Error('no_images', 'No integration images found', array('status' => 404));
    }
    
    $always_images = array();
    $default_images = array();
    
    foreach ($integrations as $integration) {
        $featured_image_url = get_the_post_thumbnail_url($integration->ID, 'full');
        if ($featured_image_url) {
            $puzzle_setting = rwmb_meta('radio_puzzle', '', $integration->ID);
            
            // Skip posts marked as 'never'
            if ($puzzle_setting === 'never') {
                continue;
            }
            
            // Always add radio_puzzle field to the image data
            $image_data = array(
                'url' => $featured_image_url
            );
            
            if ($puzzle_setting === 'always') {
                $always_images[] = $image_data;
            } else {
                // Empty/null fields and 'default' both go to default pool
                $default_images[] = $image_data;
            }
        }
    }
    
    // Start with 'always' images
    $images = $always_images;
    
    // Fill remaining slots with 'default' images
    $remaining_slots = 15 - count($always_images);
    if ($remaining_slots > 0 && !empty($default_images)) {
        shuffle($default_images);
        $images = array_merge($images, array_slice($default_images, 0, $remaining_slots));
    }
    
    // Extract just the URLs for the final response (maintaining backward compatibility)
    $image_urls = array();
    foreach ($images as $image_data) {
        $image_urls[] = $image_data['url'];
    }
    
    return rest_ensure_response(array(
        'success' => true,
        'images' => $image_urls
    ));
} 