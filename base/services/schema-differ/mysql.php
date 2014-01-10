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
    }

    final public function set_cli(tiny_api_Cli $cli)
    {
        $this->cli = $cli;
        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

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

        $this->ref_tables_to_create = array_diff($source_tables,
                                                 $target_tables);
        foreach ($this->ref_tables_to_create as $table)
        {
            $this->notice("(+) $table", 1);
        }

        $this->ref_tables_to_drop = array_diff($target_tables,
                                               $source_tables);
        foreach ($this->ref_tables_to_drop as $table)
        {
            $this->notice("(-) $table", 1);
        }
    }

    private function compute_table_differences()
    {
        $this->notice('Computing table differences...');

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

        $this->tables_to_create = array_diff($source_tables, $target_tables);
        foreach ($this->tables_to_create as $table)
        {
            $this->notice("(+) $table", 1);
        }

        $this->tables_to_drop = array_diff($target_tables, $source_tables);
        foreach ($this->tables_to_drop as $table)
        {
            $this->notice("(-) $table", 1);
        }
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
