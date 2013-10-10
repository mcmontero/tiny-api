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

        /**
         * By default the server, username and password will be extracted
         * from the php.ini using the following configuration settings:
         *
         *  mysql.default_host
         *  mysql.default_user
         *  mysql.default_password
         */
        $this->mysql = mysql_pconnect();
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function create($target, array $data)
    {
        if (empty($data))
        {
            return null;
        }

        $keys = array_keys($data);
        $vals = array_values($data);

        $query = "insert into $target ("
                  .     implode(', ', $keys)
                  . ') '
                  . 'values ('
                  .    implode(', ', $this->escape_values($vals))
                  . ')';
        if (mysql_query($query, $this->mysql) === false)
        {
            error_log(mysql_error($this->mysql));
            return null;
        }

        return mysql_insert_id($this->mysql);
    }

    final public function delete($target, array $where)
    {
        if (empty($where))
        {
            return false;
        }

        $query = "delete from $target "
                 . 'where ' . implode(' and ', $where);
        if (mysql_query($query, $this->mysql) === false)
        {
            error_log(mysql_error($this->mysql));
            return false;
        }

        return true;
    }

    final public function retrieve($target, array $cols, array $where = null)
    {
        if (empty($cols))
        {
            return null;
        }

        $query = 'select ' . implode(', ', $cols) . ' '
                 . "from $target";
        if (!is_null($where))
        {
            $query .= ' where ' . implode(' and ', $where);
        }

        if (($dsr = mysql_query($query, $this->mysql)) === false)
        {
            error_log(mysql_error($this->mysql));
            return null;
        }

        $results = $this->fetch_all_assoc($dsr);
        mysql_free_result($dsr);

        return $results;
    }

    final public function select_db($name)
    {
        if (mysql_select_db($name, $this->mysql) === false)
        {
            error_log(mysql_error($this->mysql));
            return null;
        }

        return $this;
    }

    final public function update($target, array $data, array $where = null)
    {
        if (empty($data))
        {
            return false;
        }

        $set = array();
        foreach ($data as $key => $value)
        {
            $set[] = "$key = " . $this->escape_value($value);
        }

        $query = "update $target "
                 .  'set ' . implode(', ', $set);
        if (!is_null($where))
        {
            $query .= 'where ' . implode(' and ', $where);
        }

        if (mysql_query($query, $this->mysql) === false)
        {
            error_log(mysql_error($this->mysql));
            return false;
        }

        return true;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function escape_value($value)
    {
        return current($this->escape_values(array($value)));
    }

    private function escape_values(array $values)
    {
        $num_values = count($values);
        for ($i = 0; $i < $num_values; $i++)
        {
            $values[ $i ] = '\''
                            . mysql_real_escape_string($values[ $i ])
                            . '\'';
        }

        return $values;
    }

    private function fetch_all_assoc($dsr)
    {
        $results = array();
        while(($result = mysql_fetch_assoc($dsr)) !== false)
        {
            $results[] = $result;
        }

        return $results;
    }
}
?>
