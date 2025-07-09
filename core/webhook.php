<?php
/**
 * GitHub webhook handler
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add webhook endpoint
add_action('wp_ajax_nopriv_snippet_aggregator_github_webhook', 'snippet_aggregator_handle_github_webhook');
add_action('wp_ajax_snippet_aggregator_github_webhook', 'snippet_aggregator_handle_github_webhook');

function snippet_aggregator_handle_github_webhook() {
    // Verify webhook signature
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    if (!snippet_aggregator_verify_github_signature($payload, $signature)) {
        snippet_aggregator_log('webhook', 'Invalid webhook signature', 'error');
        wp_die('Unauthorized', 401);
    }
    
    // Parse webhook data
    $data = json_decode($payload, true);
    if (!is_array($data)) {
        snippet_aggregator_log('webhook', 'Invalid webhook payload', 'error');
        wp_die('Invalid payload', 400);
    }
    
    // Check if this is a push to the tracked branch
    $tracked_branch = get_option('snippet_aggregator_github_branch', 'main');
    $ref = $data['ref'] ?? '';
    if ($ref !== "refs/heads/{$tracked_branch}") {
        snippet_aggregator_log('webhook', sprintf('Ignoring push to %s (tracking %s)', $ref, $tracked_branch), 'info');
        wp_die('OK', 200);
    }
    
    // Check for plugin updates
    if (function_exists('snippet_aggregator_check_for_updates')) {
        snippet_aggregator_log('webhook', 'Checking for updates', 'info');
        snippet_aggregator_check_for_updates();
    }
    
    wp_die('OK', 200);
}

function snippet_aggregator_verify_github_signature($payload, $signature) {
    $webhook_secret = get_option('snippet_aggregator_webhook_secret');
    if (empty($webhook_secret)) {
        return false;
    }
    
    if (empty($signature)) {
        return false;
    }
    
    $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);
    return hash_equals($expected_signature, $signature);
} 