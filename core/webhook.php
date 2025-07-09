<?php
/**
 * GitHub webhook handler
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add webhook endpoint
add_action('wp_ajax_nopriv_snippet_aggregator_github_webhook', 'snippet_aggregator_handle_webhook');
add_action('wp_ajax_snippet_aggregator_github_webhook', 'snippet_aggregator_handle_webhook');

function snippet_aggregator_handle_webhook() {
    // Get payload
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    // Verify signature
    if (!snippet_aggregator_verify_webhook_signature($payload, $signature)) {
        wp_die('Invalid signature', 401);
    }
    
    // Parse payload
    $data = json_decode($payload, true);
    if (!$data) {
        wp_die('Invalid payload', 400);
    }
    
    // Check if this is a push to the main branch
    if ($data['ref'] === 'refs/heads/main' || $data['ref'] === 'refs/heads/master') {
        // Trigger update check
        do_action('snippet_aggregator_update_check');
        
        // Log webhook trigger
        snippet_aggregator_log('webhook', 'GitHub webhook triggered update check', 'info');
    }
    
    wp_die('OK', 200);
}

function snippet_aggregator_verify_webhook_signature($payload, $signature) {
    $webhook_secret = get_option('snippet_aggregator_webhook_secret');
    if (!$webhook_secret) {
        return false;
    }
    
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);
    return hash_equals($expected, $signature);
}

// Handle webhook secret regeneration
add_action('wp_ajax_snippet_aggregator_regenerate_webhook_secret', 'snippet_aggregator_regenerate_webhook_secret');

function snippet_aggregator_regenerate_webhook_secret() {
    check_ajax_referer('snippet_aggregator_regenerate_webhook_secret');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized', 401);
    }
    
    $new_secret = wp_generate_password(32, false);
    update_option('snippet_aggregator_webhook_secret', $new_secret);
    
    wp_send_json_success();
} 