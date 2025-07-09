<?php
/**
 * Direct GitHub updater implementation
 */

namespace SnippetAggregator\Core\Updater;

if (!defined('ABSPATH')) {
    exit;
}

class DirectUpdater {
    private $plugin_file;
    private $github_repo;
    private $current_version;
    private $branch;
    private $last_checked;
    private $update_data;

    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->branch = 'master'; // Default branch
        
        // Get GitHub repo from plugin header
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data($plugin_file);
        $this->github_repo = $plugin_data['GitHub Plugin URI'] ?? '';
        $this->current_version = $plugin_data['Version'] ?? '';
        
        // Load cached update data
        $this->update_data = get_option('snippet_aggregator_update_data', array());
        $this->last_checked = get_option('snippet_aggregator_last_update_check', 0);
        
        // Add hooks
        add_action('admin_init', array($this, 'check_for_updates'));
        add_filter('plugin_action_links_' . plugin_basename($plugin_file), array($this, 'add_check_for_updates_link'));
    }

    /**
     * Check if an update is available
     */
    public function check_for_updates() {
        if (empty($this->github_repo)) {
            snippet_aggregator_log('updater', 'No GitHub repo configured', 'error');
            return false;
        }

        try {
            // Get latest release info from GitHub API
            $api_url = sprintf('https://api.github.com/repos/%s/releases/latest', 
                str_replace('https://github.com/', '', $this->github_repo)
            );
            
            $response = wp_remote_get($api_url, array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version')
                )
            ));

            if (is_wp_error($response)) {
                snippet_aggregator_log('updater', 'Failed to check for updates: ' . $response->get_error_message(), 'error');
                return false;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($body['tag_name'])) {
                snippet_aggregator_log('updater', 'Invalid response from GitHub API', 'error');
                return false;
            }

            // Clean version number (remove v prefix if exists)
            $latest_version = ltrim($body['tag_name'], 'v');
            
            // Compare versions
            if (version_compare($latest_version, $this->current_version, '>')) {
                $update_data = array(
                    'version' => $latest_version,
                    'download_url' => $body['zipball_url'],
                    'tested' => $body['body'] ?? '',  // Release notes might contain WP version tested info
                    'requires' => '5.0', // Minimum WP version, could be parsed from release notes
                    'last_updated' => $body['published_at'],
                );
                
                // Cache update data
                update_option('snippet_aggregator_update_data', $update_data);
                update_option('snippet_aggregator_last_update_check', time());
                
                $this->update_data = $update_data;
                $this->last_checked = time();
                
                return $update_data;
            }

        } catch (\Exception $e) {
            snippet_aggregator_log('updater', 'Error checking for updates: ' . $e->getMessage(), 'error');
            return false;
        }

        return false;
    }

    /**
     * Download and install update
     */
    public function install_update($update_info) {
        if (empty($update_info['download_url'])) {
            return false;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        // Store current version for rollback
        $backup_version = $this->current_version;
        
        try {
            // Download package
            $download_file = download_url($update_info['download_url']);
            if (is_wp_error($download_file)) {
                throw new \Exception('Failed to download update: ' . $download_file->get_error_message());
            }

            // Backup current plugin directory
            $backup_dir = WP_CONTENT_DIR . '/upgrade/plugin-backup';
            if (!file_exists($backup_dir)) {
                wp_mkdir_p($backup_dir);
            }
            
            $plugin_slug = dirname(plugin_basename($this->plugin_file));
            $backup_path = $backup_dir . '/' . $plugin_slug . '-' . $backup_version . '.zip';
            
            // Create backup
            if (!class_exists('PclZip')) {
                require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
            }
            $zip = new \PclZip($backup_path);
            $zip->create(dirname($this->plugin_file), PCLZIP_OPT_REMOVE_PATH, dirname(dirname($this->plugin_file)));

            // Perform update
            $upgrader = new \Plugin_Upgrader();
            $result = $upgrader->upgrade($this->plugin_file, array(
                'package' => $download_file,
                'clear_destination' => true,
                'clear_working' => true,
                'hook_extra' => array(
                    'plugin' => plugin_basename($this->plugin_file),
                    'type' => 'plugin',
                    'action' => 'update',
                )
            ));

            // Cleanup downloaded file
            unlink($download_file);

            if ($result) {
                // Update was successful
                $this->current_version = $update_info['version'];
                update_option('snippet_aggregator_version', $update_info['version']);
                snippet_aggregator_log('updater', 'Update installed successfully', 'info');
                
                // Clean old backups (keep last 3)
                $this->cleanup_old_backups($backup_dir, $plugin_slug, 3);
                
                return true;
            }

            throw new \Exception('Failed to install update');

        } catch (\Exception $e) {
            snippet_aggregator_log('updater', $e->getMessage(), 'error');
            
            // Attempt rollback if backup exists
            if (isset($backup_path) && file_exists($backup_path)) {
                snippet_aggregator_log('updater', 'Attempting rollback to version ' . $backup_version, 'info');
                
                $upgrader = new \Plugin_Upgrader();
                $rollback = $upgrader->upgrade($this->plugin_file, array(
                    'package' => $backup_path,
                    'clear_destination' => true,
                    'clear_working' => true
                ));
                
                if ($rollback) {
                    snippet_aggregator_log('updater', 'Rollback successful', 'info');
                } else {
                    snippet_aggregator_log('updater', 'Rollback failed', 'error');
                }
            }
            
            return false;
        }
    }

    /**
     * Clean up old backup files, keeping only the specified number of most recent backups
     */
    private function cleanup_old_backups($backup_dir, $plugin_slug, $keep = 3) {
        $pattern = $backup_dir . '/' . $plugin_slug . '-*.zip';
        $backups = glob($pattern);
        
        if (count($backups) > $keep) {
            // Sort by file modification time
            usort($backups, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // Remove old backups
            $to_remove = array_slice($backups, $keep);
            foreach ($to_remove as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Add "Check for updates" link to plugin actions
     */
    public function add_check_for_updates_link($links) {
        if (!current_user_can('update_plugins')) {
            return $links;
        }

        $check_link = sprintf(
            '<a href="%s">%s</a>',
            wp_nonce_url(admin_url('plugins.php?check_for_update=' . plugin_basename($this->plugin_file)), 'check_for_update'),
            __('Check for updates', 'snippet-aggregator')
        );
        array_unshift($links, $check_link);
        return $links;
    }

    /**
     * Set which branch to check for updates
     */
    public function set_branch($branch) {
        $this->branch = $branch;
    }
} 