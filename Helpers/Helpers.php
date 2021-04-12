<?php

/**
 * Woody Library DropZone
 * @author Léo POIROUX
 * @copyright Raccourci Agency 2021
 */

function dropzone_get($name = null)
{
    return apply_filters('woody_dropzone_get', $name);
}

function dropzone_set($name = null, $data = null, $expired = 0, $action = null, $params = null)
{
    do_action('woody_dropzone_set', $name, $data, $expired, $action, $params);
}

function dropzone_delete($name = null)
{
    do_action('woody_dropzone_delete', $name);
}
