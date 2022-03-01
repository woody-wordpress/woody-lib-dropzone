<?php

/**
 * Woody Library DropZone
 * @author Léo POIROUX
 * @copyright Raccourci Agency 2021
 */

namespace Woody\Lib\DropZone\Commands;

use Woody\App\Container;

// WP_SITE_KEY=superot wp woody:dropzone get %key%
// WP_SITE_KEY=superot wp woody:dropzone set %key% %data% %expired% %action%
// WP_SITE_KEY=superot wp woody:dropzone delete %key%
// WP_SITE_KEY=superot wp woody:dropzone warm %key%
// WP_SITE_KEY=superot wp woody:dropzone warm_all
// WP_SITE_KEY=superot wp woody:dropzone cleanup

class DropZoneCommand
{
    private \Woody\App\Container $container;

    private $dropZoneManager;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->dropZoneManager = $this->container->get('dropzone.manager');
    }

    public function get($args, $assoc_args)
    {
        [$name] = $args;
        $this->dropZoneManager->get($name);
    }

    public function set($args, $assoc_args)
    {
        [$name, $data, $expired, $action, $params] = $args;
        $this->dropZoneManager->set($name, $data, $expired, $action, $params);
    }

    public function delete($args, $assoc_args)
    {
        [$name] = $args;
        $this->dropZoneManager->delete($name);
    }

    public function warm($args, $assoc_args)
    {
        [$name] = $args;
        $this->dropZoneManager->warm($name);
    }

    public function warm_all($args, $assoc_args)
    {
        $this->dropZoneManager->warm_all();
    }

    public function cleanup($args, $assoc_args)
    {
        $this->dropZoneManager->cleanup();
    }
}
