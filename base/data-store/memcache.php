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
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +---------------------------+
// | tiny_api_Memcache_Manager |
// +---------------------------+
//

class tiny_api_Memcache_Manager
{
    private static $instance;
    private $handle;

    static function get_instance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new tiny_api_Memcache_Manager();
        }

        return self::$instance;
    }

    function __construct()
    {
        global $__tiny_api_conf__;

        $this->handle = new Memcache();

        if (empty($__tiny_api_conf__[ 'memcached servers' ]))
        {
            throw new tiny_Api_Data_Store_Exception(
                        __CLASS__ . ' cannot be instantiated because no '
                        . 'servers were provided');
        }

        foreach ($__tiny_api_conf__[ 'memcached servers' ] as $server)
        {
            list($ip, $port) = explode(':', $server);

            $this->handle->addServer($ip, $port, true);
        }
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function purge($key)
    {
        $this->handle->delete($key);
        return $this;
    }

    final public function retrieve($key)
    {
        if (($value = @$this->handle->get($key)) === false)
        {
            return null;
        }

        return $value;
    }

    /**
     * $ttl can be one of two values:
     *
     * 1) A UNIX timestamp representing the exact time at which the cache
     *    entry should be expired.
     * 2) The number of seconds from the current time at which the cache
     *    entry should expire.  Note, if you send in the number of seconds
     *    it cannot be larger than 60 * 60 * 24 * 30 or else Memcached will
     *    consider it a UNIX timestamp.
     *
     * For full documentation on TTLs for Memcached, see this URL:
     *
     *  http://us2.php.net/manual/en/memcached.expiration.php
     */
    final public function store($key, $data, $ttl = 0)
    {
        if (@$this->handle->set($key,
                                $data,
                                MEMCACHE_COMPRESSED,
                                $ttl) === false ||
            !empty($php_errormsg))
        {
            throw new tiny_Api_Data_Store_Exception(
                        'attempt to store a value to Memcached failed');
        }

        return $this;
    }
}
?>
