<?php
/**
 * Disable Gutenberg editor for specific post types
 */

if (!defined('ABSPATH')) {
    exit;
}

// Disable Gutenberg editor for specific post types
function disable_gutenberg_for_post_types($use_block_editor, $post_type) {
   $disabled_post_types = [
       'faq', 
    //   'glossary-item'
   ];
   
   if (in_array($post_type, $disabled_post_types)) {
       return false;
   }
   
   return $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'disable_gutenberg_for_post_types', 10, 2); 