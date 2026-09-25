<?php
/**
 * CTA shortcode feature
 */

if (!defined('ABSPATH')) {
    exit;
}

// [cta slug="routing"] or [cta id="12"]. Prefer slug: IDs differ between sites.
add_shortcode('cta', function ($atts) {
    $atts = shortcode_atts(['id' => 0, 'slug' => ''], $atts);
    $post = $atts['slug'] !== ''
        ? get_page_by_path(sanitize_title($atts['slug']), OBJECT, 'cta')
        : get_post((int) $atts['id']);
    if (!$post || $post->post_type !== 'cta' || $post->post_status !== 'publish') return '';
    // No glossary tooltips inside a CTA; a CTA must not take a term's first mention.
    $glossary = has_filter('the_content', 'process_glossary_terms');
    if ($glossary !== false) remove_filter('the_content', 'process_glossary_terms', $glossary);
    $html = apply_filters('the_content', $post->post_content);
    if ($glossary !== false) add_filter('the_content', 'process_glossary_terms', $glossary);
    return $html;
}); 