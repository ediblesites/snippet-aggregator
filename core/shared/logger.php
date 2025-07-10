<?php
/**
 * Logging functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log a message to the WordPress error log if debug mode is enabled
 * 
 * @param string $feature The feature or component generating the log
 * @param string $message The message to log
 * @param string $level The log level (info, warning, error)
 */
function snippet_aggregator_log($feature, $message, $level = 'info') {
    // Only log if plugin debug mode is enabled
    if (!get_option('snippet_aggregator_debug_mode', false)) {
        return;
    }
    
    $timestamp = current_time('mysql');
    $log_entry = sprintf(
        '[%s] [%s] %s: %s',
        $timestamp,
        strtoupper($level),
        $feature,
        $message
    );
    
    error_log($log_entry);
} 