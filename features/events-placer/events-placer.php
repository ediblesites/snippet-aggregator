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
        'We\'ll be at',
        'Find us at',
        'Visit us at',
        // 'Stop by our booth at',
        'Come see us at',
        'Meet our team at',
        'Join us at',
        'Catch us at',
        'See you at',
        'Don\'t miss us at',
        // 'Visit our booth at',
        'Connect with us at',
        'Meet us at',
        // 'We\'re exhibiting at',
        // 'We\'re presenting at',
        // 'We\'re showcasing at',
        // 'Drop by our stand at',
        'Come find us at',
        'See our latest at',
        // 'We\'re participating in',
        'We\'re attending',
        'Look for us at',
        'Schedule a meeting at',
        'Book a demo at',
        'Get hands-on at',
        'Learn more at',
        'Discover our solutions at',
        'Experience our products at',
        'See what\'s new at',
        'We\'re bringing innovation to',
        'Live demos at',
        'Network with us at',
        'Connect and collaborate at'
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
                'key' => 'event_end_date',  // Check against end date
                'value' => $today,
                'compare' => '>=',  // Event hasn't ended yet
                'type' => 'DATE'
            )
        ),
        'meta_key' => 'event_start_date',  // Still order by start date
        'orderby' => 'meta_value',
        'order' => 'ASC'
    );
    
    $events = get_posts($args);
    
    return !empty($events) ? $events[0] : null;
} 