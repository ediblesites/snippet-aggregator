<?php
/**
 * Test script for GitHub webhook functionality
 * 
 * Usage: php test-webhook.php
 */

// Load WordPress core
if (file_exists(dirname(__DIR__, 4) . '/wp-load.php')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
} else {
    die("WordPress core not found. Please check the path.\n");
}

// Test configuration
$webhook_url = admin_url('admin-ajax.php') . '?action=snippet_aggregator_github_webhook';
$webhook_secret = get_option('snippet_aggregator_webhook_secret');

if (empty($webhook_secret)) {
    die("Webhook secret not found in WordPress options. Please set it up first.\n");
}

// Simulate a GitHub webhook payload
$payload = json_encode([
    'ref' => 'refs/heads/master',
    'repository' => [
        'default_branch' => 'master'
    ]
]);

// Generate signature
$signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook_secret);

// Send webhook request
$ch = curl_init($webhook_url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Hub-Signature-256: ' . $signature,
        'X-GitHub-Event: push'
    ]
]);

echo "Sending request to: $webhook_url\n";
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nResponse (HTTP $status):\n";
echo $response . "\n"; 