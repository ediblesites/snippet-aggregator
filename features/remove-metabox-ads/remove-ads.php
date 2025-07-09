<?php
/**
 * Remove Meta Box Lite promotional elements
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_head', function () {
    echo '<style>
        .mb-cpt-upgrade,
        .mb-dashboard__widget.mb-dashboard__upgrade,
        .mb-dashboard__widget.mb-dashboard__plugins,
        .mb-dashboard__tabs,
        .mb-dashboard__info,
        .mb-dashboard__widget {
            display: none !important;
        }
    </style>';
}); 