<?php
/**
 * Logging utility functions for Snippet Aggregator plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

function snippet_aggregator_log($feature_id, $message, $level = 'info') {
    if (!WP_DEBUG && !get_option('snippet_aggregator_debug_mode', false)) {
        return;
    }
    
    $timestamp = current_time('mysql');
    $log_entry = sprintf(
        "[%s] [%s] %s: %s",
        $timestamp,
        strtoupper($level),
        $feature_id,
        $message
    );
    
    error_log($log_entry);
} 