<?php
/**
 * Removes "Archive:" prefix from custom post type archive titles
 */

if (!defined('ABSPATH')) {
    exit;
}

// Remove "Archive: " prefix from custom post type archive titles
function remove_archive_prefix_from_cpt_titles( $title ) {
    if ( is_post_type_archive() ) {
        return post_type_archive_title( '', false );
    }
    return $title;
}
add_filter( 'get_the_archive_title', 'remove_archive_prefix_from_cpt_titles' ); 