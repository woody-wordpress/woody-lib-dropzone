<?php

/**
 * Woody Library DropZone
 * @author Léo POIROUX
 * @copyright Raccourci Agency 2021
 */

namespace Woody\Lib\DropZone\Services;

class DropZoneManager
{
    public function get($name = null)
    {
        if (!empty($name)) {
            $name = sanitize_title($name);
            $data = wp_cache_get('dropzone_' . $name, 'woody');

            if (empty($data)) {
                $result = $this->getItem($name);

                // Save data inside cache if not already
                if (!empty($result['data'])) {
                    $data = maybe_unserialize($result['data']);

                    //output_success('DROPZONE GET "' . $name . '" (BDD) : ' . $this->isBlob($data));

                    if ($result['cache']) {
                        wp_cache_set('dropzone_' . $name, $data, 'woody', $result['expired']);
                    }
                } else {
                    //output_warning('DROPZONE GET "' . $name . '" (BDD) : not exist');
                }
            } else {
                //output_success('DROPZONE GET "' . $name . '" (CACHE) : ' . $this->isBlob($data));
            }

            return $data;
        }
    }

    public function set($name = null, $data = null, $expired = null, $args = [])
    {
        global $wpdb;

        if(empty($name)) {
            output_error('DROPZONE SET (empty name)');
            exit();
        }

        if(empty($data)) {
            output_error('DROPZONE SET "' . $name . '" (empty data)');
            exit();
        }

        if (!empty($name) && !empty($data)) {
            // Controle Expired value
            if (empty($expired) || !is_int($expired)) {
                $expired = null;
            }

            if (!is_array($args)) {
                output_error('DROPZONE SET "' . $name . '" (args must be a array)');
                exit();
            }

            $args = array_merge([
                'action' => null,
                'params' => null,
                'cache' => true
            ], $args);

            // Controle Action value
            $action = $args['action'];
            if (!empty($action) && !is_string($action)) {
                output_error('DROPZONE SET "' . $name . '" (action must be a string)');
                exit();
            }

            // Controle Params value
            $params = maybe_serialize($args['params']);

            // Controle Cache value
            $cache = $args['cache'];
            if (!is_bool($cache)) {
                output_error('DROPZONE SET "' . $name . '" (cache must be a boolean)');
                exit();
            }

            // Create Query
            $name = sanitize_title($name);
            $query = [
                'name' => $name,
                'data' => maybe_serialize($data),
                'created' => current_time('mysql'),
                'expired' => $expired,
                'action' => $action,
                'params' => $params,
                'cache' => $cache,
            ];

            $results = $wpdb->get_results(sprintf("SELECT id FROM %swoody_dropzone WHERE name = '%s'", $wpdb->prefix, $name), ARRAY_A);
            $result = current($results);
            if (!empty($result['id'])) {
                $query['id'] = $result['id'];
                $wpdb->update(sprintf('%swoody_dropzone', $wpdb->prefix), $query, ['id' => $result['id']]);

                if (defined(\WP_CLI::class) && WP_CLI) {
                    $query['data'] = $this->isBlob($query['data']);
                    //output_success('DROPZONE UPDATE "' . $name . '" : ' . json_encode($query, JSON_THROW_ON_ERROR));
                    output_success(sprintf('DROPZONE UPDATE "%s"', $name));
                }
            } else {
                $wpdb->insert(sprintf('%swoody_dropzone', $wpdb->prefix), $query);

                if (defined(\WP_CLI::class) && WP_CLI) {
                    $query['data'] = $this->isBlob($query['data']);
                    //output_success('DROPZONE INSERT "' . $name . '" : ' . json_encode($query, JSON_THROW_ON_ERROR));
                    output_success(sprintf('DROPZONE INSERT "%s"', $name));
                }
            }

            // Save inside Memcached if set
            if ($cache) {
                wp_cache_set('dropzone_' . $name, $data, 'woody', $expired);
            }
        }
    }

