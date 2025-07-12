<?php
/**
 * Populates announcement bar with upcoming events and manages event statuses
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include status manager functionality
require_once __DIR__ . '/includes/status-manager.php';

// Register shortcodes
add_shortcode('event_announcement', 'render_event_announcement');
add_shortcode('event_slug', 'render_event_slug');

/**
 * Returns event URL path without protocol for FSE button compatibility
 * FSE button setup requires URLs without the protocol prefix
 */
function render_event_slug() {
    $upcoming_event = get_next_upcoming_event();
    
    if (!$upcoming_event) {
        return '';
    }
    
    $event_link = get_permalink($upcoming_event->ID);
    // Strip protocol to work with FSE button limitations
    return preg_replace('(^https?://)', '', $event_link);
}

function render_event_announcement() {
    // Get the next upcoming event
    $upcoming_event = get_next_upcoming_event();
    
    if (!$upcoming_event) {
        // Hide announcement bar if no upcoming events
        add_action('wp_head', function() {
            echo '<style>.announcement-bar { display: none !important; }</style>';
        });
        return '';
    }
    
    // Get event meta data
    $start_date = get_post_meta($upcoming_event->ID, 'event_start_date', true);
    $location = get_post_meta($upcoming_event->ID, 'event_location', true);
    $event_url = get_post_meta($upcoming_event->ID, 'event_url', true);
    
    // Format the date using site settings
    $formatted_date = date_i18n(get_option('date_format'), strtotime($start_date));
    
    // Create announcement message with rotating prefix
    $prefixes = array(
        'Join us for',
        'Don\'t miss',
        'See you at',
        'Coming up:',
        'We\'ll be at',
        'Catch us at',
        'Meet us at',
        'Save the date:'
    );
    
    $random_prefix = $prefixes[array_rand($prefixes)];
    $announcement = "{$random_prefix} <strong>{$upcoming_event->post_title}</strong>";
    if ($location) {
        $announcement .= " in {$location}";
    }
    $announcement .= " on {$formatted_date}";
    
    return $announcement;
}

function get_next_upcoming_event() {
    $today = date('Y-m-d');
    
    $args = array(
        'post_type' => 'event',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'event_start_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE'
            )
        ),
        'meta_key' => 'event_start_date',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    );
    
    $events = get_posts($args);
    
    return !empty($events) ? $events[0] : null;
} 