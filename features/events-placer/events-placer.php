<?php
/**
 * Populates announcement bar with upcoming events and manages event statuses
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include status manager functionality
require_once __DIR__ . '/includes/status-manager.php';

// Populate announcement bar with upcoming event or hide if none exist
function handle_event_announcement_bar() {
    // Get the next upcoming event
    $upcoming_event = get_next_upcoming_event();
    
    if (!$upcoming_event) {
        // Hide announcement bar if no upcoming events
        add_action('wp_head', function() {
            echo '<style>.announcement-bar { display: none !important; }</style>';
        });
        return;
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
    
    // Get event permalink
    $event_link = get_permalink($upcoming_event->ID);
    
    // Replace placeholders in theme areas
    add_action('wp_head', function() use ($announcement, $event_link) {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var announcementBar = document.querySelector(".announcement-bar");
                if (announcementBar) {
                    announcementBar.innerHTML = announcementBar.innerHTML
                        .replace("{{EVENT_ANNOUNCEMENT}}", "' . addslashes($announcement) . '")
                        .replace("http://{{EVENT_SLUG}}", "' . esc_url($event_link) . '")
                        .replace("{{EVENT_SLUG}}", "' . esc_url($event_link) . '");
                }
            });
        </script>';
    });
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

// Hook into WordPress initialization
add_action('init', 'handle_event_announcement_bar'); 