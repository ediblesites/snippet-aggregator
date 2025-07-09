<?php
/**
 * GitHub Webhook Integration
 * 
 * Handles webhook callbacks from GitHub for instant plugin updates.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add webhook endpoint for GitHub
add_action('wp_ajax_nopriv_github_webhook', 'snippet_aggregator_handle_webhook');
add_action('wp_ajax_github_webhook', 'snippet_aggregator_handle_webhook');

function snippet_aggregator_handle_webhook() {
    // Verify webhook signature for security
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    if (!snippet_aggregator_verify_webhook_signature($payload, $signature)) {
        wp_die('Unauthorized', 401);
    }
    
    // Parse webhook data
    $data = json_decode($payload, true);
    
    // Check if this is a push to your main branch
    if ($data['ref'] === 'refs/heads/main') {
        global $myUpdateChecker;
        $myUpdateChecker->checkForUpdates();
        
        // Log the webhook trigger
        error_log('Webhook triggered update check');
    }
    
    wp_die('OK', 200);
}

function snippet_aggregator_verify_webhook_signature($payload, $signature) {
    $webhook_secret = get_option('snippet_aggregator_webhook_secret');
    if (!$webhook_secret) {
        return false;
    }
    
    $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);
    return hash_equals($expected_signature, $signature);
} 