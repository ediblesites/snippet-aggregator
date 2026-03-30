<?php
/**
 * Overrides the default WordPress user-agent with a generic Chrome browser string
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('http_request_args', function ($args) {
    $args['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
    return $args;
});
