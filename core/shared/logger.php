<?php
/**
 * Logging utility functions for Snippet Aggregator plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger class for handling debug and error logging
 */
class Snippet_Aggregator_Logger {
    /**
     * Log a message if debug mode is enabled
     *
     * @param string $feature_id The ID of the feature generating the log
     * @param string $message The message to log
     * @param string $level The log level (debug, info, warning, error)
     */
    public static function log($feature_id, $message, $level = 'info') {
        // Only log if WP_DEBUG is enabled or debug mode is enabled in settings
        if (!WP_DEBUG && !snippet_aggregator_get_setting('debug_mode', false)) {
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

    /**
     * Log a debug message
     */
    public static function debug($feature_id, $message) {
        self::log($feature_id, $message, 'debug');
    }

    /**
     * Log an info message
     */
    public static function info($feature_id, $message) {
        self::log($feature_id, $message, 'info');
    }

    /**
     * Log a warning message
     */
    public static function warning($feature_id, $message) {
        self::log($feature_id, $message, 'warning');
    }

    /**
     * Log an error message
     */
    public static function error($feature_id, $message) {
        self::log($feature_id, $message, 'error');
    }
} 