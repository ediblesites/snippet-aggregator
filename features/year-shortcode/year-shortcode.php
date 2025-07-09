<?php
/**
 * Year shortcode feature
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('year', function() {
    return date('Y');
}); 