<?php
/**
 * GitHub updater integration
 */

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if (!defined('ABSPATH')) {
    exit;
}

// Initialize GitHub updater
add_action('init', 'snippet_aggregator_init_updater');

function snippet_aggregator_init_updater() {
    // Get GitHub settings
    $github_repo = get_option('snippet_aggregator_github_repo');
    $github_token = get_option('snippet_aggregator_github_token');
    
    if (empty($github_repo) || empty($github_token)) {
        return;
    }
    
    // Include updater library
    require_once SNIPPET_AGGREGATOR_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';
    
    // Initialize updater
    $myUpdateChecker = PucFactory::buildUpdateChecker(
        "https://github.com/{$github_repo}/",
        SNIPPET_AGGREGATOR_FILE,
        'snippet-aggregator'
    );
    
    // Set authentication
    $myUpdateChecker->setAuthentication($github_token);
    
    // Set branch
    $myUpdateChecker->setBranch(get_option('snippet_aggregator_github_branch', 'main'));
    
    // Store updater instance for webhook access
    global $snippet_aggregator_updater;
    $snippet_aggregator_updater = $myUpdateChecker;
}

// Register settings
add_action('admin_init', 'snippet_aggregator_register_updater_settings');

function snippet_aggregator_register_updater_settings() {
    // GitHub repository
    register_setting(
        'snippet_aggregator_settings',
        'snippet_aggregator_github_repo',
        [
            'type' => 'string',
            'description' => 'GitHub repository in owner/repo format',
            'sanitize_callback' => 'snippet_aggregator_sanitize_github_repo',
        ]
    );
    
    // GitHub token
    register_setting(
        'snippet_aggregator_settings',
        'snippet_aggregator_github_token',
        [
            'type' => 'string',
            'description' => 'GitHub personal access token',
            'sanitize_callback' => 'snippet_aggregator_sanitize_github_token',
        ]
    );
    
    // GitHub branch
    register_setting(
        'snippet_aggregator_settings',
        'snippet_aggregator_github_branch',
        [
            'type' => 'string',
            'description' => 'GitHub branch to track',
            'sanitize_callback' => 'snippet_aggregator_sanitize_github_branch',
            'default' => 'main',
        ]
    );
    
    // Webhook secret
    register_setting(
        'snippet_aggregator_settings',
        'snippet_aggregator_webhook_secret',
        [
            'type' => 'string',
            'description' => 'GitHub webhook secret',
            'sanitize_callback' => 'snippet_aggregator_sanitize_webhook_secret',
        ]
    );
}

// Sanitization callbacks
function snippet_aggregator_sanitize_github_repo($value) {
    $value = sanitize_text_field($value);
    if (!preg_match('/^[a-zA-Z0-9-]+\/[a-zA-Z0-9-]+$/', $value)) {
        add_settings_error(
            'snippet_aggregator_messages',
            'invalid_github_repo',
            __('Invalid GitHub repository format. Use owner/repo format.', 'snippet-aggregator')
        );
        $value = get_option('snippet_aggregator_github_repo');
    }
    return $value;
}

function snippet_aggregator_sanitize_github_token($value) {
    $value = sanitize_text_field($value);
    if (empty($value)) {
        add_settings_error(
            'snippet_aggregator_messages',
            'invalid_github_token',
            __('GitHub token cannot be empty.', 'snippet-aggregator')
        );
        $value = get_option('snippet_aggregator_github_token');
    }
    return $value;
}

function snippet_aggregator_sanitize_github_branch($value) {
    $value = sanitize_text_field($value);
    if (empty($value)) {
        $value = 'main';
    }
    return $value;
}

function snippet_aggregator_sanitize_webhook_secret($value) {
    $value = sanitize_text_field($value);
    if (empty($value)) {
        $value = wp_generate_password(32, false);
    }
    return $value;
}

// Add update check action for webhook
add_action('snippet_aggregator_update_check', 'snippet_aggregator_check_for_updates');

function snippet_aggregator_check_for_updates() {
    global $snippet_aggregator_updater;
    if ($snippet_aggregator_updater) {
        $snippet_aggregator_updater->checkForUpdates();
    }
} 