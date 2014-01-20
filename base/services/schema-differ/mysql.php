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
require_once 'base/services/table-builder/mysql.php';

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
    private $prefix_module_name_map;
    private $write_upgrade_scripts;
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

        $this->write_upgrade_scripts = true;
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

    final public function dont_write_upgrade_scripts()
    {
        $this->write_upgrade_scripts = false;
        return $this;
    }

    final public function execute()
    {
        $this->map_prefixes_to_module_name();

        $this->compute_ref_table_differences();
        $this->compute_table_differences();
        $this->compute_column_differences();

        $this->write_upgrade_scripts();

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

    private function get_column_terms($column_data)
    {
        $terms = array();

        if ($column_data[ 'is_nullable' ] == 'NO')
        {
            $terms[] = 'not null';
        }

        if ($column_data[ 'extra' ] == 'auto_increment')
        {
            $terms[] = 'auto_increment';
        }

        if ($column_data[ 'column_key' ] == 'UNI')
        {
            $terms[] = 'unique';
        }

        if (!empty($column_data[ 'character_set_name' ]))
        {
            $terms[] = 'character set '
                       . $column_data[ 'character_set_name' ];
        }

        if (!empty($column_data[ 'collation_name' ]))
        {
            $terms[] = 'collate '
                       . $column_data[ 'collation_name' ];
        }

        if (!empty($column_data[ 'column_default' ]))
        {
            $terms[] = 'default '
                       . (in_array($column_data[ 'column_default' ],
                                   array('current_timestamp')) ?
                            $column_data[ 'column_default' ] :
                            "'" . $column_data[ 'column_default' ] . "'");
        }

        return $terms;
    }

    private function map_prefixes_to_module_name()
    {
        $this->notice('Mapping module prefixes to names...');

        $this->prefix_module_name_map = array();

        $paths = explode(':', ini_get('include_path'));
        foreach ($paths as $path)
        {
            $command = "/usr/bin/find $path/ -type f -name build.php";
            $output  = null;
            exec($command, $output, $retval);

            if ($retval)
            {
                throw new tiny_api_Schema_Differ_Exception(
                            "failed to execute \"$command\": "
                            . print_r($output, true));
            }

            foreach ($output as $build_file)
            {
                if (!preg_match('#^(.*?)/sql/ddl/build.php#',
                                preg_replace("#^$path/?#", '', $build_file),
                                $matches))
                {
                    throw new tiny_api_Schema_Differ_Exception(
                                "could not get module name from build file "
                                . "include path \"$build_file\"");
                }
                $module_name = $matches[ 1 ];

                $contents = file_get_contents($build_file);
                if ($contents &&
                    preg_match('/function (.*)_build\\s?\(/msi',
                               $contents, $matches))
                {
                    $prefix = $matches[ 1 ];
                }

                if (!empty($module_name) && !empty($prefix))
                {
                    $this->notice("$prefix => $module_name", 1);

                    $this->prefix_module_name_map[ $prefix ] = $module_name;
                }
            }
        }
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

    private function write_add_modify_columns_sql()
    {
        $file = '20-columns.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->columns_to_create as $column_name)
        {
            list($table_name, $column_name) = explode('.', $column_name);

            $column = $this->source->query
            (
                __METHOD__,
                'select table_name,
                        column_name,
                        column_default,
                        is_nullable,
                        character_set_name,
                        collation_name,
                        column_type,
                        column_key,
                        extra
                   from information_schema.columns
                  where table_name = ?
                    and column_name = ?',
                array($table_name, $column_name)
            );

            ob_start();
?>
alter table <?= $column[ 0 ][ 'table_name' ] . "\n" ?>
        add <?= $column[ 0 ][ 'column_name' ] . "\n" ?>
            <?= $column[ 0 ][ 'column_type' ] ?>
<?
            $contents .= ob_get_clean() . "\n";

            $terms = $this->get_column_terms($column[ 0 ]);
            foreach ($terms as $index => $term)
            {
                $terms[ $index ] = "            $term";
            }
            $contents .= implode("\n", $terms) . ";\n\n";
        }

        foreach ($this->columns_to_modify as $column)
        {
            ob_start();
?>
alter table <?= $column[ 'table_name' ] . "\n" ?>
     modify <?= $column[ 'column_name' ] . "\n" ?>
            <?= $column[ 'column_type' ] ?>
<?
            $contents .= ob_get_clean() . "\n";

            $terms = $this->get_column_terms($column);
            foreach ($terms as $index => $term)
            {
                $terms[ $index ] = "            $term";
            }
            $contents .= implode("\n", $terms) . ";\n\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_add_ref_tables_sql()
    {
        $file = '10-ref_tables.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->ref_tables_to_create as $table_name)
        {
            $records = $this->source->query
            (
                __METHOD__,
                "select id,
                        value,
                        display_order
                   from " . $this->source_db_name . ".$table_name
                  order by id asc"
            );

            $ref_table = tiny_api_Ref_Table::make($this->source_db_name,
                                                  $table_name);
            foreach ($records as $record)
            {
                $ref_table->add($record[ 'id' ],
                                $record[ 'value' ],
                                $record[ 'display_order' ]);
            }

            $contents .= $ref_table->get_definition() . "\n\n";
            foreach ($ref_table->get_insert_statements() as $statement)
            {
                $contents .= "$statement\n\n";
            }
        }

        file_put_contents($file, $contents);
    }

    private function write_add_tables_sql()
    {
        $file = '20-tables.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->tables_to_create as $table_name)
        {
            $table_terms = array();

            $table_definition = $this->source->query
            (
                __METHOD__,
                'select engine,
                        table_collation
                   from tables
                  where table_name = ?',
                array($table_name)
            );

            if (array_key_exists(0, $table_definition))
            {
                $table_terms[] =
                    'engine = '
                    . strtolower($table_definition[ 0 ][ 'engine' ]);
                $table_terms[] =
                    'default charset = utf8';

                if (!empty($table_definition[ 0 ][ 'table_collation' ]))
                {
                    $table_terms[] =
                        'collate = '
                        . $table_definition[ 0 ][ 'table_collation' ];
                }
            }

            $columns = array();

            $data = $this->source->query
            (
                __METHOD__,
                'select table_name,
                        column_name,
                        column_default,
                        is_nullable,
                        character_set_name,
                        collation_name,
                        column_type,
                        column_key,
                        extra
                   from information_schema.columns
                  where table_name = ?
                  order by ordinal_position asc',
                array($table_name)
            );

            foreach ($data as $column)
            {
                $terms = $this->get_column_terms($column);

                $columns[] = '    '
                             . $column[ 'column_name' ]
                             . ' '
                             . $column[ 'column_type' ]
                             . (!empty($terms) ?
                                ' ' . implode(' ', $terms) : '');
            }

            $data = $this->source->query
            (
                __METHOD__,
                "show keys
                      from " . $this->source_db_name . ".$table_name
                where key_name = 'PRIMARY'"
            );

            $primary_key = '';

            $pk_cols = array();
            foreach ($data as $column)
            {
                $pk_cols[] = $column[ 'Column_name' ];
            }

            if (!empty($pk_cols))
            {
                $primary_key =
                    "alter table $table_name\n"
                    . "        add constraint $table_name" . "_pk\n"
                    . '    primary key (' . implode(', ', $pk_cols) . ');';
            }

            ob_start();
?>
create table <?= $table_name . "\n" ?>
(
<?= implode(",\n", $columns) . "\n" ?>
)<?= !empty($table_terms) ? ' ' . implode(' ', $table_terms) : '' ?>;

<?= !empty($primary_key) ? "$primary_key\n" : '' ?>
<?
            $contents .= ob_get_clean();
        }

        file_put_contents($file, $contents);
    }

    private function write_drop_ref_tables_sql()
    {
        $file = '80-ref_tables.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->ref_tables_to_drop as $table_name)
        {
            $contents .= "drop table if exists $table_name;\n\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_upgrade_scripts()
    {
        if (!$this->write_upgrade_scripts)
        {
            return;
        }

        $this->notice('Writing upgrade scripts into current directory...');

        $this->write_add_ref_tables_sql();
        $this->write_add_tables_sql();
        $this->write_add_modify_columns_sql();
        $this->write_drop_ref_tables_sql();
    }
}
?>