    public function delete($name = null)
    {
        global $wpdb;

        if (!empty($name)) {
            $name = sanitize_title($name);
            $wpdb->delete(sprintf('%swoody_dropzone', $wpdb->prefix), [
                'name' => $name,
            ]);

            output_success(sprintf('DROPZONE DELETE "%s"', $name));
            wp_cache_delete('dropzone_' . $name, 'woody');
        }
    }

    public function warm($name = null)
    {
        if (!empty($name)) {
            $name = sanitize_title($name);
            $result = $this->getItem($name);

            if (!empty($result['action'])) {
                // Params can be pass to function as string or array
                // dropzone_set('name', 'data', 86400, ['action' => 'my_action_hook']);
                // dropzone_set('name', 'data', null, ['action' => 'my_action_hook']);
                // dropzone_set('name', 'data', null, ['action' => 'my_action_hook', 'cache' => false]);
                // dropzone_set('name', 'data', 0, ['action' => 'my_action_hook', 'params' => 'my_var']);
                // dropzone_set('name', 'data', 0, ['action' => 'my_action_hook', 'params' => ['my_var', 'my_var2']]);

                $func_array = [];
                if (!empty($result['params'])) {
                    $params = maybe_unserialize($result['params']);
                    if (is_array($params)) {
                        $func_array = $params;
                    } else {
                        $func_array[] = $params;
                    }
                }

                // Added action on first position
                array_unshift($func_array, $result['action']);
                //output_success('DROPZONE WARM "' . $name . '" : ' . json_encode($func_array, JSON_THROW_ON_ERROR));
                output_success(sprintf('DROPZONE WARM "%s"', $name));

                // Delete before warm
                $this->delete($name);

                // Call do_action()
                // call_user_func_array('do_action', $func_array); new syntax
                do_action(...$func_array);
            } else {
                output_error('DROPZONE WARM "' . $name . '" : no action to WARM');
            }
        }
    }

    public function warm_all()
    {
        global $wpdb;

        $return = [];
        $results = $wpdb->get_results(sprintf('SELECT name, expired, created, action FROM %swoody_dropzone WHERE action is not NULL', $wpdb->prefix), ARRAY_A);
        if (!empty($results)) {
            foreach ($results as $result) {
                $result = $this->checkIfExpired($result);
                if (!empty($result['name'])) {
                    $this->warm($result['name']);
                    $return[] = $result['name'];
                }
            }
        }

        return $return;
    }

    public function cleanup()
    {
        global $wpdb;

        $results = $wpdb->get_results(sprintf('SELECT name, expired, created FROM %swoody_dropzone', $wpdb->prefix), ARRAY_A);
        if (!empty($results)) {
            foreach ($results as $result) {
                $this->checkIfExpired($result);
            }
        }
    }

    private function getItem($name = null)
    {
        global $wpdb;

        if (!empty($name)) {
            $name = sanitize_title($name);
            $results = $wpdb->get_results(sprintf("SELECT * FROM %swoody_dropzone WHERE name = '%s'", $wpdb->prefix, $name), ARRAY_A);
            $result = current($results);
            return $this->checkIfExpired($result);
        }
    }

    private function isBlob($val)
    {
        if (is_bool($val)) {
            return $val;
        } else {
            $val = (is_string($val)) ? $val : json_encode($val, JSON_THROW_ON_ERROR);
            if (strlen($val) > 200) {
                return '--- BLOB (more than 200 characters) ---';
            } else {
                return $val;
            }
        }
    }

    private function checkIfExpired($result = [])
    {
        // Return empty if expired and delete item
        if (!empty($result['expired']) && !empty($result['created'])) {
            $expired = (empty($result['expired'])) ? 0 : $result['expired'];
            $created = \DateTime::createFromFormat('Y-m-d H:i:s', $result['created'], wp_timezone())->getTimestamp();

            if (time() > ($created + $expired)) {
                output_warning('DROPZONE EXPIRE "' . $result['name'] . '" since ' . (time() - $created + $expired) . 's');
                $this->delete($result['name']);
                return;
            }
        }

        return $result;
    }
}
