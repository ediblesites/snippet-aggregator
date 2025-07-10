<?php
/**
 * Manages event status updates and scheduling
 */

if (!defined('ABSPATH')) {
    exit;
}

// Schedule daily status update
add_action('wp', 'schedule_event_status_update');
function schedule_event_status_update() {
    if (!wp_next_scheduled('update_event_status_hook')) {
        wp_schedule_event(time(), 'daily', 'update_event_status_hook');
    }
}

// Hook the actual function
add_action('update_event_status_hook', 'update_all_event_statuses');

function update_all_event_statuses() {
    $today = date('Y-m-d');
    
    // Get all events
    $events = get_posts(array(
        'post_type' => 'event',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    foreach ($events as $event_id) {
        $start_date = get_post_meta($event_id, 'event_start_date', true);
        
        if (!$start_date) continue;
        
        // Remove existing status terms
        wp_remove_object_terms($event_id, array('future', 'past'), 'event_status');
        
        // Set new term based on date
        $new_term = ($start_date >= $today) ? 'future' : 'past';
        wp_set_object_terms($event_id, $new_term, 'event_status');
    }
} 