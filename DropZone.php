<?php

/**
 * Woody Library DropZone
 * @author Léo POIROUX
 * @copyright Raccourci Agency 2021
 */

namespace Woody\Lib\DropZone;

use Woody\App\Container;
use Woody\Modules\Module;
use Woody\Services\ParameterManager;
use Woody\Lib\DropZone\Commands\DropZoneCommand;

final class DropZone extends Module
{
    protected static $key = 'woody_lib_dropzone';

    protected $refresh_list = [];

    protected $dropZoneManager;

    public function initialize(ParameterManager $parameterManager, Container $container)
    {
        define('WOODY_LIB_DROPZONE_VERSION', '1.3.5');
        define('WOODY_LIB_DROPZONE_ROOT', __FILE__);
        define('WOODY_LIB_DROPZONE_DIR_ROOT', dirname(WOODY_LIB_DROPZONE_ROOT));

        parent::initialize($parameterManager, $container);
        $this->dropZoneManager = $this->container->get('dropzone.manager');
        require_once WOODY_LIB_DROPZONE_DIR_ROOT . '/Helpers/Helpers.php';
    }

    public function registerCommands()
    {
        \WP_CLI::add_command('woody:dropzone', new DropZoneCommand($this->container));
    }

    public static function dependencyServiceDefinitions()
    {
        return \Woody\Lib\DropZone\Configurations\Services::loadDefinitions();
    }

    public function subscribeHooks()
    {
        register_activation_hook(WOODY_LIB_DROPZONE_ROOT, [$this, 'activate']);
        register_deactivation_hook(WOODY_LIB_DROPZONE_ROOT, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
        add_action('init', [$this, 'scheduleDropzoneCleanup']);
        add_action('woody_theme_update', [$this, 'upgrade'], 2);

        add_filter('woody_dropzone_get', [$this, 'get'], 10, 1);
        add_action('woody_dropzone_set', [$this, 'set'], 10, 4);
        add_action('woody_dropzone_delete', [$this, 'delete'], 10, 1);
        add_action('woody_dropzone_warm', [$this, 'warm'], 10, 1);
        add_filter('woody_dropzone_warm_all', [$this, 'warm_all'], 10);
        add_action('woody_dropzone_cleanup', [$this, 'cleanup'], 10);
    }

    // ------------------------
    // CRON
    // ------------------------

    public function scheduleDropzoneCleanup()
    {
        if (!wp_next_scheduled('woody_dropzone_cleanup')) {
            wp_schedule_event(time(), 'daily', 'woody_dropzone_cleanup');
        }
    }

    // ------------------------
    // GETTER / SETTER
    // ------------------------

    public function get($name = null)
    {
        return $this->dropZoneManager->get($name);
    }

    public function set($name = null, $data = null, $expired = null, $args = [])
    {
        $this->dropZoneManager->set($name, $data, $expired, $args);
    }

    public function delete($name = null)
    {
        $this->dropZoneManager->delete($name);
    }

    public function warm($name = null)
    {
        $this->dropZoneManager->warm($name);
    }

    public function warm_all()
    {
        return $this->dropZoneManager->warm_all();
    }

    public function cleanup()
    {
        $this->dropZoneManager->cleanup();
    }

    // ------------------------
    // ADMIN BAR
    // ------------------------
    public function init()
    {
        if (is_admin()) {
            $user = wp_get_current_user();
            if (in_array('administrator', $user->roles)) {
                add_action('admin_bar_menu', [$this, 'warm_all_adminbar'], 100);
                if (isset($_GET['refresh_dropzone']) && check_admin_referer('dropzone')) {
                    $this->refresh_dropzone();
                }
            }
        }
    }

    public function warm_all_adminbar($admin_bar)
    {
        $admin_bar->add_menu(array(
            'id'    => 'warm-all-dropzone',
            'title' => '<span class="ab-icon dashicons dashicons-update" style="top:2px;" aria-hidden="true"></span> Refresh Dropzone',
            'href'  => wp_nonce_url(add_query_arg('refresh_dropzone', 1), 'dropzone'),
            'meta'  => array(
                'title' => 'Refresh Dropzone',
            )
        ));
    }

    public function refresh_dropzone()
    {
        $this->refresh_list = $this->warm_all();
        add_action('admin_notices', [$this, 'refresh_message']);
    }

    public function refresh_message()
    {
        if (!empty($this->refresh_list)) {
            echo '<div id="message" class="updated fade"><p><strong>Dropzone is refreshed</strong>';
            foreach ($this->refresh_list as $item) {
                echo '<br />&nbsp;•&nbsp;' . $item;
            }

            echo '</p></div>';
        } else {
            echo '<div id="message" class="error fade"><p><strong>Dropzone is empty</strong></p></div>';
        }
    }

    // ------------------------
    // DATABASE UPGRADE
    // ------------------------
    public function upgrade()
    {
        $saved_version = (int) get_option('woody_dropzone_db_version');
        if ($saved_version < 100 && $this->upgrade_100()) {
            update_option('woody_dropzone_db_version', 100);
        }

        if ($saved_version < 200 && $this->upgrade_200()) {
            update_option('woody_dropzone_db_version', 200);
        }
    }

    private function upgrade_100()
    {
        global $wpdb;

        // Apply upgrade
        $sql = [];
        $charset_collate = $wpdb->get_charset_collate();
        $sql[] = "CREATE TABLE `{$wpdb->base_prefix}woody_dropzone` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) CHARACTER SET utf8 NOT NULL,
            `data` longtext CHARACTER SET utf8 NOT NULL,
            `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `expired` bigint(20) DEFAULT NULL,
            `action` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
            `params` longtext CHARACTER SET utf8 DEFAULT NULL,
            PRIMARY KEY (`id`, `name`)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        if (empty($wpdb->last_error)) {
            output_success('+ woody-lib-dropzone upgrade_100');
            return true;
        } else {
            output_error('+ woody-lib-dropzone upgrade_100');
            return false;
        }
    }

    private function upgrade_200()
    {
        global $wpdb;

        // Apply upgrade
        $sql = sprintf('ALTER TABLE `%swoody_dropzone` ADD `cache` BOOLEAN default 1;', $wpdb->base_prefix);
        $wpdb->query($sql);
        if (empty($wpdb->last_error)) {
            output_success('+ woody-lib-dropzone upgrade_200');
            return true;
        } else {
            output_error('+ woody-lib-dropzone upgrade_200');
            return false;
        }
    }
}
