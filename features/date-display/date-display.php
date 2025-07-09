<?php
/**
 * Date Display Feature
 * 
 * Adds a current date display to the WordPress admin dashboard.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Snippet_Aggregator_Date_Display {
    /**
     * Initialize the feature
     */
    public static function init() {
        add_action('wp_dashboard_setup', [self::class, 'add_dashboard_widget']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Add the dashboard widget
     */
    public static function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'snippet_aggregator_date_display',
            __('Current Date & Time', 'snippet-aggregator'),
            [self::class, 'render_widget']
        );
    }

    /**
     * Render the dashboard widget content
     */
    public static function render_widget() {
        $current_time = current_time('mysql');
        $date = wp_date(get_option('date_format'), strtotime($current_time));
        $time = wp_date(get_option('time_format'), strtotime($current_time));
        $timezone = wp_timezone_string();
        
        ?>
        <div class="snippet-aggregator-date-display">
            <div class="date-display-row">
                <span class="date-label"><?php _e('Date:', 'snippet-aggregator'); ?></span>
                <span class="date-value" id="sa-current-date"><?php echo esc_html($date); ?></span>
            </div>
            <div class="date-display-row">
                <span class="date-label"><?php _e('Time:', 'snippet-aggregator'); ?></span>
                <span class="date-value" id="sa-current-time"><?php echo esc_html($time); ?></span>
            </div>
            <div class="date-display-row">
                <span class="date-label"><?php _e('Timezone:', 'snippet-aggregator'); ?></span>
                <span class="date-value"><?php echo esc_html($timezone); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue CSS and JavaScript assets
     */
    public static function enqueue_assets($hook) {
        if ($hook !== 'index.php') { // Only load on dashboard
            return;
        }

        wp_enqueue_style(
            'snippet-aggregator-date-display',
            SNIPPET_AGGREGATOR_URL . 'features/date-display/css/style.css',
            [],
            SNIPPET_AGGREGATOR_VERSION
        );

        wp_enqueue_script(
            'snippet-aggregator-date-display',
            SNIPPET_AGGREGATOR_URL . 'features/date-display/js/script.js',
            ['jquery'],
            SNIPPET_AGGREGATOR_VERSION,
            true
        );

        // Pass localized data to JavaScript
        wp_localize_script(
            'snippet-aggregator-date-display',
            'saDateDisplay',
            [
                'dateFormat' => get_option('date_format'),
                'timeFormat' => get_option('time_format'),
                'timezone' => wp_timezone_string()
            ]
        );
    }
}

// Initialize the feature
Snippet_Aggregator_Date_Display::init(); 