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

function dropzone_set($name = null, $data = null, $expired = null, $args = [])
{
    do_action('woody_dropzone_set', $name, $data, $expired, $args);
}

function dropzone_delete($name = null)
{
    do_action('woody_dropzone_delete', $name);
}

function dropzone_warm($name = null)
{
    do_action('woody_dropzone_warm', $name);
}

function dropzone_warm_all()
{
    return apply_filters('woody_dropzone_warm_all', null);
}
