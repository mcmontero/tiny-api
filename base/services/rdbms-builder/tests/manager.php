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

require_once 'base/services/rdbms-builder/manager.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class rdbm_Builder_Test_Manager
extends PHPUnit_Framework_TestCase
{
    function test_getting_module_sql()
    {
        $sql = _tiny_api_Rdbms_Builder_Module::make('a', 'b')
                    ->set_sql(array('c', 'd'))
                    ->get_sql();

        $this->assertTrue(is_array($sql));
        $this->assertTrue(in_array('c', $sql));
        $this->assertTrue(in_array('d', $sql));
    }

    function test_getting_module_build_file()
    {
        $this->assertEquals(
            '/a/b/c/build.php',
            _tiny_api_Rdbms_Builder_Module::make('a', 'b')
                ->set_build_file('/a/b/c/build.php')
                ->get_build_file());
    }

    function test_getting_module_prefix()
    {
        $this->assertEquals(
            'def',
            _tiny_api_Rdbms_Builder_Module::make('abc', 'def')
                ->get_prefix());
    }

    function test_builder_manager_execute_exceptions()
    {
        try
        {
            tiny_api_Rdbms_Builder_Manager::make()->execute();

            $this->fail('Was able to execute tiny_api_Rdbms_Builder_Manager '
                        . 'even though no connection name was set.');
        }
        catch (tiny_api_Rdbms_Builder_Exception $e)
        {
            $this->assertEquals(
                'connection name has not been set',
                $e->get_text());
        }
    }

    function test_getting_module_name()
    {
        $this->assertEquals(
            'abc',
            _tiny_api_Rdbms_Builder_Module::make('abc', 'def')
                ->get_name());
    }

    function test_adding_and_getting_dml_files_for_rdbms_builder_module()
    {
        $dml_files = _tiny_api_Rdbms_Builder_Module::make('abc', 'def')
                        ->add_dml_file('/a/b/c')
                        ->add_dml_file('/d/e/f')
                        ->get_dml_files();

        $this->assertTrue(is_array($dml_files));
        $this->assertEquals(2, count($dml_files));
        $this->assertEquals('/a/b/c', $dml_files[ 0 ]);
        $this->assertEquals('/d/e/f', $dml_files[ 1 ]);
    }
}
?>
