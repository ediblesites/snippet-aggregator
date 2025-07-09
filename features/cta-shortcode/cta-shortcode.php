<?php
/**
 * CTA shortcode feature
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('cta', function ($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $post = get_post((int) $atts['id']);
    if (!$post || $post->post_type !== 'cta' || $post->post_status !== 'publish') return '';
    return apply_filters('the_content', $post->post_content);
}); 