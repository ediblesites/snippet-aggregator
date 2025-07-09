<?php
/**
 * Helper functions for Snippet Aggregator plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get a plugin setting value
 *
 * @param string $key The setting key
 * @param mixed $default Default value if setting doesn't exist
 * @return mixed The setting value or default
 */
function snippet_aggregator_get_setting($key, $default = null) {
    $settings = get_option('snippet_aggregator_settings', []);
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Update a plugin setting value
 *
 * @param string $key The setting key
 * @param mixed $value The new value
 * @return bool Whether the setting was updated successfully
 */
function snippet_aggregator_update_setting($key, $value) {
    $settings = get_option('snippet_aggregator_settings', []);
    $settings[$key] = $value;
    return update_option('snippet_aggregator_settings', $settings);
}

/**
 * Check if a feature is enabled
 *
 * @param string $feature_id Feature identifier
 * @return bool
 */
function snippet_aggregator_is_feature_enabled($feature_id) {
    return (bool) get_option("snippet_aggregator_feature_{$feature_id}", false);
} 