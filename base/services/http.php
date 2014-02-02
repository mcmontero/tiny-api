<?php

// +------------------------------------------------------------+
// | LICENSE                                                    |
// +------------------------------------------------------------+

/**
 * Copyright 2013 Michael C. Montero
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// +------------------------------------------------------------+
// | INCLUDES                                                   |
// +------------------------------------------------------------+

require_once 'base/services/context.php';

// +------------------------------------------------------------+
// | PUBLIC FUNCTIONS                                           |
// +------------------------------------------------------------+

function tiny_api_http_get_cookie($name)
{
    return tiny_api_Http_Manager::get_instance()->get_cookie($name);
}

function tiny_api_http_set_cookie($name,
                                  $value,
                                  $expire = 0,
                                  $path = '/',
                                  $domain = null,
                                  $secure = false,
                                  $http_only = false)
{
    tiny_api_Http_Manager::get_instance()
        ->set_cookie($name,
                     $value,
                     $expire,
                     $path,
                     $domain,
                     $secure,
                     $http_only);
}

// +------------------------------------------------------------+
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +-----------------------+
// | tiny_api_Http_Manager |
// +-----------------------+
//

class tiny_api_Http_Manager
{
    private static $instance;

    function __construct()
    {
        $this->reset();
    }

    static function get_instance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new tiny_api_Http_Manager();
        }

        return self::$instance;
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function get_cookie($name)
    {
        return array_key_exists($name, $_COOKIE) ?  $_COOKIE[ $name ] : null;
    }

    final public function reset()
    {
        $_COOKIE = array();
    }

    final public function set_cookie($name,
                                     $value,
                                     $expire = 0,
                                     $path = '/',
                                     $domain = null,
                                     $secure = false,
                                     $http_only = false)
    {
        if (env_unit_test())
        {
            $_COOKIE[ $name ] = $value;
        }
        else
        {
            @setcookie($name,
                       $value,
                       $expire,
                       $path,
                       $domain,
                       $secure,
                       $http_only);
        }

        return $this;
    }
}
?>
