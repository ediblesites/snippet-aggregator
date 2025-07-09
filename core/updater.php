<?php
/**
 * GitHub updater integration for Snippet Aggregator plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize the GitHub updater
 */
function snippet_aggregator_init_updater() {
    // Only proceed if we have the required settings
    $github_repo = snippet_aggregator_get_setting('github_repo');
    $github_token = snippet_aggregator_get_setting('github_token');
    
    if (empty($github_repo) || empty($github_token)) {
        return;
    }
    
    // Check if plugin update checker library exists
    if (!class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
        Snippet_Aggregator_Logger::error('updater', 'Plugin Update Checker library not found');
        return;
    }
    
    try {
        // Initialize GitHub updater
        $myUpdateChecker = PucFactory::buildUpdateChecker(
            "https://github.com/{$github_repo}/",
            SNIPPET_AGGREGATOR_FILE,
            'snippet-aggregator'
        );
        
        // For private repos
        $myUpdateChecker->setAuthentication($github_token);
        
        // Optional: Enable branch switching
        $myUpdateChecker->setBranch(snippet_aggregator_get_setting('github_branch', 'main'));
        
        Snippet_Aggregator_Logger::info('updater', 'GitHub updater initialized successfully');
    } catch (Exception $e) {
        Snippet_Aggregator_Logger::error('updater', 'Failed to initialize GitHub updater: ' . $e->getMessage());
    }
}
add_action('init', 'snippet_aggregator_init_updater');

/**
 * Handle GitHub webhook requests
 */
function snippet_aggregator_handle_github_webhook() {
    // Verify this is a GitHub webhook request
    if (!isset($_SERVER['HTTP_X_GITHUB_EVENT'])) {
        wp_die('Invalid request', 403);
    }
    
    // Get the webhook secret
    $webhook_secret = snippet_aggregator_get_setting('webhook_secret');
    if (empty($webhook_secret)) {
        wp_die('Webhook secret not configured', 403);
    }
    
    // Get and verify the payload
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    if (!snippet_aggregator_verify_github_signature($payload, $signature, $webhook_secret)) {
        wp_die('Invalid signature', 403);
    }
    
    // Parse the payload
    $data = json_decode($payload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_die('Invalid payload', 400);
    }
    
    // Check if this is a push to your main branch
    if ($data['ref'] === 'refs/heads/main') {
        global $myUpdateChecker;
        $myUpdateChecker->checkForUpdates();
        
        Snippet_Aggregator_Logger::info('updater', 'Webhook received, triggering update check');
    }
    
    wp_die('OK - No action needed', 200);
}
add_action('wp_ajax_nopriv_snippet_aggregator_github_webhook', 'snippet_aggregator_handle_github_webhook');
add_action('wp_ajax_snippet_aggregator_github_webhook', 'snippet_aggregator_handle_github_webhook');

/**
 * Verify GitHub webhook signature
 *
 * @param string $payload The raw payload
 * @param string $signature The signature from GitHub
 * @param string $secret The webhook secret
 * @return bool
 */
function snippet_aggregator_verify_github_signature($payload, $signature, $secret) {
    if (empty($signature)) {
        return false;
    }
    
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}

/**
 * Add GitHub configuration fields to the settings page
 */
function snippet_aggregator_github_settings_fields() {
    add_settings_section(
        'snippet_aggregator_github_settings',
        __('GitHub Integration', 'snippet-aggregator'),
        function() {
            echo '<p>' . __('Configure GitHub integration for automatic updates.', 'snippet-aggregator') . '</p>';
        },
        'snippet-aggregator'
    );
    
    // GitHub repository field
    add_settings_field(
        'github_repo',
        __('GitHub Repository', 'snippet-aggregator'),
        function() {
            $value = snippet_aggregator_get_setting('github_repo');
            echo '<input type="text" name="github_repo" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">' . __('Format: username/repository', 'snippet-aggregator') . '</p>';
        },
        'snippet-aggregator',
        'snippet_aggregator_github_settings'
    );
    
    // GitHub token field
    add_settings_field(
        'github_token',
        __('GitHub Token', 'snippet-aggregator'),
        function() {
            $value = snippet_aggregator_get_setting('github_token');
            echo '<input type="password" name="github_token" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">' . __('Personal access token with repo access', 'snippet-aggregator') . '</p>';
        },
        'snippet-aggregator',
        'snippet_aggregator_github_settings'
    );
    
    // Branch field
    add_settings_field(
        'github_branch',
        __('GitHub Branch', 'snippet-aggregator'),
        function() {
            $value = snippet_aggregator_get_setting('github_branch', 'main');
            echo '<input type="text" name="github_branch" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">' . __('Branch to track for updates (default: main)', 'snippet-aggregator') . '</p>';
        },
        'snippet-aggregator',
        'snippet_aggregator_github_settings'
    );
    
    // Webhook secret field
    add_settings_field(
        'webhook_secret',
        __('Webhook Secret', 'snippet-aggregator'),
        function() {
            $value = snippet_aggregator_get_setting('webhook_secret');
            if (empty($value)) {
                $value = wp_generate_password(32, false);
                snippet_aggregator_update_setting('webhook_secret', $value);
            }
            echo '<input type="text" name="webhook_secret" value="' . esc_attr($value) . '" class="regular-text" readonly>';
            echo '<button type="button" class="button" onclick="this.previousElementSibling.select();">' . 
                 __('Select', 'snippet-aggregator') . '</button>';
            echo '<p class="description">' . __('Use this secret when configuring the webhook in GitHub', 'snippet-aggregator') . '</p>';
            
            // Display webhook URL
            $webhook_url = add_query_arg('action', 'snippet_aggregator_github_webhook', admin_url('admin-ajax.php'));
            echo '<p><strong>' . __('Webhook URL:', 'snippet-aggregator') . '</strong><br>';
            echo '<input type="text" value="' . esc_url($webhook_url) . '" class="large-text" readonly>';
            echo '<button type="button" class="button" onclick="this.previousElementSibling.select();">' . 
                 __('Select', 'snippet-aggregator') . '</button></p>';
        },
        'snippet-aggregator',
        'snippet_aggregator_github_settings'
    );
} 