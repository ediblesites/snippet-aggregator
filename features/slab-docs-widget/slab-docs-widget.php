<?php
/**
 * Adds a dashboard widget that links to Slab documentation
 */

if (!defined('ABSPATH')) {
    exit;
}

// Dashboard panel linking to Slab documentation
add_action('wp_dashboard_setup', 'add_slab_docs_dashboard_widget');

function add_slab_docs_dashboard_widget() {
    wp_add_dashboard_widget(
        'slab_docs_widget',
        'Documentation',
        'slab_docs_widget_content'
    );
}

function slab_docs_widget_content() {
    echo '<p><a href="https://pcibooking.slab.com/" target="_blank" class="button button-primary">Documentation on Slab</a></p>';
    echo '<p>Access project documentation and resources.</p>';
} 