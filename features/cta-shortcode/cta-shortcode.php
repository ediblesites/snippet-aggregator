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
    // Skip the glossary for this inner pass, and mark the output with
    // data-no-glossary: when the CTA sits in post content, the outer the_content
    // pass runs the glossary filter (priority 20) after do_shortcode (11).
    $glossary = has_filter('the_content', 'process_glossary_terms');
    if ($glossary !== false) remove_filter('the_content', 'process_glossary_terms', $glossary);
    $html = apply_filters('the_content', $post->post_content);
    if ($glossary !== false) add_filter('the_content', 'process_glossary_terms', $glossary);
    // Mark every top-level element, so a CTA without an outer group is covered too.
    $html = snippet_aggregator_cta_mark_no_glossary($html);
    return $html;
});

/**
 * Add data-no-glossary to every top-level element of the CTA's HTML.
 */
function snippet_aggregator_cta_mark_no_glossary($html) {
    if (class_exists('WP_HTML_Processor')) {
        $p = WP_HTML_Processor::create_fragment($html);
        if ($p) {
            $top = null;
            while ($p->next_tag()) {
                $depth = $p->get_current_depth();
                if ($top === null) $top = $depth;
                if ($depth === $top) $p->set_attribute('data-no-glossary', '');
            }
            $marked = $p->get_updated_html();
            if (null === $p->get_last_error() && $marked !== '') return $marked;
        }
    }
    // Fallback: mark the first element only.
    $tags = new WP_HTML_Tag_Processor($html);
    if ($tags->next_tag()) {
        $tags->set_attribute('data-no-glossary', '');
        $html = $tags->get_updated_html();
    }
    return $html;
}
