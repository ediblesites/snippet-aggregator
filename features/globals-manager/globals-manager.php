<?php
/**
 * Provides an admin interface for managing global content snippets
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'globals_manager_settings_init');
add_action('wp_ajax_globals_manager_save', 'globals_manager_save_ajax');
add_action('wp_ajax_globals_manager_delete', 'globals_manager_delete_ajax');

function globals_manager_settings_init() {
    register_setting('globals_manager_settings', 'globals_manager_pairs');
}

function globals_manager_save_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'globals_manager_nonce')) {
        wp_die('Invalid nonce');
    }

    if (!current_user_can('edit_pages')) {
        wp_die('Insufficient permissions');
    }

    $name = sanitize_text_field($_POST['name']);
    $value = $_POST['value'];
    $operation = sanitize_text_field($_POST['operation']);
    $original_name = sanitize_text_field($_POST['original_name']);

    if (empty($name) || empty($value)) {
        wp_send_json_error('Name and value are required');
    }

    $pairs = get_option('globals_manager_pairs', array());

    if ($operation === 'update' && $original_name !== $name) {
        unset($pairs[$original_name]);
    }

    $pairs[$name] = $value;
    update_option('globals_manager_pairs', $pairs);

    wp_send_json_success('Global saved successfully');
}

function globals_manager_delete_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'globals_manager_nonce')) {
        wp_die('Invalid nonce');
    }

    if (!current_user_can('edit_pages')) {
        wp_die('Insufficient permissions');
    }

    $name = sanitize_text_field($_POST['name']);
    $pairs = get_option('globals_manager_pairs', array());

    if (isset($pairs[$name])) {
        unset($pairs[$name]);
        update_option('globals_manager_pairs', $pairs);
    }

    wp_send_json_success('Global deleted successfully');
}

// Register shortcodes
add_action('init', 'globals_manager_register_shortcodes');

function globals_manager_register_shortcodes() {
    $pairs = get_option('globals_manager_pairs', array());
    foreach ($pairs as $name => $content) {
        add_shortcode($name, function() use ($content) {
            return $content;
        });
    }
} 