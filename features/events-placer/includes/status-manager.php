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
    $taxonomy = 'event-status';
    
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
                'taxonomy' => 'event-status',
                'field' => 'slug',
                'terms' => 'past',
                'operator' => 'NOT IN'
            )
        )
    ));
    
    $changed = 0;

    foreach ($events as $event_id) {
        $start_date = get_post_meta($event_id, 'event_start_date', true);
        $end_date = get_post_meta($event_id, 'event_end_date', true);
        
        if (!$start_date || !$end_date) continue;
        
        // Determine new status
        $new_term = 'future'; // default to future
        
        if ($end_date < $today) {
            $new_term = 'past';
        } elseif ($start_date <= $today && $end_date >= $today) {
            $new_term = 'ongoing';
        }
        
        // Only touch events whose status actually changes
        $current = wp_get_object_terms($event_id, 'event-status', array('fields' => 'slugs'));
        if (!is_wp_error($current) && $current === array($new_term)) continue;
        
        // Remove existing status terms, then set the new one
        wp_remove_object_terms($event_id, array('future', 'ongoing', 'past'), 'event-status');
        wp_set_object_terms($event_id, $new_term, 'event-status');
        $changed++;
    }

    if ($changed > 0) {
        purge_page_cache_after_status_change();
    }
}

/**
 * Term changes do not fire the post hooks (save_post etc.) that page caches listen to,
 * and a status change also affects archives, status term pages and the announcement bar
 * on every page. So clear the whole page cache, at most once a day.
 */
function purge_page_cache_after_status_change() {
    // WP Engine: page (Varnish) cache for the whole site
    if (class_exists('WpeCommon') && method_exists('WpeCommon', 'purge_varnish_cache')) {
        WpeCommon::purge_varnish_cache();
    }

    // Other hosts and cache plugins can hook in here
    do_action('snippet_aggregator_event_statuses_changed');
}
