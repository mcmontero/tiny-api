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
// +----------------------------------+
// | tiny_api_Data_Store_Mysql_Myisam |
// +----------------------------------+
//

class tiny_api_Data_Store_Mysql_Myisam
extends tiny_api_Base_Rdbms
{
    private $mysql;

    function __construct()
    {
        parent::__construct();

        $this->mysql = new mysqli(ini_get("mysqli.default_host"),
                                  ini_get("mysqli.default_user"),
                                  ini_get("mysqli.default_pw"));
        if ($this->mysql->connect_error)
        {
            throw new tiny_Api_Data_Store_Exception(
                        $this->mysql->connect_error);
        }
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function create($target, array $data, $return_insert_id = true)
    {
        if (empty($data))
        {
            return null;
        }

        $keys  = array_keys($data);
        $binds = $this->get_binds($keys);
        $vals  = array_values($data);

        $query = "insert into $target ("
                  .    implode(', ', $keys)
                  . ') '
                  . 'values ('
                  .    implode(', ', $binds)
                  . ')';

        if (($dss = $this->mysql->prepare($query)) === false)
        {
            throw new tiny_Api_Data_Store_Exception($this->mysql->error);
        }

        $this->bind($dss, $vals);

        if ($dss->execute() === false)
        {
            throw new tiny_Api_Data_Store_Exception($dss->error);
        }

        $dss->free_result();

        return ($return_insert_id ? $this->mysql->insert_id : '');
    }

    final public function delete($target, array $where, array $binds = array())
    {
        if (empty($where))
        {
            return false;
        }

        $query = "delete from $target "
                 . 'where ' . implode(' and ', $where);

        if (($dss = $this->mysql->prepare($query)) === false)
        {
            throw new tiny_Api_Data_Store_Exception($this->mysql->error);
        }

        $this->bind($dss, $binds);

        if ($dss->execute() === false)
        {
            throw new tiny_Api_Data_Store_Exception($this->error);
        }

        $dss->free_result();
        $this->memcache_purge();

        return true;
    }

    final public function query($caller, $query, $binds = array())
    {
        if (!is_null(($results_from_cache = $this->memcache_retrieve())))
        {
            return $results_from_cache;
        }

        $query = "/* $caller */ $query";
        if (($dss = $this->mysql->prepare($query)) === false)
        {
            throw new tiny_Api_Data_Store_Exception($this->mysql->error);
        }

        $this->bind($dss, $binds);

        if ($dss->execute() === false)
        {
            throw new tiny_Api_Data_Store_Exception($dss->error);
        }

        $results = $this->fetch_all_assoc($dss);
        $dss->free_result();

        $this->memcache_store($results);

        return $results;
    }

    final public function retrieve($target,
                                   array $cols,
                                   array $where = null,
                                   array $binds = array())
    {
        if (empty($cols))
        {
            return null;
        }

        if (!is_null(($results_from_cache = $this->memcache_retrieve())))
        {
            return $results_from_cache;
        }

        $query = 'select ' . implode(', ', $cols) . ' '
                 . "from $target";
        if (!is_null($where))
        {
            $query .= ' where ' . implode(' and ', $where);
        }

        if (($dss = $this->mysql->prepare($query)) === false)
        {
            throw new tiny_Api_Data_Store_Exception($this->mysql->error);
        }

        $this->bind($dss, $binds);

        if ($dss->execute() === false)
        {
            throw new tiny_Api_Data_Store_Exception($dss->error);
        }

        $results = $this->fetch_all_assoc($dss);
        $dss->free_result();

        $this->memcache_store($results);

        return $results;
    }

    final public function select_db($name)
    {
        if ($this->mysql->select_db($name) === false)
        {
            throw new tiny_Api_Data_Store_Exception(
                        "could not select DB \"$name\"");
        }

        return $this;
    }

    final public function update($target,
                                 array $data,
                                 array $where = null,
                                 array $binds = array())
    {
        if (empty($data))
        {
            return false;
        }

        $query = "update $target "
                 .  'set '
                 . implode(', ', $data);
        if (!is_null($where))
        {
            $query .= ' where ' . implode(' and ', $where);
        }

        if (($dss = $this->mysql->prepare($query)) === false)
        {
            throw new tiny_Api_Data_Store_Exception($this->mysql->error);
        }

        $this->bind($dss, $binds);

        if ($dss->execute() === false)
        {
            throw new tiny_Api_Data_Store_Exception($dss->error);
        }

        $dss->free_result();
        $this->memcache_purge();

        return true;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function bind($dss, array $binds)
    {
        if (empty($binds))
        {
            return false;
        }

        $num_binds = count($binds);
        $types     = '';
        $vals      = array();
        for ($i = 0; $i < $num_binds; $i++)
        {
            if (is_string($binds[ $i ]))
            {
                $types .= 's';
            }
            else if (is_int($binds[ $i ]))
            {
                $types .= 'i';
            }
            else if (is_float($binds[ $i ]))
            {
                $types .= 'd';
            }
            else
            {
                $types .= 's';
            }

            $vals[] = &$binds[ $i ];
        }

        call_user_func_array(array($dss, 'bind_param'),
                             array_merge(array($types), $vals));

        return true;
    }

    private function fetch_all_assoc($dss)
    {
        if ($dss->store_result() === false)
        {
            throw new tiny_Api_Data_Store_Exception($dss->error);
        }

        $vars = array();
        $data = array();

        if (($meta = $dss->result_metadata()) === false)
        {
            throw new tiny_Api_Data_Store_Exception($dss->error);
        }

        while (($field = $meta->fetch_field()) !== false)
        {
            $vars[] = &$data[ $field->name ];
        }

        call_user_func_array(array($dss, 'bind_result'), $vars);

        $results = array();
        $i       = 0;
        while ($dss->fetch() === true)
        {
            $results[ $i ] = array();

            foreach ($data as $key => $val)
            {
                $results[ $i ][ $key ] = $val;
            }

            $i++;
        }

        return $results;
    }

    private function get_binds($keys)
    {
        if (empty($keys))
        {
            return array();
        }

        $binds = array();
        foreach ($keys as $junk)
        {
            $binds[] = '?';
        }

        return $binds;
    }
}
?>
