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

require_once 'base/data-store/memcache.php';

// +------------------------------------------------------------+
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +---------------------+
// | tiny_api_Base_Rdbms |
// +---------------------+
//

class tiny_api_Base_Rdbms
extends tiny_api_Base_Data_Store
{
    private $memcache_key;
    private $memcache_ttl;

    function __construct()
    {
        parent::__construct();
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    public function create($target,
                           array $data,
                           $return_insert_id = true)
    {
        return ($return_insert_id ? '' : null);
    }

    public function delete($target,
                           array $where,
                           array $binds = array())
    {
        return false;
    }

    final public function memcache($key, $ttl = 0)
    {
        $this->memcache_key = $key;
        $this->memcache_ttl = $ttl;
        return $this;
    }

    public function query($caller, $query, $binds = array())
    {
        return null;
    }

    public function retrieve($target,
                             array $cols,
                             array $where = null,
                             array $binds = array())
    {
        return null;
    }

    public function update($target,
                           array $data,
                           array $where = null,
                           array $binds = array())
    {
        return false;
    }

    // +-------------------+
    // | Protected Methods |
    // +-------------------+

    final protected function memcache_purge()
    {
        if (empty($this->memcache_key))
        {
            return false;
        }

        tiny_api_Memcache_Manager::get_instance()->purge($this->memcache_key);
        return $this;
    }

    final protected function memcache_retrieve()
    {
        if (empty($this->memcache_key))
        {
            return null;
        }

        return tiny_api_Memcache_Manager::get_instance()
                ->retrieve($this->memcache_key);
    }

    final protected function memcache_store($data)
    {
        if (empty($this->memcache_key))
        {
            return false;
        }

        tiny_api_Memcache_Manager::get_instance()
            ->store($this->memcache_key, $data, $this->memcache_ttl);

        return $this;
    }
}

//
// +--------------------------+
// | tiny_api_Base_Data_Store |
// +--------------------------+
//

class tiny_api_Base_Data_Store
{
    function __construct() {}
}

//
// +------------------------------+
// | tiny_api_Data_Store_Provider |
// +------------------------------+
//

class tiny_api_Data_Store_Provider
{
    private $tiny_api_conf;
    private $dsh;

    function __construct()
    {
        global $__tiny_api_conf__;
        $this->tiny_api_conf = $__tiny_api_conf__;
    }

    public static function make()
    {
        return new self();
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function get_data_store_handle()
    {
        if ($this->tiny_api_conf[ 'data store' ] == 'mysql (myisam)')
        {
            if (is_null($this->dsh))
            {
                require_once 'base/data-store/mysql-myisam.php';
                $this->dsh = new tiny_api_Data_Store_Mysql_Myisam();
            }

            return $this->dsh;
        }
        else if ($this->tiny_api_conf[ 'data store' ] == 'postgresql')
        {
            if (is_null($this->dsh))
            {
                require_once 'base/data-store/postgresql.php';
                $this->dsh = new tiny_api_Data_Store_Postgresql();
            }

            return $this->dsh;
        }
        else
        {
            error_log('Data store configured in tiny-api-conf.php ('
                      . $this->tiny_api_conf[ 'data store' ]
                      . ') is not presently supported');
            return null;
        }
    }
}
?>
