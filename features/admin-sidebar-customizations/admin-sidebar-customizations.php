<?php
/**
 * Customizes admin menu styling for custom post types
 */

if (!defined('ABSPATH')) {
    exit;
}

// Custom color for all CPT menu items
function custom_admin_menu_colors() {
    echo '<style>
        #adminmenu #menu-posts-utility .wp-menu-name,
        #adminmenu #menu-posts-faq .wp-menu-name,
        #adminmenu #menu-posts-glossary-item .wp-menu-name,
        #adminmenu #menu-posts-use-case .wp-menu-name,
        #adminmenu #menu-posts-integration .wp-menu-name,
        #adminmenu #menu-posts-cta .wp-menu-name,
		#adminmenu #menu-posts-event .wp-menu-name,

		#adminmenu #menu-posts-utility .wp-menu-image:before,
        #adminmenu #menu-posts-faq .wp-menu-image:before,
        #adminmenu #menu-posts-glossary-item .wp-menu-image:before,
        #adminmenu #menu-posts-use-case .wp-menu-image:before,
        #adminmenu #menu-posts-integration .wp-menu-image:before,
        #adminmenu #menu-posts-cta .wp-menu-image:before,
		#adminmenu #menu-posts-event .wp-menu-image:before {
            color: #08c5d1 !important;
        }
        
        #adminmenu #menu-posts-utility .wp-menu-name:hover,
        #adminmenu #menu-posts-faq .wp-menu-name:hover,
        #adminmenu #menu-posts-glossary-item .wp-menu-name:hover,
        #adminmenu #menu-posts-use-case .wp-menu-name:hover,
        #adminmenu #menu-posts-integration .wp-menu-name:hover,
        #adminmenu #menu-posts-cta .wp-menu-name:hover,
		#adminmenu #menu-posts-event .wp-menu-name:hover {
            filter: brightness(1.2);
        }
    </style>';
}
add_action('admin_head', 'custom_admin_menu_colors'); 