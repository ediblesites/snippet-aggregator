<?php
/**
 * Remove Meta Box Lite promotional elements
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_head', function () {
    echo 
    '<style>
        
        /* Remove Meta Box Lite ads */
        .mb-cpt-upgrade,
        .mb-dashboard__widget.mb-dashboard__upgrade,
        .mb-dashboard__widget.mb-dashboard__plugins,
        .mb-dashboard__tabs,
        .mb-dashboard__info,
        .mb-dashboard__widget {
            display: none !important;
        }

        /* Remove Pipedrive/CF7 integration ads */
        .updated.below-h2.vx_pro_version {
            display: none;
        }
            
        /* Remove WPPusher ads */
        div#wppusher-welcome-panel {
            display: none;
        }
        
    </style>';
}); 