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

/**
 * Verify that an image URL points to an existing file
 * 
 * @param string $url The image URL to verify
 * @return bool True if image exists and is accessible
 */
function verify_image_exists($url) {
    // Convert URL to local file path
    $upload_dir = wp_upload_dir();
    $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
    
    // Check if file exists and is an image
    return file_exists($file_path) && getimagesize($file_path) !== false;
}

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
        
        // Skip if no URL or image doesn't exist
        if (!$featured_image_url || !verify_image_exists($featured_image_url)) {
            continue;
        }
        
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
    
    // Start with 'always' images
    $images = $always_images;
    
    // Fill remaining slots with 'default' images
    $remaining_slots = 15 - count($always_images);
    if ($remaining_slots > 0 && !empty($default_images)) {
        shuffle($default_images);
        $images = array_merge($images, array_slice($default_images, 0, $remaining_slots));
    }
    
    // If no valid images found, return error
    if (empty($images)) {
        return new WP_Error('no_valid_images', 'No valid integration images found', array('status' => 404));
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