<?php
/**
 * Adds quick edit links to archive entries for users with edit permissions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds an "Edit This" link to archive entries for users with edit permissions
 */
function add_archive_edit_link() {
    if (!is_user_logged_in() || !current_user_can('edit_posts')) {
        return;
    }
    
    global $post;
    if (!$post) {
        return;
    }
    
    $edit_url = get_edit_post_link($post->ID);
    if (!$edit_url) {
        return;
    }
    
    echo '<a href="' . esc_url($edit_url) . '" class="edit-link" style="margin-left: 8px; text-decoration: none; display: inline;">🖉</a>';
}

/**
 * Hook into the post content or excerpt only on archive pages
 */
function maybe_add_archive_edit_link($content) {
    if (!is_archive()) {
        return $content;
    }
    
    ob_start();
    add_archive_edit_link();
    $edit_link = ob_get_clean();
    
    return $content . $edit_link;
}

// Add to both content and excerpt on archive pages
add_filter('the_content', 'maybe_add_archive_edit_link');
add_filter('the_excerpt', 'maybe_add_archive_edit_link'); 