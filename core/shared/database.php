<?php
/**
 * Database utility functions for Snippet Aggregator plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create a feature-specific database table
 *
 * @param string $feature_id Feature identifier
 * @param string $schema Table schema SQL
 * @return bool
 */
function snippet_aggregator_create_table($feature_id, $schema) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . "snippet_aggregator_{$feature_id}";
    
    // Include WordPress database upgrade functions
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name ($schema) {$wpdb->get_charset_collate()};";
    
    // Execute the SQL using dbDelta for safe table creation/updates
    dbDelta($sql);
    
    // Track created tables
    $created_tables = get_option('snippet_aggregator_tables', array());
    if (!in_array($table_name, $created_tables)) {
        $created_tables[] = $table_name;
        update_option('snippet_aggregator_tables', $created_tables);
    }
    
    return true;
}

/**
 * Remove a feature's database table
 *
 * @param string $feature_id Feature identifier
 * @return bool
 */
function snippet_aggregator_remove_table($feature_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . "snippet_aggregator_{$feature_id}";
    
    // Drop the table
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    // Remove from tracked tables
    $created_tables = get_option('snippet_aggregator_tables', array());
    $created_tables = array_diff($created_tables, array($table_name));
    update_option('snippet_aggregator_tables', $created_tables);
    
    return true;
}

/**
 * Clean up all plugin database tables
 */
function snippet_aggregator_cleanup_tables() {
    $created_tables = get_option('snippet_aggregator_tables', array());
    
    foreach ($created_tables as $table) {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    delete_option('snippet_aggregator_tables');
} 