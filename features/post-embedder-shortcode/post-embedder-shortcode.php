<?php
/**
 * Shortcode for embedding post content by ID or slug
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shortcode [post_embed id="1"] 
function post_embed_shortcode($atts) {
   $atts = shortcode_atts(array(
       'id' => '',
       'slug' => ''
   ), $atts);
   
   if (empty($atts['id']) && empty($atts['slug'])) {
       return '';
   }
   
   if (!empty($atts['id'])) {
       $post = get_post($atts['id']);
   } else {
       $post = get_page_by_path($atts['slug'], OBJECT, 'utility');
   }
   
   if (!$post || $post->post_status !== 'publish') {
       return '';
   }
   
   return apply_filters('the_content', $post->post_content);
}
add_shortcode('post_embed', 'post_embed_shortcode'); 