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

require_once 'base/conf.php';

// +------------------------------------------------------------+
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +--------------------------------+
// | tiny_api_Rdbms_Builder_Manager |
// +--------------------------------+
//

class tiny_api_Rdbms_Builder_Manager
{
    private $cli;
    private $modules;
    private $num_rdbms_objects;
    private $num_rdbms_tables;
    private $num_rdbms_indexes;
    private $connection_name;
    private $exec_sql_command;
    private $dependencies_map;
    private $dependents_map;
    private $modules_to_build;
    private $modules_to_build_prefix;
    private $foreign_keys;
    private $unindexed_foreign_keys;

    function __construct(tiny_api_Cli $cli = null)
    {
        $this->cli                     = $cli;
        $this->modules                 = array();
        $this->num_rdbms_objects       = 0;
        $this->num_rdbms_tables        = 0;
        $this->num_rdbms_indexes       = 0;
        $this->dependencies_map        = array();
        $this->dependents_map          = array();
        $this->modules_to_build        = array();
        $this->modules_to_build_prefix = array();
        $this->foreign_keys            = array();
        $this->unindexed_foreign_keys  = array();
    }

    static function make(tiny_api_Cli $cli = null)
    {
        return new self($cli);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function execute()
    {
        if (empty($this->connection_name))
        {
            throw new tiny_api_Rdbms_Builder_Exception(
                        'connection name has not been set');
        }

        // +------------------------------------------------------------+
        // | Step 1                                                     |
        // |                                                            |
        // | Clean up unused RDBMS builder files.                       |
        // +------------------------------------------------------------+

        $this->clean_up_rdbms_builder_files();

        // +------------------------------------------------------------+
        // | Step 2                                                     |
        // |                                                            |
        // | Create RDBMS builder objects.                              |
        // +------------------------------------------------------------+

        $this->verify_rdbms_builder_objects();

        // +------------------------------------------------------------+
        // | Step 3                                                     |
        // |                                                            |
        // | Assemble all modules.                                      |
        // +------------------------------------------------------------+

        $this->assemble_all_modules();

        // +------------------------------------------------------------+
        // | Step 4                                                     |
        // |                                                            |
        // | Compile the list of modules that need to be built.         |
        // +------------------------------------------------------------+

        $module_name = $this->cli->get_arg('module-name');
        if (!empty($module_name))
        {
            $this->notice('Compiling build list for specified module...');
            $this->notice("(+) $module_name", 1);
            $this->compile_build_list_for_module($module_name);
        }
        else
        {
            $this->notice('Compiling build list based on changes...');
            $this->compile_build_list_by_changes();
        }

        // +------------------------------------------------------------+
        // | Step 5                                                     |
        // |                                                            |
        // | Determine if the build should continue.                    |
        // +------------------------------------------------------------+

        if (empty($this->modules_to_build))
        {
            $this->notice('Database is up to date!');
            exit(0);
        }

        // +------------------------------------------------------------+
        // | Step 6                                                     |
        // |                                                            |
        // | Drop all foreign key constraints for the tables that need  |
        // | to built so we can tear down objects without errors.       |
        // +------------------------------------------------------------+

        $this->drop_foreign_key_constraints();

        // +------------------------------------------------------------+
        // | Step 7                                                     |
        // |                                                            |
        // | Drop objects for modules marked for rebuild.               |
        // +------------------------------------------------------------+

        $this->drop_objects();

        // +------------------------------------------------------------+
        // | Step 8                                                     |
        // |                                                            |
        // | Rebuild modules.                                           |
        // +------------------------------------------------------------+

        $this->rebuild_modules();

        // +------------------------------------------------------------+
        // | Step 9                                                     |
        // |                                                            |
        // | Add all foreign key constraints.                           |
        // +------------------------------------------------------------+

        $this->add_foreign_key_constraints();

        // +------------------------------------------------------------+
        // | Step 10                                                    |
        // |                                                            |
        // | Verify foreign key indexes.                                |
        // +------------------------------------------------------------+

        $this->verify_foreign_key_indexes();

        // +------------------------------------------------------------+
        // | Step 11                                                    |
        // |                                                            |
        // | Report interesting stats about the build.                  |
        // +------------------------------------------------------------+

        $this->notice('RDBMS builder stats:');
        $this->notice(sprintf('%-16s: %6d',
                      '# tables',
                      number_format($this->num_rdbms_tables)),
                      1);
        $this->notice(sprintf('%-16s: %6d',
                      '# indexes',
                      number_format($this->num_rdbms_indexes)),
                      1);
        $this->notice(sprintf('%-16s: %6d',
                      'total # objects',
                      number_format($this->num_rdbms_objects)),
                      1);
    }

    final public function set_connection_name($connection_name)
    {
        $this->connection_name = $connection_name;
        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function add_foreign_key_constraints()
    {
        $this->notice('Adding foreign key constraints...');

        foreach ($this->foreign_keys as $module_name => $fk)
        {
            if (array_key_exists($module_name, $this->modules_to_build))
            {
                list($db_name, $foreign_key) = $fk;

                if (!preg_match('/add constraint (.*?) /msi',
                                $foreign_key, $matches))
                {
                    throw new tiny_api_Rdbms_Builder_Exception(
                                "could not find name of constraint in "
                                . "statement:\n"
                                . $foreign_key);
                }

                $this->execute_statement($db_name, $foreign_key);
                $this->notice("(+) " . trim($matches[ 1 ]), 1);

                $this->num_rdbms_objects++;
            }
        }
    }

    private function assemble_all_modules()
    {
        $this->notice('Assembling all modules...');

        $paths = explode(':', ini_get('include_path'));
        foreach ($paths as $path)
        {
            $command = "/usr/bin/find $path/ -type f -name \"build.php\"";
            $output  = null;
            exec($command, $output, $retval);

            if ($retval)
            {
                throw new tiny_api_Rdbms_Builder_Exception(
                            "failed to execute \"$command\": "
                            . print_r($output, true));
            }

            foreach ($output as $file)
            {
                $module_name = null;
                $build_func  = null;
                $prefix      = null;

                if (preg_match('#(.*?)/sql/build.php$#', $file, $matches))
                {
                    $module_name = preg_replace("#$path/?#", '', $matches[ 1 ]);

                    $contents = file_get_contents($file);
                    if ($contents &&
                        preg_match('/function ((.*)_build)\\s?\(/msi',
                                   $contents, $matches))
                    {
                        $build_func = $matches[ 1 ];
                        $prefix     = $matches[ 2 ];
                    }
                }

                if (!empty($module_name) && !empty($prefix))
                {
                    $this->modules[ $module_name ] =
                        _tiny_api_Rdbms_Builder_Module::make(
                                $module_name, $prefix)
                            ->set_build_file($file);

                    if (!array_key_exists(
                            $module_name,
                            $this->dependencies_map))
                    {
                        $this->dependencies_map[ $module_name ] = array();
                    }

                    if (!array_key_exists(
                            $module_name,
                            $this->dependents_map))
                    {
                        $this->dependents_map[ $module_name ] = array();
                    }

                    require_once $file;

                    $objs = $build_func();
                    $sql  = array();
                    foreach ($objs as $obj)
                    {
                        $sql[] = array
                        (
                            $obj->get_db_name(),
                            $obj->get_definition(),
                        );

                        if ($obj instanceof tiny_api_Table)
                        {
                            $dependencies = $obj->get_dependencies();
                            foreach ($dependencies as $dependency)
                            {
                                if ($module_name != $dependency)
                                {
                                    $this->dependencies_map[ $module_name ][] =
                                                $dependency;

                                    if (!array_key_exists(
                                            $dependency,
                                            $this->dependents_map))
                                    {
                                        $this->dependents_map[ $dependency ] =
                                                        array();
                                    }

                                    $this->dependents_map[ $dependency ][] =
                                                $module_name;
                                }
                            }

                            $indexes = $obj->get_index_definitions();
                            foreach ($indexes as $index)
                            {
                                $sql[] = array
                                (
                                    $obj->get_db_name(),
                                    "$index;",
                                );
                            }

                            $inserts = $obj->get_insert_statements();
                            if (!empty($inserts))
                            {
                                $sql[] = array
                                (
                                    $obj->get_db_name(),
                                    $inserts,
                                );
                            }

                            $fks = $obj->get_foreign_key_definitions();
                            foreach ($fks as $fk)
                            {
                                $this->foreign_keys[ $module_name ] = array
                                (
                                    $obj->get_db_name(),
                                    "$fk;",
                                );
                            }

                            $this->unindexed_foreign_keys =
                                array_merge($this->unindexed_foreign_keys,
                                            $obj->get_unindexed_foreign_keys());
                        }
                    }
                    $this->modules[ $module_name ]->set_sql($sql);
                }
            }
        }
    }

    private function build_sql(_tiny_api_Rdbms_Builder_Module $module)
    {
        foreach ($module->get_sql() as $data)
        {
            list($db_name, $statement) = $data;

            if (preg_match('/^create table (.*?)$/msi',
                           $statement, $matches))
            {
                $this->notice("(+) table $db_name." . $matches[ 1 ], 2);
                $this->num_rdbms_tables++;
                $this->num_rdbms_objects++;
            }
            else if (preg_match("/^create index (.*?)\n/msi",
                                $statement, $matches))
            {
                $this->notice("(+) index $db_name." . $matches[ 1 ], 2);
                $this->num_rdbms_indexes++;
                $this->num_rdbms_objects++;
            }
            else if (preg_match("/^insert into/", $statement))
            {
                $this->notice('(i) row', 2);
            }

            $this->execute_statement($db_name, $statement);
        }

        $sha1 = sha1(file_get_contents($module->get_build_file()));

        if (tiny_api_is_data_store_mysql_myisam())
        {
            dsh()->query
            (
                __METHOD__,
                'insert into rdbms_builder.module_info
                 (
                    build_file,
                    sha1
                 )
                 values
                 (
                    ?,
                    ?
                 )
                 on duplicate key
                 update sha1 = ?',
                array($module->get_build_file(), $sha1, $sha1)
            );
        }
        else
        {
            $this->data_store_not_supported();
        }

        return true;
    }

    private function clean_up_rdbms_builder_files()
    {
        $this->notice('Cleaning up RDBMS builder files...');

        $dir = new DirectoryIterator('/tmp');
        foreach ($dir as $file)
        {
            if (!$dir->isDot())
            {
                if (preg_match('/^tiny-api-rdbms-builder-/', $file))
                {
                    unlink("/tmp/$file");
                    $this->notice("(-) $file", 1);
                }
            }
        }
    }

    private function compile_build_list_by_changes()
    {
        foreach ($this->modules as $module)
        {
            $sha1 = sha1(file_get_contents($module->get_build_file()));

            if (tiny_api_is_data_store_mysql_myisam())
            {
                $results = dsh()->query
                (
                    __METHOD__,
                    'select sha1
                       from rdbms_builder.module_info
                      where build_file = ?',
                    array($module->get_build_file())
                );
            }
            else
            {
                $this->data_store_not_supported();
            }

            if (!array_key_exists(0, $results) ||
                !array_key_exists('sha1', $results[ 0 ]) ||
                $results[ 0 ][ 'sha1' ] != $sha1)
            {
                $this->notice('(+) ' . $module->get_name(), 1);
                $this->compile_build_list_for_module($module->get_name());
            }
        }
    }

    private function compile_build_list_for_module($module_name)
    {
        $this->modules_to_build
            [ $module_name ] = true;
        $this->modules_to_build_prefix
            [ $this->modules[ $module_name ]->get_prefix() ] = true;

        if (array_key_exists($module_name, $this->dependents_map))
        {
            foreach ($this->dependents_map[ $module_name ] as $index => $module)
            {
                $this->compile_build_list_for_module($module);
            }
        }
    }

    private function data_store_not_supported()
    {
        global $__tiny_api_conf__;

        throw new tiny_api_Rdbms_Builder_Exception(
                    'the RDBMS builder does not currently support "'
                    . $__tiny_api_conf__[ 'data store' ]
                    . '"');
    }

    private function drop_foreign_key_constraints()
    {
        $this->notice('Dropping relevant foreign key constraints...');

        if (tiny_api_is_data_store_mysql_myisam())
        {
            $constraints = dsh()->query
            (
                __METHOD__,
                'select constraint_schema,
                        table_name,
                        constraint_name
                   from referential_constraints'
            );

            foreach ($constraints as $constraint)
            {
                $name = explode('_', $constraint[ 'constraint_name' ]);
                if (array_key_exists($name[ 0 ],
                                     $this->modules_to_build_prefix))
                {
                    $this->notice('(-) foreign key '
                                  . $constraint[ 'constraint_name' ],
                                  1);

                    dsh()->query
                    (
                        __METHOD__,
                        'alter table '
                        . $constraint[ 'constraint_schema' ]
                        . '.'
                        . $constraint[ 'table_name' ]
                        . ' drop foreign key '
                        . $constraint[ 'constraint_name' ]
                    );
                }
            }
        }
        else
        {
            $this->data_store_not_supported();
        }
    }

    private function drop_objects()
    {
        $this->notice('Dropping objects that will be rebuilt...');

        if (tiny_api_is_data_store_mysql_myisam())
        {
            $tables = dsh()->query
            (
                __METHOD__,
                'select table_schema,
                        table_name
                   from tables
                  where table_schema != ?',
                array('information_schema')
            );

            foreach ($tables as $table)
            {
                $name = explode('_', $table[ 'table_name' ]);
                if (array_key_exists($name[ 0 ],
                    $this->modules_to_build_prefix))
                {
                    $this->notice('(-) table ' . $table[ 'table_name' ], 1);

                    dsh()->query
                    (
                        __METHOD__,
                        'drop table '
                        . $table[ 'table_schema' ]
                        . '.'
                        . $table[ 'table_name' ]
                    );
                }
            }
        }
        else
        {
            $this->data_store_not_supported();
        }
    }

    private function enhance_build_error(array $output)
    {
        if (tiny_api_is_data_store_mysql_myisam())
        {
            if (preg_match('/^ERROR 1005/', $output[ 0 ]) &&
                preg_match('/errno: 150/', $output[ 0 ]))
            {
                $output[ count($output) ] =
                    'A column that has a foreign key is not the exact same '
                    . 'type as the column it is referencing.';
            }
        }

        return $output;
    }

    private function error($message, $indent = null)
    {
        if (is_null($this->cli))
        {
            return null;
        }

        $this->cli->error($message, $indent);
    }

    private function execute_statement($db_name, $statement)
    {
        $temp_file = tempnam('/tmp', 'tiny-api-rdbms-builder-');
        file_put_contents($temp_file, $statement);

        $command = $this->get_exec_sql_command()
                   . " --database=$db_name"
                   . " < $temp_file "
                   . " 2>&1";
        $output  = null;
        exec($command, $output, $retval);

        if ($retval)
        {
            throw new tiny_api_Rdbms_Builder_Exception(
                        "$temp_file\n" .
                        print_r($this->enhance_build_error($output), true));
        }

        unlink($temp_file);
    }

    private function get_exec_sql_command()
    {
        global $__tiny_api_conf__;

        if (!empty($this->exec_sql_command))
        {
            return $this->exec_sql_command;
        }

        if (tiny_api_is_data_store_mysql_myisam())
        {
            if (empty($this->connection_name))
            {
                throw new tiny_api_Rdbms_Builder_Exception(
                            'cannot execute SQL because a connection name '
                            . 'has not been set.');
            }

            if (!array_key_exists(
                    $this->connection_name,
                    $__tiny_api_conf__[ 'mysql connection data' ]))
            {
                throw new tiny_api_Rdbms_Builder_Exception(
                            'no connection data has been configured for "'
                            . $this->connection_name
                            . '"');
            }

            list($host, $user, $password) =
                $__tiny_api_conf__[ 'mysql connection data' ]
                                  [ $this->connection_name ];

            $command = array('/usr/bin/mysql');
            if (!empty($host))
            {
                $command[] = "--host=$host";
            }

            if (!empty($user))
            {
                $command[] = "--user=$user";
            }

            if (!empty($password))
            {
                $command[] = "--password='$password'";
            }

            $this->exec_sql_command = implode(' ', $command);
            return $this->exec_sql_command;
        }
        else
        {
            $this->data_store_not_supported();
        }
    }

    private function notice($message, $indent = null)
    {
        if (is_null($this->cli))
        {
            return null;
        }

        $this->cli->notice($message, $indent);
    }

    private function rebuild_modules()
    {
        $this->notice('Building all objects now...');

        foreach ($this->modules_to_build as $module_name => $true)
        {
            $this->notice("building module $module_name", 1);

            $this->build_sql($this->modules[ $module_name ]);
        }
    }

    private function verify_foreign_key_indexes()
    {
        $this->notice('Verifying foreign key indexes...');

        foreach ($this->unindexed_foreign_keys as $data)
        {
            list($table_name, $parent_table_name, $cols, $parent_cols) = $data;

            $this->notice('(!) unindexed foreign key', 1);
            $this->notice("table: $table_name -> parent: $parent_table_name",
                          2);
            $this->notice('['
                          . implode(', ', $cols)
                          . '] -> ['
                          . implode(', ', $parent_cols)
                          . ']',
                          2);
        }

        if (!empty($this->unindexed_foreign_keys))
        {
            throw new tiny_api_Rdbms_Builder_Exception(
                        'unindexed foreign keys found (see above)');
        }
    }

    private function verify_rdbms_builder_objects()
    {
        global $__tiny_api_conf__;

        if (tiny_api_is_data_store_mysql_myisam())
        {
            $dsh = tiny_api_Data_Store_Provider::get_instance()
                    ->get_data_store_handle()
                    ->select_db($this->connection_name, 'information_schema');

            $databases = dsh()->query
            (
                __METHOD__,
                'show databases'
            );

            foreach ($databases as $database)
            {
                if ($database[ 'Database' ] == 'rdbms_builder')
                {
                    return;
                }
            }

            list($host, $user, $password) =
                $__tiny_api_conf__[ 'mysql connection data' ]
                                  [ $this->connection_name ];

            ob_start();
?>
create database rdbms_builder;

create table rdbms_builder.module_info
(
    build_file varchar(100) not null primary key,
    sha1 char(40) not null
);

grant all privileges
   on rdbms_builder.*
   to '<?= $user ?>'@'<?= $host ?>'
      identified by '<?= $password ?>';

flush privileges;
<?
            $statements = ob_get_clean();

            throw new tiny_api_Rdbms_Builder_Exception(
                        'RDBMS builder database and objects do not exist; '
                        . "create them as root using:\n\n" . $statements);
        }
        else
        {
            $this->data_store_not_supported();
        }
    }

    private function warn($message, $indent = null)
    {
        if (is_null($this->cli))
        {
            return null;
        }

        $this->cli->warn($message, $indent);
    }
}

// +------------------------------------------------------------+
// | PRIVATE CLASSES                                            |
// +------------------------------------------------------------+

//
// +--------------------------------+
// | _tiny_api_Rdbms_Builder_Module |
// +--------------------------------+
//

class _tiny_api_Rdbms_Builder_Module
{
    private $name;
    private $prefix;
    private $sql;
    private $build_file;

    function __construct($name, $prefix)
    {
        $this->name   = $name;
        $this->prefix = $prefix;
    }

    static function make($name, $prefix)
    {
        return new self($name, $prefix);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function get_build_file()
    {
        return $this->build_file;
    }

    final public function get_name()
    {
        return $this->name;
    }

    final public function get_prefix()
    {
        return $this->prefix;
    }

    final public function get_sql()
    {
        return $this->sql;
    }

    final public function set_build_file($build_file)
    {
        $this->build_file = $build_file;
        return $this;
    }

    final public function set_sql(array $sql)
    {
        $this->sql = $sql;
        return $this;
    }
}
?>
