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
// +--------------------------------+
// | tiny_api_Data_Store_Postgresql |
// +--------------------------------+
//

class tiny_api_Data_Store_Postgresql
extends tiny_api_Base_Rdbms
{
    private $postgresql;
    private $db_name;

    function __construct()
    {
        parent::__construct();
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

        $this->connect();

        $keys  = array_keys($data);
        $binds = $this->get_binds($keys);
        $vals  = array_values($data);

        $query = "insert into $target ("
                 .    implode(', ', $keys)
                 . ') '
                 . 'values ('
                 .    implode(', ', $binds)
                 . ')'
                 . ($return_insert_id ? 'returning id' : '');
        $statement_name = sha1($query);

        if (is_null(($dsr = $this->prepare($statement_name, $query))))
        {
            return null;
        }

        if (($dsr =
                pg_execute($this->postgresql, $statement_name, $vals))
            === false)
        {
            error_log(pg_result_error($dsr));
            return null;
        }

        if ($return_insert_id)
        {
            $row = pg_fetch_assoc($dsr);
            pg_free_result($dsr);

            return $row[ 'id' ];
        }
        else
        {
            return '';
        }
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
        $statement_name = sha1($query);

        if (is_null(($dsr = $this->prepare($statement_name, $query))))
        {
            return false;
        }

        if (($dsr =
                pg_execute($this->postgresql, $statement_name, $binds))
            === false)
        {
            error_log(pg_result_error($dsr));
            return false;
        }

        return true;
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

        $this->connect();

        $query = 'select ' . implode(', ', $cols) . ' '
                 . "from $target";
        if (!is_null($where))
        {
            $query .= ' where ' . implode(' and ', $where);
        }

        $statement_name = sha1($query);

        if (is_null(($dsr = $this->prepare($statement_name, $query))))
        {
            return null;
        }

        if (($dsr =
                pg_execute($this->postgresql, $statement_name, $binds))
            === false)
        {
            error_log(pg_result_error($dsr));
            return null;
        }

        $results = $this->fetch_all_assoc($dsr);
        pg_free_result($dsr);

        return $results;
    }

    final public function select_db($name)
    {
        $this->db_name = $name;
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

        $this->connect();

        $query = "update $target "
                 .  'set '
                 . implode(', ', $data);
        if (!is_null($where))
        {
            $query .= ' where ' . implode(' and ', $where);
        }

        $statement_name = sha1($query);

        if (is_null(($dsr = $this->prepare($statement_name, $query))))
        {
            return false;
        }

        if (($dsr =
                pg_execute($this->postgresql, $statement_name, $binds))
            === false)
        {
            error_log(pg_result_error($dsr));
            return false;
        }

        return true;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function connect()
    {
        global $__tiny_api_conf__;

        if (!is_null($this->postgresql))
        {
            return $this->postgresql;
        }

        if (is_null($this->db_name))
        {
            error_log('cannot connect PostgreSQL because no database was '
                      . 'selected');
            return null;
        }

        if (($this->postgresql =
                pg_pconnect($__tiny_api_conf__[ 'postgresql connection string' ]
                            . ' dbname=' . $this->db_name))
            === false)
        {
            error_log(pg_last_error());
        }

        return $this->postgresql;
    }

    private function fetch_all_assoc($dsr)
    {
        $results = array();
        while (($result = pg_fetch_assoc($dsr)) !== false)
        {
            $results[] = $result;
        }

        return $results;
    }

    private function get_binds($keys)
    {
        if (empty($keys))
        {
            return array();
        }

        $num_keys = count($keys);
        $binds    = array();
        for ($i = 0; $i < $num_keys; $i++)
        {
            $binds[] = "\$" . ($i + 1);
        }

        return $binds;
    }

    private function prepare($statement_name, $query)
    {
        if (($dsr =
                @pg_prepare($this->postgresql, $statement_name, $query))
            === false)
        {
            $last_error = pg_last_error($this->postgresql);
            if (!preg_match('/already exists/', $last_error))
            {
                error_log(pg_result_error($dsr));
                return null;
            }
        }

        return $dsr;
    }
}
?>
