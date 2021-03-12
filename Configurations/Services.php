<?php

/**
 * Woody Library DropZone
 * @author Léo POIROUX
 * @copyright Raccourci Agency 2021
 */

namespace Woody\Lib\DropZone\Configurations;

class Services
{
    private static $definitions;

    private static function definitions()
    {
        return [
            'dropzone.manager' => [
                'class'     => \Woody\Lib\DropZone\Services\DropZoneManager::class,
            ],
        ];
    }

    public static function loadDefinitions()
    {
        if (!self::$definitions) {
            self::$definitions = self::definitions();
        }
        return self::$definitions;
    }
}
