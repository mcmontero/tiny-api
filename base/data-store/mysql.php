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
// | tiny_api_Data_Store_Mysql |
// +---------------------------+
//

class tiny_api_Data_Store_Mysql
extends tiny_api_Base_Rdbms
{
    private $mysql;

    function __construct()
    {
        parent::__construct();
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function begin_transaction()
    {
        $this->connect();

        $this->mysql->autocommit(false);
    }

    final public function commit()
    {
        if (is_null($this->mysql))
        {
            throw new tiny_Api_Data_Store_Exception(
                        'transaction cannot be committed because a database '
                        . 'connection has not been established yet');
        }

        if (!$this->mysql->commit())
        {
            throw new tiny_Api_Data_Store_Exception(
                        'transaction commit failed');
        }

        $this->mysql->autocommit(true);
    }

    final public function create($target, array $data, $return_insert_id = true)
    {
        if (empty($data))
        {
            return null;
        }

        $this->connect();

        $keys  = array_keys($data);
        $binds = $this->get_binds($data);
        $vals  = array_values($data);

        $query = "insert into $target ("
                  .    implode(', ', $keys)
                  . ') '
                  . 'values ('
                  .    implode(', ', $binds)
                  . ')';

        if (($dss = $this->mysql->prepare($query)) === false)
        {
            throw new tiny_Api_Data_Store_Exception(
                        "execution of this query:\n\n$query\n\nproduced this "
                        . "error:\n\n" .  $this->mysql->error);
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

        $this->connect();

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

        $this->connect();

        $is_select = true;
        if (!preg_match('/^\(?select /i', $query) &&
            !preg_match('/^show /i', $query))
        {
            $is_select = false;
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

        if ($is_select)
        {
            $results = $this->fetch_all_assoc($dss);

            $dss->free_result();
            $this->memcache_store($results);

            return $results;
        }
        else
        {
            return true;
        }
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

        $this->connect();

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

    final public function rollback()
    {
        $this->mysql->rollback();
        $this->mysql->autocommit(true);
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

        $this->connect();

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
            if ($this->param_is_bindable($binds[ $i ]))
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
        }

        if (count($vals) > 0)
        {
            // There might not be any values to bind because something like
            // current_timestamp was the only value passed to the query.
            call_user_func_array(array($dss, 'bind_param'),
                                 array_merge(array($types), $vals));
        }

        return true;
    }

    private function connect()
    {
        global $__tiny_api_conf__;

        if (!is_null($this->mysql))
        {
            return $this->mysql;
        }

        if (is_null($this->connection_name))
        {
            throw new tiny_Api_Data_Store_Exception(
                        'cannot connect to MySQL because no connection name '
                        . 'was selected');
        }

        if (!array_key_exists($this->connection_name,
                              $__tiny_api_conf__[ 'mysql connection data' ]))
        {
            throw new tiny_Api_Data_Store_Exception(
                        'the MySQL connection name you provided is invalid');
        }

        if (is_null($this->db_name))
        {
            throw new tiny_Api_Data_Store_Exception(
                        'cannot connect to MySQL because no database name was '
                        . 'selected');
        }

        list($host, $user, $pw) =
            $__tiny_api_conf__[ 'mysql connection data' ]
                              [ $this->connection_name ];

        $this->mysql = new mysqli($host, $user, $pw);
        if ($this->mysql->connect_error)
        {
            throw new tiny_Api_Data_Store_Exception(
                        $this->mysql->connect_error);
        }

        if ($this->mysql->select_db($this->db_name) === false)
        {
            throw new tiny_Api_Data_Store_Exception(
                        'could not select DB '
                        . "\"" . $this->db_name . "\"");
        }

        if (!empty($this->charset))
        {
            $this->mysql->set_charset($this->charset);
        }

        return $this->mysql;
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
            throw new tiny_Api_Data_Store_Exception($this->mysql->error);
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

    private function get_binds(array $data)
    {
        if (empty($data))
        {
            return array();
        }

        $binds = array();
        foreach ($data as $column => $value)
        {
            if (!$this->param_is_bindable($value))
            {
                $binds[] = $value;
            }
            else
            {
                $binds[] = '?';
            }
        }

        return $binds;
    }

    private function param_is_bindable($value)
    {
        if ($value == 'current_timestamp')
        {
            return false;
        }

        return true;
    }
}
?>
