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

require_once 'base/data-store/mysql.php';

// +------------------------------------------------------------+
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +------------------------------+
// | tiny_api_Mysql_Schema_Differ |
// +------------------------------+
//

class tiny_api_Mysql_Schema_Differ
{
    private $cli;
    private $source;
    private $target;
    private $source_db_name;
    private $target_db_name;
    private $ref_tables_to_create;
    private $ref_tables_to_drop;
    private $tables_to_create;
    private $tables_to_drop;
    private $table_create_drop_list;
    private $columns_to_create;
    private $columns_to_drop;
    private $columns_to_modify;

    function __construct($source_connection_name,
                         $source_db_name,
                         $target_connection_name,
                         $target_db_name)
    {
        $this->source = new tiny_api_Data_Store_Mysql();
        $this->source->select_db($source_connection_name, 'information_schema');
        $this->source_db_name = $source_db_name;

        $this->target = new tiny_api_Data_Store_Mysql();
        $this->target->select_db($target_connection_name, 'information_schema');
        $this->target_db_name = $target_db_name;
    }

    static function make($source_connection_name,
                         $source_db_name,
                         $target_connection_name,
                         $target_db_name)
    {
        return new self($source_connection_name,
                        $source_db_name,
                        $target_connection_name,
                        $target_db_name);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function execute()
    {
        $this->compute_ref_table_differences();
        $this->compute_table_differences();
        $this->compute_column_differences();

        return $this;
    }

    final public function get_columns_to_create()
    {
        return $this->columns_to_create;
    }

    final public function get_columns_to_drop()
    {
        return $this->columns_to_drop;
    }

    final public function get_columns_to_modify()
    {
        return $this->columns_to_modify;
    }

    final public function get_ref_tables_to_create()
    {
        return $this->ref_tables_to_create;
    }

    final public function get_ref_tables_to_drop()
    {
        return $this->ref_tables_to_drop;
    }

    final public function get_tables_to_create()
    {
        return $this->tables_to_create;
    }

    final public function get_tables_to_drop()
    {
        return $this->tables_to_drop;
    }

    final public function set_cli(tiny_api_Cli $cli)
    {
        $this->cli = $cli;
        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function compute_column_differences()
    {
        $this->notice('Computing column differences...');

        $query = "select table_name,
                         column_name,
                         column_default,
                         is_nullable,
                         character_set_name,
                         collation_name,
                         column_type,
                         column_key,
                         extra
                    from columns
                   where table_schema = ?
                     and table_name not like '%\_ref\_%'
                     and table_name not in ("
                         . $this->table_create_drop_list
                         . ")";

        $source_columns =
            $this->query_source(
                __METHOD__,
                $query,
                array($this->source_db_name));
        $target_columns =
            $this->query_target(
                __METHOD__,
                $query,
                array($this->target_db_name));

        $source_names = array();
        $source       = array();
        foreach ($source_columns as $source_column)
        {
            $name = $source_column[ 'table_name' ]
                    . '.'
                    . $source_column[ 'column_name' ];

            $source_names[]  = $name;
            $source[ $name ] = $source_column;
        }

        $target_names = array();
        $target       = array();
        foreach ($target_columns as $target_column)
        {
            $name = $target_column[ 'table_name' ]
                    . '.'
                    . $target_column[ 'column_name' ];

            $target_names[]  = $name;
            $target[ $name ] = $target_column;
        }

        $this->columns_to_create =
            array_values(array_diff($source_names, $target_names));
        foreach ($this->columns_to_create as $column_to_create)
        {
            $this->notice("(+) $column_to_create", 1);
        }

        $this->columns_to_drop =
            array_values(array_diff($target_names, $source_names));
        foreach ($this->columns_to_drop as $column_to_drop)
        {
            $this->notice("(-) $column_to_drop", 1);
        }

        $this->columns_to_modify = array();
        foreach ($source as $column_name => $column_data)
        {
            if (in_array($column_name, $this->columns_to_create) ||
                in_array($column_name, $this->columns_to_drop))
            {
                continue;
            }

            if (!array_key_exists($column_name, $target))
            {
                throw new tiny_api_Schema_Differ_Exception(
                            "could not find column \"$column_name\" in the "
                            . "list of target columns");
            }

            foreach ($column_data as $key => $value)
            {
                if ($target[ $column_name ][ $key ] != $value)
                {
                    $this->notice("(=) $column_name ($key)", 1);

                    $this->columns_to_modify[ $column_name ] = $column_data;
                    break;
                }
            }
        }
    }

    private function compute_ref_table_differences()
    {
        $this->notice('Computing reference table differences...');

        $query = 'select table_name
                    from tables
                   where table_schema = ?
                     and table_name like \'%\_ref\_%\'';

        $source_tables =
            $this->flatten_tables(
                $this->query_source(
                    __METHOD__,
                    $query,
                    array($this->source_db_name)));
        $target_tables =
            $this->flatten_tables(
                $this->query_source(
                    __METHOD__,
                    $query,
                    array($this->target_db_name)));

        $this->ref_tables_to_create =
            array_values(array_diff($source_tables, $target_tables));
        foreach ($this->ref_tables_to_create as $table)
        {
            $this->notice("(+) $table", 1);
        }

        $this->ref_tables_to_drop =
            array_values(array_diff($target_tables, $source_tables));
        foreach ($this->ref_tables_to_drop as $table)
        {
            $this->notice("(-) $table", 1);
        }
    }

    private function compute_table_differences()
    {
        $this->notice('Computing table differences...');

        $create_drop_list = array();

        $query = 'select table_name
                    from tables
                   where table_schema = ?
                     and table_name not like \'%\_ref\_%\'';

        $source_tables =
            $this->flatten_tables(
                $this->query_source(
                    __METHOD__,
                    $query,
                    array($this->source_db_name)));
        $target_tables =
            $this->flatten_tables(
                $this->query_target(
                    __METHOD__,
                    $query,
                    array($this->target_db_name)));

        $this->tables_to_create =
            array_values(array_diff($source_tables, $target_tables));
        foreach ($this->tables_to_create as $table)
        {
            $this->notice("(+) $table", 1);

            $create_drop_list[] = "'" . $table . "'";
        }

        $this->tables_to_drop =
            array_values(array_diff($target_tables, $source_tables));
        foreach ($this->tables_to_drop as $table)
        {
            $this->notice("(-) $table", 1);

            $create_drop_list[] = "'" . $table . "'";
        }

        $this->table_create_drop_list = implode(', ', $create_drop_list);
    }

    private function error($message, $indent = null)
    {
        if (is_null($this->cli))
        {
            return;
        }

        $this->cli->error($message, $indent);
    }

    private function flatten_tables($tables)
    {
        $results = array();
        foreach ($tables as $table)
        {
            $results[] = $table[ 'table_name' ];
        }

        return $results;
    }

    private function notice($message, $indent = null)
    {
        if (is_null($this->cli))
        {
            return;
        }

        $this->cli->notice($message, $indent);
    }

    private function query_source($caller, $query, array $binds)
    {
        return $this->source->query($caller, $query, $binds);
    }

    private function query_target($caller, $query, array $binds)
    {
        return $this->target->query($caller, $query, $binds);
    }

    private function table_is_being_created($table_name)
    {
        return in_array($table_name, $this->tables_to_create);
    }

    private function table_is_being_dropped($table_name)
    {
        return in_array($table_name, $this->tables_to_drop);
    }

    private function warn($message, $indent = null)
    {
        if (is_null($this->cli))
        {
            return;
        }

        $this->cli->error($message, $indent);
    }
}
?>
