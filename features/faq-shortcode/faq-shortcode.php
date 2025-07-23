<?php
/**
 * FAQ shortcode that generates FAQ subsets from a template page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue FAQ styles
function enqueue_faq_styles() {
    wp_enqueue_style(
        'faq-shortcode-styles',
        plugins_url('faq-styles.css', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'faq-styles.css')
    );
}
add_action('wp_enqueue_scripts', 'enqueue_faq_styles');

// Get template from the faq-template page
function get_faq_template() {
    $page = get_page_by_path('faq-template', OBJECT, ['page', 'utility']);
    return $page ? $page->post_content : '';
}

// Generate FAQ blocks using the template
function generate_faq_blocks($faq_data) {
    $template = get_faq_template();
    if (!$template) return '';
    
    $output = '';
    foreach ($faq_data as $faq) {
        $output .= str_replace(
            ['{QUESTION}', '{ANSWER}'],
            [esc_html($faq['question']), $faq['answer']],
            $template
        );
    }
    
    return $output;
}

// Usage example
function create_faq_post($post_id, $faq_data) {
    $faq_blocks = generate_faq_blocks($faq_data);
    
    if (!$faq_blocks) {
        return new WP_Error('no_template', 'FAQ template page not found');
    }
    
    return wp_update_post([
        'ID' => $post_id,
        'post_content' => $faq_blocks
    ]);
}

// Get FAQ posts by taxonomy tags
function get_faq_data_by_tags($tags = [], $operator = 'IN') {
    if (empty($tags)) return [];
    
    $args = [
        'post_type' => 'faq',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'tax_query' => [
            [
                'taxonomy' => 'faq-tag',
                'field' => 'slug',
                'terms' => $tags,
                'operator' => $operator
            ]
        ]
    ];
    
    $faqs = get_posts($args);
    $faq_data = [];
    
    foreach ($faqs as $faq) {
        $content = get_the_content(null, false, $faq->ID);
        // Remove opening and closing <p> tags, replace middle </p><p> with <br>
        $content = preg_replace('/^<p[^>]*>/', '', $content);
        $content = preg_replace('/<\/p>$/', '', $content);
        $content = preg_replace('/<\/p>\s*<p[^>]*>/', '<br><br>', $content);
        
        $faq_data[] = [
            'question' => get_the_title($faq->ID),
            'answer' => $content
        ];
    }
    
    return $faq_data;
}

// Shortcode for FAQ display
function faq_shortcode($atts) {
    $atts = shortcode_atts([
        'tags' => ''
    ], $atts);
    
    if (empty($atts['tags'])) return '';
    
    // Parse tags and determine operator
    $tags_string = $atts['tags'];
    
    if (strpos($tags_string, '+') !== false) {
        // AND operator - all tags must be present
        $tags = array_map('trim', explode('+', $tags_string));
        $operator = 'AND';
    } else {
        // OR operator (default) - any tag can match
        $tags = array_map('trim', explode(',', $tags_string));
        $operator = 'IN';
    }
    
    $faq_data = get_faq_data_by_tags($tags, $operator);
    
    if (empty($faq_data)) return '';
    
    $block_markup = generate_faq_blocks($faq_data);
    
    return '<div style="margin-top:30px;">' . do_blocks($block_markup) . '</div>';
}
add_shortcode('faq', 'faq_shortcode'); 