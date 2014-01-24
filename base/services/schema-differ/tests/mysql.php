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

require_once 'base/services/schema-differ/mysql.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class schema_Differ_Test_Mysql
extends PHPUnit_Framework_TestCase
{
    private $differ;

    function __construct()
    {
        global $__tiny_api_conf__;

        parent::__construct();

        $__tiny_api_conf__[ 'mysql connection data' ]
                          [ 'schema differ tests' ] =
        [
            '', '', ''
        ];

        $this->differ = tiny_api_Mysql_Schema_Differ::make(
                            'schema differ tests',
                            'schema_differ_source',
                            'schema differ tests',
                            'schema_differ_target')
                                ->dont_write_upgrade_scripts()
                                ->execute();
    }

    function test_ref_tables_to_create()
    {
        $tables = $this->differ->get_ref_tables_to_create();
        $this->assertEquals(1, count($tables));
        $this->assertEquals('schema_differ_ref_add', $tables[ 0 ]);
    }

    function test_ref_tables_to_drop()
    {
        $tables = $this->differ->get_ref_tables_to_drop();
        $this->assertEquals(1, count($tables));
        $this->assertEquals('schema_differ_ref_drop', $tables[ 0 ]);
    }

    function test_tables_to_create()
    {
        $tables = $this->differ->get_tables_to_create();
        $this->assertEquals(1, count($tables));
        $this->assertEquals('schema_differ_add', $tables[ 0 ]);
    }

    function test_tables_to_drop()
    {
        $tables = $this->differ->get_tables_to_drop();
        $this->assertEquals(1, count($tables));
        $this->assertEquals('schema_differ_drop', $tables[ 0 ]);
    }

    function test_columns_to_create()
    {
        $columns = $this->differ->get_columns_to_create();
        $this->assertEquals(1, count($columns));
        $this->assertEquals('schema_differ_cols.col_c', $columns[ 0 ]);
    }

    function test_columns_to_drop()
    {
        $columns = $this->differ->get_columns_to_drop();
        $this->assertEquals(1, count($columns));
        $this->assertEquals('schema_differ_cols.col_z', $columns[ 0 ]);
    }

    function test_columns_to_modify()
    {
        $columns = $this->differ->get_columns_to_modify();
        $this->assertEquals(1, count($columns));
        $this->assertTrue(array_key_exists('schema_differ_cols.col_b',
                          $columns));
    }

    function test_foreign_keys_to_create()
    {
        $fks = $this->differ->get_foreign_keys_to_create();
        $this->assertEquals(2, count($fks));
        $this->assertEquals('schema_differ_fks_0_fk', $fks[ 0 ][ 'name' ]);
        $this->assertEquals('schema_differ_fks_1_fk', $fks[ 1 ][ 'name' ]);
    }

    function test_foreign_keys_to_drop()
    {
        $fks = $this->differ->get_foreign_keys_to_drop();
        $this->assertEquals(2, count($fks));
        $this->assertEquals('schema_differ_fks_100_fk', $fks[ 0 ][ 'name' ]);
        $this->assertEquals('schema_differ_fks_1_fk', $fks[ 1 ][ 'name' ]);
    }
}
?>
