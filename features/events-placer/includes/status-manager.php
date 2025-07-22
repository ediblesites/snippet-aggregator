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

// Ensure status terms exist
function ensure_event_status_terms() {
    $taxonomy = 'event_status';
    
    // Create terms if they don't exist
    $required_terms = array(
        'future' => 'Future Events',
        'ongoing' => 'Ongoing Events',
        'past' => 'Past Events'
    );
    
    foreach ($required_terms as $slug => $name) {
        if (!term_exists($slug, $taxonomy)) {
            wp_insert_term($name, $taxonomy, array('slug' => $slug));
        }
    }
}

function update_all_event_statuses() {
    // Ensure terms exist before proceeding
    ensure_event_status_terms();
    
    $today = date('Y-m-d');
    
    // Get only future and ongoing events, no need to recheck past events
    $events = get_posts(array(
        'post_type' => 'event',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => array(
            array(
                'taxonomy' => 'event_status',
                'field' => 'slug',
                'terms' => 'past',
                'operator' => 'NOT IN'
            )
        )
    ));
    
    foreach ($events as $event_id) {
        $start_date = get_post_meta($event_id, 'event_start_date', true);
        $end_date = get_post_meta($event_id, 'event_end_date', true);
        
        if (!$start_date || !$end_date) continue;
        
        // Remove existing status terms
        wp_remove_object_terms($event_id, array('future', 'ongoing', 'past'), 'event_status');
        
        // Determine new status
        $new_term = 'future'; // default to future
        
        if ($end_date < $today) {
            $new_term = 'past';
        } elseif ($start_date <= $today && $end_date >= $today) {
            $new_term = 'ongoing';
        }
        
        wp_set_object_terms($event_id, $new_term, 'event_status');
    }
} 