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
    private $write_upgrade_scripts;
    private $ref_tables_to_create;
    private $ref_tables_to_drop;
    private $tables_to_create;
    private $tables_to_drop;
    private $table_create_drop_list;
    private $ref_table_drop_list;
    private $columns_to_create;
    private $columns_to_drop;
    private $columns_to_modify;
    private $column_uniqueness_to_drop;
    private $foreign_keys_to_create;
    private $foreign_keys_to_drop;
    private $ref_data_to_add;
    private $ref_data_to_remove;
    private $ref_data_to_modify;
    private $indexes_to_create;
    private $indexes_to_drop;
    private $unique_keys_to_create;
    private $unique_keys_to_drop;

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
        $this->verify_schemas();

        $this->compute_ref_table_differences();
        $this->compute_table_differences();
        $this->compute_column_differences();
        $this->compute_foreign_key_differences();
        $this->compute_ref_table_data_differences();
        $this->compute_index_differences();
        $this->compute_unique_key_differences();

        if (!$this->there_are_differences())
        {
            $this->notice("Both schemas are the same!");
            exit(0);
        }

        $this->write_upgrade_scripts();

        return $this;
    }

    final public function get_column_uniqueness_to_drop()
    {
        return $this->column_uniqueness_to_drop;
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

    final public function get_foreign_keys_to_create()
    {
        return $this->foreign_keys_to_create;
    }

    final public function get_foreign_keys_to_drop()
    {
        return $this->foreign_keys_to_drop;
    }

    final public function get_indexes_to_create()
    {
        return $this->indexes_to_create;
    }

    final public function get_indexes_to_drop()
    {
        return $this->indexes_to_drop;
    }

    final public function get_ref_data_to_add()
    {
        return $this->ref_data_to_add;
    }

    final public function get_ref_data_to_modify()
    {
        return $this->ref_data_to_modify;
    }

    final public function get_ref_data_to_remove()
    {
        return $this->ref_data_to_remove;
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

    final public function get_unique_keys_to_create()
    {
        return $this->unique_keys_to_create;
    }

    final public function get_unique_keys_to_drop()
    {
        return $this->unique_keys_to_drop;
    }

    final public function set_cli(tiny_api_Cli $cli)
    {
        $this->cli = $cli;
        return $this;
    }

    final public function there_are_differences()
    {
        return !empty($this->ref_tables_to_create)      ||
               !empty($this->ref_tables_to_drop)        ||
               !empty($this->tables_to_create)          ||
               !empty($this->tables_to_drop)            ||
               !empty($this->columns_to_create)         ||
               !empty($this->columns_to_drop)           ||
               !empty($this->columns_to_modify)         ||
               !empty($this->column_uniqueness_to_drop) ||
               !empty($this->foreign_keys_to_create)    ||
               !empty($this->foreign_keys_to_drop)      ||
               !empty($this->ref_data_to_add)           ||
               !empty($this->ref_data_to_remove)        ||
               !empty($this->ref_data_to_modify)        ||
               !empty($this->indexes_to_create)         ||
               !empty($this->indexes_to_drop)           ||
               !empty($this->unique_keys_to_create)     ||
               !empty($this->unique_keys_to_drop);
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
                     and table_name not like '%\_ref\_%'";
        if (!empty($this->table_create_drop_list))
        {
            $query .= 'and table_name not in ('
                      . $this->table_create_drop_list
                      . ')';
        }

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

        $this->columns_to_modify         = array();
        $this->column_uniqueness_to_drop = array();
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
                $remove_uniqueness = false;

                if ($key   == 'column_key' &&
                    $value != 'UNI'        &&
                    $target[ $column_name ][ $key ] == 'UNI')
                {
                    // This is special case handling when the unique key is
                    // being removed from a column.  MySQL requires you to
                    // drop the underlying unique index as opposed to modiyfing
                    // the uniqueness off of the table.
                    $this->notice("(-) $column_name (uniqueness)", 1);

                    $this->column_uniqueness_to_drop[] = $column_name;

                    $remove_uniqueness = true;
                }

                if ($target[ $column_name ][ $key ] != $value)
                {
                    if ($key == 'column_key' && $remove_uniqueness)
                    {
                        // We've handled this above in the special case for
                        // removing uniqueness.  See the comments there.
                        // There is nothing to do here since the necessary
                        // changes were recorded above.
                    }
                    else
                    {
                        $this->notice("(=) $column_name ($key)", 1);

                        $this->columns_to_modify[ $column_name ] = $column_data;
                        break;
                    }
                }
            }
        }
    }

    private function compute_index_differences()
    {
        $this->notice('Computing index differences...');

        $query = "select table_name,
                         index_name,
                         seq_in_index,
                         column_name
                    from statistics
                   where index_schema = ?
                     and index_name like '%\_idx'";
        if (!empty($this->table_create_drop_list))
        {
            $query .= ' and table_name not in ('
                      . $this->table_create_drop_list
                      . ')';
        }

        $source_indexes =
            $this->query_source(
                __METHOD__,
                $query,
                array($this->source_db_name));
        $target_indexes =
            $this->query_target(
                __METHOD__,
                $query,
                array($this->target_db_name));

        $source_names = array();
        $source       = array();
        foreach ($source_indexes as $index)
        {
            $source_names[ $index[ 'index_name' ] ] = true;

            if (!array_key_exists($index[ 'index_name' ], $source))
            {
                $source[ $index[ 'index_name' ] ] = array
                (
                    'table_name' => $index[ 'table_name' ],
                    'cols'       => array()
                );
            }

            $source[ $index[ 'index_name' ] ]
                   [ 'cols' ]
                   [ intval($index[ 'seq_in_index' ]) ] =
                    $index[ 'column_name' ];
        }

        $target_names = array();
        $target       = array();
        foreach ($target_indexes as $index)
        {
            $target_names[ $index[ 'index_name' ] ] = true;

            if (!array_key_exists($index[ 'index_name' ], $target))
            {
                $target[ $index[ 'index_name' ] ] = array
                (
                    'table_name' => $index[ 'table_name' ],
                    'cols'       => array()
                );
            }

            $target[ $index[ 'index_name' ] ]
                   [ 'cols' ]
                   [ intval($index[ 'seq_in_index' ]) ] =
                    $index[ 'column_name' ];
        }

        $indexes_to_create =
            array_diff(array_keys($source_names),
                       array_keys($target_names));
        $indexes_to_drop =
            array_diff(array_keys($target_names),
                       array_keys($source_names));
        $indexes_to_modify =
            array();

        foreach ($source as $index_name => $data)
        {
            if (array_key_exists($index_name, $target) &&
                implode(',', $data[ 'cols' ]) !=
                implode(',', $target[ $index_name ][ 'cols' ]))
            {
                $indexes_to_modify[] = $index_name;
            }
        }

        $this->indexes_to_create = array();
        foreach ($indexes_to_create as $index_name)
        {
            $this->notice("(+) $index_name", 1);

            $this->indexes_to_create[] = array
            (
                'table_name' => $source[ $index_name ][ 'table_name' ],
                'index_name' => $index_name,
                'cols'       => $source[ $index_name ][ 'cols' ]
            );
        }

        $this->indexes_to_drop = array();
        foreach ($indexes_to_drop as $index_name)
        {
            $this->notice("(-) $index_name", 1);

            $this->indexes_to_drop[] = array
            (
                'table_name' => $target[ $index_name ][ 'table_name' ],
                'index_name' => $index_name,
                'cols'       => $target[ $index_name ][ 'cols' ]
            );
        }

        foreach ($indexes_to_modify as $index_name)
        {
            $this->notice("(=) $index_name", 1);

            $this->indexes_to_create[] = array
            (
                'table_name' => $source[ $index_name ][ 'table_name' ],
                'index_name' => $index_name,
                'cols'       => $source[ $index_name ][ 'cols' ]
            );

            $this->indexes_to_drop[] = array
            (
                'table_name' => $target[ $index_name ][ 'table_name' ],
                'index_name' => $index_name,
                'cols'       => $target[ $index_name ][ 'cols' ]
            );
        }
    }

    private function compute_foreign_key_differences()
    {
        $this->notice('Computing foreign key differences...');

        $query = "select k.table_name,
                         k.column_name,
                         k.constraint_name,
                         k.ordinal_position,
                         k.referenced_table_name,
                         k.referenced_column_name,
                         c.delete_rule
                    from key_column_usage k
                    left outer join referential_constraints c
                      on c.constraint_schema = k.constraint_schema
                     and c.constraint_name = k.constraint_name
                   where k.constraint_schema = ?
                     and k.constraint_name like '%\_fk'";
        if (!empty($this->table_create_drop_list))
        {
            $query .= "and k.table_name not in ("
                         . $this->table_create_drop_list
                         . ")";
        }

        $source_fks =
            $this->process_fks(
                $this->query_source(
                    __METHOD__,
                    $query,
                    array($this->source_db_name)));
        $target_fks =
            $this->process_fks(
                $this->query_target(
                    __METHOD__,
                    $query,
                    array($this->target_db_name)));

        $source_fk_names = array_keys($source_fks);
        $target_fk_names = array_keys($target_fks);

        $foreign_keys_to_create =
            array_diff($source_fk_names, $target_fk_names);
        $foreign_keys_to_drop =
            array_diff($target_fk_names, $source_fk_names);

        $this->foreign_keys_to_create = array();
        foreach ($foreign_keys_to_create as $fk_name)
        {
            $this->notice("(+) $fk_name", 1);

            $this->foreign_keys_to_create[] = $source_fks[ $fk_name ];
        }

        $this->foreign_keys_to_drop = array();
        foreach ($foreign_keys_to_drop as $fk_name)
        {
            $this->notice("(-) $fk_name", 1);

            $this->foreign_keys_to_drop[] = $target_fks[ $fk_name ];
        }

        foreach ($source_fks as $constraint_name => $fk)
        {
            if (array_key_exists($constraint_name, $target_fks) &&
                !in_array($constraint_name, $this->foreign_keys_to_create) &&
                !in_array($constraint_name, $this->foreign_keys_to_drop))
            {
                if ($source_fks[ $constraint_name ][ 'table_name' ]         !=
                    $target_fks[ $constraint_name ][ 'table_name' ]         ||
                    $source_fks[ $constraint_name ][ 'ref_table_name' ]     !=
                    $target_fks[ $constraint_name ][ 'ref_table_name' ]     ||
                    $source_fks[ $constraint_name ][ 'delete_rule' ]        !=
                    $target_fks[ $constraint_name ][ 'delete_rule' ]        ||
                    implode(
                        ',', $source_fks[ $constraint_name ][ 'cols' ])     !=
                    implode(
                        ',', $target_fks[ $constraint_name ][ 'cols' ])     ||
                    implode(
                        ',', $source_fks[ $constraint_name ][ 'ref_cols' ]) !=
                    implode(
                        ',', $target_fks[ $constraint_name ][ 'ref_cols' ]))
                {
                    $this->notice("(=) $constraint_name", 1);

                    $this->foreign_keys_to_drop[] =
                        $source_fks[ $constraint_name ];
                    $this->foreign_keys_to_create[] =
                        $source_fks[ $constraint_name ];
                }
            }
        }
    }

    private function compute_ref_table_data_differences()
    {
        $this->notice('Computing reference table data differences...');

        $query = "select table_name
                    from tables
                   where table_schema = ?
                     and table_name like '%\_ref\_%'";
        if (!empty($this->ref_table_drop_list))
        {
            $query .= ' and table_name not in ('
                      . $this->ref_table_drop_list
                      . ')';
        }

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

        $source_data = array();
        foreach ($source_tables as $table_name)
        {
            $records = $this->query_source
            (
                __METHOD__,
                'select id,
                        value,
                        display_order
                   from ' . $this->source_db_name . ".$table_name
                  order by id asc",
                  array()
            );

            $source_data[ $table_name ] = array();

            foreach ($records as $record)
            {
                $source_data[ $table_name ][ $record[ 'id' ] ] = array
                (
                    $record[ 'value' ],
                    $record[ 'display_order' ]
                );
            }
        }

        $target_data = array();
        foreach ($target_tables as $table_name)
        {
            $records = $this->query_target
            (
                __METHOD__,
                'select id,
                        value,
                        display_order
                   from ' . $this->target_db_name . ".$table_name
                  order by id asc",
                array()
            );

            $target_data[ $table_name ] = array();

            foreach ($records as $record)
            {
                $target_data[ $table_name ][ $record[ 'id' ] ] = array
                (
                    $record[ 'value' ],
                    $record[ 'display_order' ]
                );
            }
        }

        $this->ref_data_to_add    = array();
        $this->ref_data_to_modify = array();
        foreach ($source_data as $table_name => $data)
        {
            foreach ($data as $id => $values)
            {
                if (!array_key_exists($table_name, $target_data) ||
                    !array_key_exists($id, $target_data[ $table_name ]))
                {
                    $this->notice("(+) $table_name #$id", 1);

                    $this->ref_data_to_add[] = array
                    (
                        $table_name,
                        $id,
                        $values[ 0 ],
                        $values[ 1 ]
                    );
                }
                else
                {
                    if (implode(',', $values) !=
                        implode(',', $target_data[ $table_name ][ $id ]))
                    {
                        $this->notice("(=) $table_name #$id", 1);

                        $this->ref_data_to_modify[] = array
                        (
                            $table_name,
                            $id,
                            $values[ 0 ],
                            $values[ 1 ]
                        );
                    }
                }
            }
        }

        $this->ref_data_to_remove = array();
        foreach ($target_data as $table_name => $data)
        {
            foreach ($data as $id => $values)
            {
                if (!array_key_exists($table_name, $source_data) ||
                    !array_key_exists($id, $source_data[ $table_name ]))
                {
                    $this->notice("(-) $table_name #$id", 1);

                    $this->ref_data_to_remove[] = array
                    (
                        $table_name,
                        $id,
                        $values[ 0 ],
                        $values[ 1 ]
                    );
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
                $this->query_target(
                    __METHOD__,
                    $query,
                    array($this->target_db_name)));

        $this->ref_tables_to_create =
            array_values(array_diff($source_tables, $target_tables));
        foreach ($this->ref_tables_to_create as $table)
        {
            $this->notice("(+) $table", 1);
        }

        $drop_list = array();

        $this->ref_tables_to_drop =
            array_values(array_diff($target_tables, $source_tables));
        foreach ($this->ref_tables_to_drop as $table)
        {
            $this->notice("(-) $table", 1);

            $drop_list[] = "'$table'";
        }

        $this->ref_table_drop_list = implode(',', $drop_list);
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

            $create_drop_list[] = "'$table'";
        }

        $this->table_create_drop_list = implode(', ', $create_drop_list);
    }

    private function compute_unique_key_differences()
    {
        $this->notice('Computing unique key differences...');

        $query = "select table_name,
                         constraint_name,
                         column_name,
                         ordinal_position
                    from key_column_usage
                   where table_schema = ?
                     and constraint_name like '%\_uk'";
        if (!empty($this->table_create_drop_list))
        {
            $query .= 'and table_name not in ('
                      . $this->table_create_drop_list
                      . ')';
        }

        $source_uks =
            $this->process_uks(
                $this->query_source(
                    __METHOD__,
                    $query,
                    array($this->source_db_name)));
        $target_uks =
            $this->process_uks(
                $this->query_target(
                    __METHOD__,
                    $query,
                    array($this->target_db_name)));

        $source_uk_names = array_keys($source_uks);
        $target_uk_names = array_keys($target_uks);

        $unique_keys_to_create =
            array_diff($source_uk_names, $target_uk_names);
        $unique_keys_to_drop =
            array_diff($target_uk_names, $source_uk_names);

        $this->unique_keys_to_create = array();
        foreach ($unique_keys_to_create as $uk_name)
        {
            $this->notice("(+) $uk_name", 1);

            $this->unique_keys_to_create[] = $source_uks[ $uk_name ];
        }

        $this->unique_keys_to_drop = array();
        foreach ($unique_keys_to_drop as $uk_name)
        {
            $this->notice("(-) $uk_name", 1);

            $this->unique_keys_to_drop[] = $target_uks[ $uk_name ];
        }

        foreach ($source_uks as $constraint_name => $uk)
        {
            if (array_key_exists($constraint_name, $target_uks) &&
                !in_array($constraint_name, $this->unique_keys_to_create) &&
                !in_array($constraint_name, $this->unique_keys_to_drop))
            {
                if ($source_uks[ $constraint_name ][ 'table_name' ]         !=
                    $target_uks[ $constraint_name ][ 'table_name' ]         ||
                    implode(',', $source_uks[ $constraint_name ][ 'cols' ]) !=
                    implode(',', $target_uks[ $constraint_name ][ 'cols' ]))
                {
                    $this->notice("(=) $constraint_name", 1);

                    $this->unique_keys_to_drop[] =
                        $source_uks[ $constraint_name ];
                    $this->unique_keys_to_create[] =
                        $source_uks[ $constraint_name ];
                }
            }
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

    private function get_column_terms($column_data)
    {
        $terms = array();

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

        if ($column_data[ 'is_nullable' ] == 'NO')
        {
            $terms[] = 'not null';
        }

        return $terms;
    }

    private function notice($message, $indent = null)
    {
        if (is_null($this->cli))
        {
            return;
        }

        $this->cli->notice($message, $indent);
    }

    private function process_fks($data)
    {
        $fks = array();
        foreach ($data as $fk)
        {
            if (!array_key_exists($fk[ 'constraint_name' ], $fks))
            {
                $fks[ $fk[ 'constraint_name' ] ] = array
                (
                    'name'           => $fk[ 'constraint_name' ],
                    'table_name'     => $fk[ 'table_name' ],
                    'ref_table_name' => $fk[ 'referenced_table_name' ],
                    'cols'           => array
                    (
                        $fk[ 'ordinal_position' ] =>
                            $fk[ 'column_name' ]
                    ),
                    'ref_cols'       => array
                    (
                        $fk[ 'ordinal_position' ] =>
                            $fk[ 'referenced_column_name' ]
                    ),
                    'delete_rule'    => $fk[ 'delete_rule' ]
                );
            }
            else
            {
                $fks[ $fk[ 'constraint_name' ] ]
                    [ 'cols' ]
                    [ $fk[ 'ordinal_position' ] ] =
                        $fk[ 'column_name' ];

                $fks[ $fk[ 'constraint_name' ] ]
                    [ 'ref_cols' ]
                    [ $fk[ 'ordinal_position' ] ] =
                        $fk[ 'referenced_column_name' ];
            }
        }

        foreach ($fks as $constraint_name => $fk)
        {
            ksort($fks[ $constraint_name ][ 'cols' ]);
            ksort($fks[ $constraint_name ][ 'ref_cols' ]);
        }

        return $fks;
    }

    private function process_uks($data)
    {
        $uks = array();
        foreach ($data as $uk)
        {
            if (!array_key_exists($uk[ 'constraint_name' ], $uks))
            {
                $uks[ $uk[ 'constraint_name' ] ] = array
                (
                    'name'       => $uk[ 'constraint_name' ],
                    'table_name' => $uk[ 'table_name' ],
                    'cols'       => array
                    (
                        $uk[ 'ordinal_position' ] => $uk[ 'column_name' ]
                    )
                );
            }
            else
            {
                $uks[ $uk[ 'constraint_name' ] ]
                    [ 'cols' ]
                    [ $uk[ 'ordinal_position' ] ] =
                        $uk[ 'column_name' ];
            }
        }

        foreach ($uks as $constraint_name => $uk)
        {
            ksort($uks[ $constraint_name ][ 'cols' ]);
        }

        return $uks;
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

    private function verify_schemas()
    {
        $this->notice('Verifying schemas...');

        $query = 'select 1 as schema_exists
                    from schemata
                   where schema_name = ?';

        $record = $this->source->query
        (
            __METHOD__,
            $query,
            array($this->source_db_name)
        );

        if (!array_key_exists(0, $record) ||
            $record[ 0 ][ 'schema_exists' ] != 1)
        {
            $this->error('source schema "'
                         . $this->source_db_name
                         . "\" does not exist", 1);
            exit(1);
        }

        $record = $this->target->query
        (
            __METHOD__,
            $query,
            array($this->target_db_name)
        );

        if (!array_key_exists(0, $record) ||
            $record[ 0 ][ 'schema_exists' ] != 1)
        {
            $this->error('target schema "'
                         . $this->target_db_name
                         . "\" does not exist", 1);
            exit(1);
        }
    }

    private function warn($message, $indent = null)
    {
        if (is_null($this->cli))
        {
            return;
        }

        $this->cli->error($message, $indent);
    }

    private function write_add_foreign_key_constraint_sql()
    {
        $file = '55-foreign_keys.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->foreign_keys_to_create as $fk)
        {
            ob_start();
?>
alter table <?= $fk[ 'table_name' ] . "\n" ?>
        add constraint <?= $fk[ 'name' ] . "\n" ?>
    foreign key (<?= implode(', ', $fk[ 'cols' ]) ?>)
 references <?= $fk[ 'ref_table_name' ] . "\n" ?>
            (<?= implode(', ', $fk[ 'ref_cols' ]) ?>)
  on delete <?= strtolower($fk[ 'delete_rule' ]) ?>;
<?
            $contents .= ob_get_clean() . "\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_add_indexes_sql()
    {
        $file = '65-indexes.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->indexes_to_create as $index)
        {
            ob_start();
?>
create index <?= $index[ 'index_name' ]. "\n" ?>
          on <?= $index[ 'table_name' ] . "\n" ?>
              (<?= implode(',', $index[ 'cols' ]) ?>);
<?
            $contents .= ob_get_clean() . "\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_add_modify_columns_sql()
    {
        $file = '35-columns.sql';

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
            $contents .= ob_get_clean();

            $terms = $this->get_column_terms($column[ 0 ]);
            if (!empty($terms))
            {
                $contents .= "\n";
            }

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
            $contents .= ob_get_clean();

            $terms = $this->get_column_terms($column);
            if (!empty($terms))
            {
                $contents .= "\n";
            }

            foreach ($terms as $index => $term)
            {
                $terms[ $index ] = "            $term";
            }
            $contents .= implode("\n", $terms) . ";\n\n";
        }

        foreach ($this->columns_to_drop as $column)
        {
            list($table_name, $column_name) = explode('.', $column);

            ob_start();
?>
alter table <?= $table_name . "\n" ?>
       drop <?= $column_name ?>;
<?
            $contents .= ob_get_clean() . "\n";
        }

        foreach ($this->column_uniqueness_to_drop as $column)
        {
            list($table_name, $column_name) = explode('.', $column);

            $index = $this->query_target
            (
                __METHOD__,
                'show index
                 from ' . $this->target_db_name . ".$table_name
                 where column_name = ?",
                array($column_name)
            );

            ob_start();
?>
alter table <?= $index[ 0 ][ 'Table' ] . "\n" ?>
       drop index <?= $index[ 0 ][ 'Key_name' ] ?>;
<?
            $contents .= ob_get_clean() . "\n\n";
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
            $record = $this->source->query
            (
                __METHOD__,
                "show create table " . $this->source_db_name . ".$table_name"
            );

            $contents .= $record[ 0 ][ 'Create Table' ] . ";\n\n";
        }

        if (!empty($contents))
        {
            $contents .= "\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_add_tables_sql()
    {
        $file = '30-tables.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->tables_to_create as $table_name)
        {
            $record = $this->source->query
            (
                __METHOD__,
                "show create table " . $this->source_db_name . ".$table_name"
            );

            $contents .= $record[ 0 ][ 'Create Table' ] . ";\n\n\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_add_unique_key_constraint_sql()
    {
        $file = '60-unique_keys.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->unique_keys_to_create as $uk)
        {
            ob_start();
?>
alter table <?= $uk[ 'table_name' ] . "\n" ?>
        add unique key <?= $uk[ 'name' ] . "\n" ?>
            (<?= implode(', ', $uk[ 'cols' ]) ?>);
<?
            $contents .= ob_get_clean() . "\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_drop_foreign_key_constraint_sql()
    {
        $file = '15-foreign_keys.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->foreign_keys_to_drop as $fk)
        {
            ob_start();
?>
alter table <?= $fk[ 'table_name' ] . "\n" ?>
       drop foreign key <?= $fk[ 'name' ] ?>;
<?
            $contents .= ob_get_clean() . "\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_drop_indexes_sql()
    {
        $file = '25-indexes.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->indexes_to_drop as $index)
        {
            ob_start();
?>
alter table <?= $index[ 'table_name' ] . "\n" ?>
       drop index <?= $index[ 'index_name' ] ?>;
<?
            $contents .= ob_get_clean() . "\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_drop_ref_tables_sql()
    {
        $file = '45-ref_tables.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->ref_tables_to_drop as $table_name)
        {
            $contents .= "drop table if exists $table_name;\n\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_drop_tables_sql()
    {
        $file = '40-tables.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->tables_to_drop as $table_name)
        {
            $contents .= "drop table if exists $table_name;\n\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_drop_unique_key_constraint_sql()
    {
        $file = '20-unique_keys.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->unique_keys_to_drop as $uk)
        {
            ob_start();
?>
alter table <?= $uk[ 'table_name' ] . "\n" ?>
       drop key <?= $uk[ 'name' ] ?>;
<?
            $contents .= ob_get_clean() . "\n";
        }

        file_put_contents($file, $contents);
    }

    private function write_ref_table_data_sql()
    {
        $file = '50-ref_data.sql';

        $this->notice($file, 1);

        $contents = '';
        foreach ($this->ref_data_to_add as $data)
        {
            ob_start();
?>
insert into <?= $data[ 0 ] . "\n" ?>
(
    id,
    value,
    display_order
)
values
(
    <?= $data[ 1 ] ?>,
    '<?= $data[ 2 ] ?>',
    <?= $data[ 3 ] . "\n" ?>
);
<?
            $contents .= ob_get_clean() . "\n";
        }

        foreach ($this->ref_data_to_modify as $data)
        {
            ob_start();
?>
update <?= $data[ 0 ] . "\n" ?>
   set value = '<?= $data[ 2 ] ?>',
       display_order = <?= $data[ 3 ] . "\n" ?>
 where id = <?= $data[ 1 ] ?>;
<?
            $contents .= ob_get_clean() . "\n";
        }

        foreach ($this->ref_data_to_remove as $data)
        {
            ob_start();
?>
delete from <?= $data[ 0 ] . "\n" ?>
      where id = <?= $data[ 1 ] ?>;
<?
            $contents .= ob_get_clean() . "\n";
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
        $this->write_drop_foreign_key_constraint_sql();
        $this->write_drop_unique_key_constraint_sql();
        $this->write_drop_indexes_sql();
        $this->write_add_tables_sql();
        $this->write_add_modify_columns_sql();
        $this->write_drop_tables_sql();
        $this->write_drop_ref_tables_sql();
        $this->write_ref_table_data_sql();
        $this->write_add_foreign_key_constraint_sql();
        $this->write_add_unique_key_constraint_sql();
        $this->write_add_indexes_sql();
    }
}
?>
