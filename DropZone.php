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

    public function initialize(ParameterManager $parameters, Container $container)
    {
        define('WOODY_LIB_DROPZONE_VERSION', '1.0.2');
        define('WOODY_LIB_DROPZONE_ROOT', __FILE__);
        define('WOODY_LIB_DROPZONE_DIR_ROOT', dirname(WOODY_LIB_DROPZONE_ROOT));

        parent::initialize($parameters, $container);
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

        add_action('init', [$this, 'upgrade']);

        add_filter('woody_dropzone_get', [$this, 'get'], 10, 1);
        add_action('woody_dropzone_set', [$this, 'set'], 10, 5);
        add_action('woody_dropzone_delete', [$this, 'delete'], 10, 1);
        add_action('woody_dropzone_warm', [$this, 'warm'], 10, 1);
        add_action('woody_dropzone_warm_all', [$this, 'warm_all'], 10);
    }

    public function get($name = null)
    {
        return $this->dropZoneManager->get($name);
    }

    public function set($name = null, $data = null, $expired = 0, $action = null, $params = null)
    {
        $this->dropZoneManager->set($name, $data, $expired, $action, $params);
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
        $this->dropZoneManager->warm_all();
    }

    public function upgrade()
    {
        $saved_version = (int) get_option('woody_dropzone_db_version');
        if ($saved_version < 100 && $this->upgrade_100()) {
            update_option('woody_dropzone_db_version', 100);
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
            `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `data` longtext CHARACTER SET utf8 NOT NULL,
            `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `expired` bigint(20) DEFAULT NULL,
            `action` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `params` longtext CHARACTER SET utf8 DEFAULT NULL,
            PRIMARY KEY (`id`, `name`)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        return empty($wpdb->last_error);
    }
}
