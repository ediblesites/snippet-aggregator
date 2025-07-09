<?php
/**
 * URL inclusion/exclusion handling for glossary processing
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get exclusion list with filter for customization
function get_glossary_exclusion_list() {
    // Get exclusions from admin settings
    $admin_exclusions = get_option('glossary_exclusions', '');
    $exclusions_array = [];
    
    if (!empty($admin_exclusions)) {
        // Split by newlines and clean up
        $exclusions_array = array_map('trim', explode("\n", $admin_exclusions));
        $exclusions_array = array_filter($exclusions_array); // Remove empty lines
    }
    
    // Allow themes/plugins to modify the list
    return apply_filters('glossary_processing_exclusions', $exclusions_array);
}

// Get inclusion list with filter for customization
function get_glossary_inclusion_list() {
    // Get inclusions from admin settings
    $admin_inclusions = get_option('glossary_inclusions', '');
    $inclusions_array = [];
    
    if (!empty($admin_inclusions)) {
        // Split by newlines and clean up
        $inclusions_array = array_map('trim', explode("\n", $admin_inclusions));
        $inclusions_array = array_filter($inclusions_array); // Remove empty lines
    }
    
    // Allow themes/plugins to modify the list
    return apply_filters('glossary_processing_inclusions', $inclusions_array);
}

// Check if glossary processing should be excluded for current request
function is_glossary_processing_excluded() {
    // Get exclusion list
    $excluded_urls = get_glossary_exclusion_list();
    
    if (empty($excluded_urls)) {
        return false;
    }
    
    // Get current URL path
    $current_url = $_SERVER['REQUEST_URI'];
    $current_path = parse_url($current_url, PHP_URL_PATH);
    
    // Check each exclusion pattern
    foreach ($excluded_urls as $pattern) {
        // Support exact matches and wildcard patterns
        if ($pattern === $current_path || 
            (strpos($pattern, '*') !== false && fnmatch($pattern, $current_path))) {
            return true;
        }
    }
    
    return false;
}

// Check if glossary processing should be included for current request
function is_glossary_processing_included() {
    // Get inclusion list
    $included_urls = get_glossary_inclusion_list();
    
    if (empty($included_urls)) {
        return false;
    }
    
    // Get current URL path
    $current_url = $_SERVER['REQUEST_URI'];
    $current_path = parse_url($current_url, PHP_URL_PATH);
    
    // Check each inclusion pattern
    foreach ($included_urls as $pattern) {
        // Support exact matches and wildcard patterns
        if ($pattern === $current_path || 
            (strpos($pattern, '*') !== false && fnmatch($pattern, $current_path))) {
            return true;
        }
    }
    
    return false;
} 