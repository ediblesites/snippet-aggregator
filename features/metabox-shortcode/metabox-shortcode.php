<?php
/**
 * Shortcode to display Meta Box fields with custom formatting
 */
if (!defined('ABSPATH')) {
    exit;
}

// General shortcode for any Meta Box field
// Usage examples:
// [metabox_field id="event_start_date" type="date"] - Formats using site's date format
// [metabox_field id="event_start_time" type="time"] - Formats using site's time format
// [metabox_field id="event_start_date" type="datetime"] - Shows both date and time
// [metabox_field id="event_url" type="url"] - Creates clickable link
// [metabox_field id="contact_email" type="email"] - Creates mailto link
// [metabox_field id="phone_number" type="phone"] - Creates tel link
// [metabox_field id="event_location" type="default"] - Uses Meta Box's built-in formatting
// [metabox_field id="any_field"] - Default behavior (Meta Box formatting)
function metabox_field_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => '',
        'type' => 'default',
        'format' => 'default'
    ), $atts);
    
    if (empty($atts['id'])) {
        return '';
    }
    
    // Check if Meta Box is active
    if (!function_exists('rwmb_get_value')) {
        return '';
    }
    
    // Ensure we're working with the current loop post
    global $post;
    if (!$post) {
        return '';
    }
    
    // Force setup post data to ensure Meta Box sees the current loop context
    $original_post = $post;
    setup_postdata($post);
    
    // Get the raw value - let Meta Box handle the context
    $value = rwmb_get_value($atts['id']);
    
    // Restore original post data
    wp_reset_postdata();
    
    if (empty($value)) {
        return '';
    }
    
    // Apply custom formatting based on type
    switch ($atts['type']) {
        case 'date':
            if (is_string($value)) {
                return date_i18n(get_option('date_format'), strtotime($value));
            }
            break;
            
        case 'time':
            if (is_string($value)) {
                return date_i18n(get_option('time_format'), strtotime($value));
            }
            break;
            
        case 'datetime':
            if (is_string($value)) {
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($value));
            }
            break;
            
        case 'url':
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return '<a href="' . esc_url($value) . '">' . esc_html($value) . '</a>';
            }
            break;
            
        case 'email':
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
            }
            break;
            
        case 'phone':
            return '<a href="tel:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
            
        case 'default':
        default:
            // Use Meta Box's built-in formatting
            ob_start();
            rwmb_the_value($atts['id']);
            return ob_get_clean();
    }
    
    // Fallback to raw value if custom formatting fails
    return esc_html($value);
}
add_shortcode('metabox_field', 'metabox_field_shortcode');