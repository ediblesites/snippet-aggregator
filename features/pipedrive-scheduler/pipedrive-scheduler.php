<?php
/**
 * Provides shortcode for embedding Pipedrive scheduler iframe using post meta data
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shortcode [pipedrive_scheduler] for embedding Pipedrive scheduler iframe
function pipedrive_scheduler_shortcode($atts) {
    $post = get_queried_object();
    
    if (!$post) {
        return '';
    }
    
    $pipedrive_id = get_post_meta($post->ID, 'pipedrive_id', true);
    $pipedrive_title = get_post_meta($post->ID, 'pipedrive_title', true);
    
    if (!$pipedrive_id || !$pipedrive_title) {
        return '';
    }
    
    return '<iframe src="https://pcibooking.pipedrive.com/scheduler/' . esc_attr($pipedrive_id) . '/' . esc_attr($pipedrive_title) . '" title="Pipedrive Scheduler Embed" frameborder="0" height="600px" width="100%" style="max-width: 800px" allowfullscreen></iframe>';
}
add_shortcode('pipedrive_scheduler', 'pipedrive_scheduler_shortcode'); 