<?php
/**
 * GitHub updater integration
 */

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if (!defined('ABSPATH')) {
    exit;
}

// Initialize GitHub updater
add_action('plugins_loaded', 'snippet_aggregator_init_updater');

function snippet_aggregator_get_updater() {
    static $updater = null;
    
    if ($updater === null) {
        // Get GitHub repo from plugin header
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data(SNIPPET_AGGREGATOR_FILE);
        $github_repo = $plugin_data['GitHub Plugin URI'] ?? '';
        
        // TODO: Future setting in admin
        // $github_repo = get_option('snippet_aggregator_github_repo');
        
        // TODO: For future private repository support
        // $github_token = get_option('snippet_aggregator_github_token');
        
        if (!empty($github_repo)) {
            // Initialize updater
            $updater = PucFactory::buildUpdateChecker(
                $github_repo,
                SNIPPET_AGGREGATOR_FILE,
                'snippet-aggregator'
            );
            
            // TODO: For future private repository support
            // $updater->setAuthentication($github_token);
            
            // TODO: Future setting in admin
            // $updater->setBranch(get_option('snippet_aggregator_github_branch', 'master'));
            $updater->setBranch('master');
        }
    }
    
    return $updater;
}

function snippet_aggregator_init_updater() {
    snippet_aggregator_get_updater();
}

// Register settings
add_action('admin_init', 'snippet_aggregator_register_updater_settings');

function snippet_aggregator_register_updater_settings() {
    // TODO: Future setting in admin
    /*
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
    
    // GitHub branch
    register_setting(
        'snippet_aggregator_settings',
        'snippet_aggregator_github_branch',
        [
            'type' => 'string',
            'description' => 'GitHub branch to track',
            'sanitize_callback' => 'snippet_aggregator_sanitize_github_branch',
            'default' => 'master',
        ]
    );
    */

    // TODO: For future private repository support
    /*
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
    */
    
    // Register webhook secret
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

// TODO: Future setting in admin
/*
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

function snippet_aggregator_sanitize_github_branch($value) {
    $value = sanitize_text_field($value);
    if (empty($value)) {
        $value = 'master';
    }
    return $value;
}
*/

// TODO: For future private repository support
/*
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
*/

// Add update check action for webhook
add_action('snippet_aggregator_update_check', 'snippet_aggregator_check_for_updates');

function snippet_aggregator_check_for_updates() {
    $updater = snippet_aggregator_get_updater();
    if ($updater) {
        $updater->checkForUpdates();
    }
} 