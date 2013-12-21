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
// | tiny_api_Rdbms_Builder_Manager |
// +--------------------------------+
//

class tiny_api_Rdbms_Builder_Manager
{
    private $cli;
    private $modules;
    private $modules_to_build;
    private $modules_to_build_prefix;
    private $num_rdbms_objects;
    private $connection_name;
    private $exec_sql_command;

    function __construct(tiny_api_Cli $cli = null)
    {
        $this->cli                     = $cli;
        $this->modules                 = array();
        $this->modules_to_build        = array();
        $this->modules_to_build_prefix = array();
        $this->num_rdbms_objects       = 0;
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
        // +------------------------------------------------------------+
        // | Step 1                                                     |
        // |                                                            |
        // | Create RDBMS builder objects.                              |
        // +------------------------------------------------------------+

        $this->verify_rdbms_builder_objects();

        // +------------------------------------------------------------+
        // | Step 1                                                     |
        // |                                                            |
        // | Compile the list of build files in the API.                |
        // +------------------------------------------------------------+

        $this->notice('Compiling list of build files...');

        $build_files = array();
        $paths       = explode(':', ini_get('include_path'));
        foreach ($paths as $path)
        {
            foreach (new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path))
                     as $file)
            {
                if (preg_match('#sql/build.php$#', $file->getPathName()))
                {
                    $build_files[] = $file->getPathName();
                }
            }
        }
        $num_build_files = count($build_files);

        $this->notice('number of build files found: '
                      . number_format($num_build_files),
                      1);

        // +------------------------------------------------------------+
        // | Step 2                                                     |
        // |                                                            |
        // | Configure each of the modules that needs to be built.      |
        // +------------------------------------------------------------+

        $this->notice('Compiling modules to build...');

        foreach ($build_files as $build_file)
        {
            $contents = file_get_contents($build_file);
            $sha1     = sha1($contents);

            $module_info = dsh()->query
            (
                __METHOD__,
                'select sha1
                   from rdbms_builder.module_info
                  where build_file = ?',
                array($build_file)
            );

            if (empty($module_info) || $module_info[ 0 ][ 'sha1' ] != $sha1)
            {
                $path        = explode('/', $build_file);
                $module_name = $path[ (count($path) - 3) ];
                if (empty($module_name))
                {
                    throw new tiny_api_Rdbms_Builder_Exception(
                                'could not determine module name from build '
                                . "file path \"$build_file\"");
                }

                if (!empty($contents))
                {
                    if (!preg_match('/function ((.*)_build)\\s?\(/msi',
                                    $contents, $matches))
                    {
                        continue;
                    }

                    $build_func = $matches[ 1 ];
                    $prefix     = $matches[ 2 ];

                    $this->modules[ $module_name ] =
                        _tiny_api_Rdbms_Builder_Module::make(
                                $module_name, $prefix)
                            ->set_build_file($build_file);

                    require_once $build_file;

                    $objs = $build_func();
                    $sql  = array();
                    foreach ($objs as $obj)
                    {
                        $sql[] = array(
                            $obj->get_db_name(),
                            $obj->get_definition(),
                        );

                        if ($obj instanceof tiny_api_Table)
                        {
                            $dependencies = $obj->get_dependencies();
                            foreach ($dependencies as $dependency)
                            {
                                $this->modules_to_build[ $dependency ] = true;
                            }
                            $this->modules[ $module_name ]
                                ->set_dependencies($dependencies);

                            $indexes = $obj->get_index_definitions();
                            foreach ($indexes as $index)
                            {
                                $sql[] = array(
                                    $obj->get_db_name(),
                                    "$index;",
                                );
                            }

                            $sql[] = array(
                                $obj->get_db_name(),
                                $obj->get_insert_statements(),
                            );
                        }
                    }
                    $this->modules[ $module_name ]->set_sql($sql);

                    $this->modules_to_build[ $module_name ]   = true;
                    $this->modules_to_build_prefix[ $prefix ] = true;
                }
            }
        }

        $this->notice('number of modules to build: '
                      . number_format(count($this->modules_to_build)),
                      1);

        if (empty($this->modules_to_build))
        {
            $this->notice('The database is up to date.');
            exit(0);
        }

        // +------------------------------------------------------------+
        // | Step 3                                                     |
        // |                                                            |
        // | Drop all foreign key constraints for the tables that need  |
        // | to built so we can tear down objects without errors.       |
        // +------------------------------------------------------------+

        $this->drop_foreign_key_constraints();

        // +------------------------------------------------------------+
        // | Step 5                                                     |
        // |                                                            |
        // | Build all modules.                                         |
        // +------------------------------------------------------------+

        $this->notice('Dropping objects that will be rebuilt...');

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
            if (array_key_exists($name[ 0 ], $this->modules_to_build_prefix))
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

        // +------------------------------------------------------------+
        // | Step 6                                                     |
        // |                                                            |
        // | Build all modules.                                         |
        // +------------------------------------------------------------+

        $this->notice('Building all objects now...');

        $pass = 0;
        while (1)
        {
            foreach ($this->modules_to_build as $module_name => $true)
            {
                $build = true;
                $deps  = $this->modules[ $module_name ]->get_dependencies();
                foreach ($deps as $dep)
                {
                    if (array_key_exists($dep, $this->modules_to_build))
                    {
                        $build = false;
                        break;
                    }
                }

                if ($build)
                {
                    $this->notice("(pass.$pass) building module $module_name",
                                  1);

                    if ($this->build_sql($this->modules[ $module_name ]))
                    {
                        unset($this->modules_to_build[ $module_name ]);
                    }
                }
            }

            if (count($this->modules_to_build) == 0)
            {
                break;
            }

            $pass++;
            if ($pass >= 10)
            {
                throw new tiny_api_Rdbms_Builder_Exception(
                            'a circular dependency exists between the '
                            . "following modules:\n"
                            . trim(print_r($this->modules_to_build, true)));
            }
        }

        $this->notice('Number of objects built: '
                      . number_format($this->num_rdbms_objects));
    }

    final public function set_connection_name($connection_name)
    {
        $this->connection_name = $connection_name;
        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function build_sql(_tiny_api_Rdbms_Builder_Module $module)
    {
        global $__tiny_api_conf__;

        foreach ($module->get_sql() as $data)
        {
            list($db_name, $statement) = $data;

            if (preg_match('/^create table (.*?)$/msi',
                           $statement, $matches))
            {
                $this->notice("(+) table $db_name." . $matches[ 1 ], 2);
                $this->num_rdbms_objects++;
            }
            else if (preg_match("/^create index (.*?)\n/msi",
                                $statement, $matches))
            {
                $this->notice("(+) index $db_name." . $matches[ 1 ], 2);
                $this->num_rdbms_objects++;
            }
            else if (preg_match("/^insert into/", $statement))
            {
                $this->notice('(i) row', 2);
            }

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

        $sha1 = sha1(file_get_contents($module->get_build_file()));

        if ($__tiny_api_conf__[ 'data store' ] == 'mysql (myisam)')
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
            throw new tiny_api_Rdbms_Builder_Exception(
                        'the RDBMS builder does not currently support "'
                        . $__tiny_api_conf__[ 'data store' ]
                        . '"');
        }

        return true;
    }

    private function drop_foreign_key_constraints()
    {
        global $__tiny_api_conf__;

        $this->notice('Dropping relevant foreign key constraints...');

        if ($__tiny_api_conf__[ 'data store' ] == 'mysql (myisam)')
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
            throw new tiny_api_Rdbms_Builder_Exception(
                        'the RDBMS builder does not currently support "'
                        . $__tiny_api_conf__[ 'data store' ]
                        . '"');
        }
    }

    private function enhance_build_error(array $output)
    {
        global $__tiny_api_conf__;

        if ($__tiny_api_conf__[ 'data store' ] == 'mysql (myisam)')
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

    private function get_exec_sql_command()
    {
        global $__tiny_api_conf__;

        if (!empty($this->exec_sql_command))
        {
            return $this->exec_sql_command;
        }

        if ($__tiny_api_conf__[ 'data store' ] == 'mysql (myisam)')
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
            throw new tiny_api_Rdbms_Builder_Exception(
                        'the RDBMS builder does not currently support "'
                        . $__tiny_api_conf__[ 'data store' ]
                        . '"');
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

    private function verify_rdbms_builder_objects()
    {
        global $__tiny_api_conf__;

        if ($__tiny_api_conf__[ 'data store' ] == 'mysql (myisam)')
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
   to ''@'localhost'
      identified by '';

flush privileges;
<?
            $statements = ob_get_clean();

            throw new tiny_api_Rdbms_Builder_Exception(
                        'RDBMS builder database and objects do not exist; '
                        . "create them as root using:\n\n" . $statements);
        }
        else
        {
            throw new tiny_api_Rdbms_Builder_Exception(
                        'the RDBMS builder does not currently support "'
                        . $__tiny_api_conf__[ 'data store' ]
                        . '"');
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
    private $build_file;
    private $sql;
    private $dependencies;

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

    final public function get_dependencies()
    {
        return $this->dependencies;
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

    final public function set_dependencies(array $dependencies)
    {
        $this->dependencies = $dependencies;
        return $this;
    }

    final public function set_sql(array $sql)
    {
        $this->sql = $sql;
        return $this;
    }
}
?>
