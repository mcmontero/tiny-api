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

require_once 'base/services/table-builder/reference.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class base_Test_Table_Builder_Reference
extends PHPUnit_Framework_TestCase
{
    private $new_ref_defs_file;
    private $last_ref_defs_file;

    function __construct()
    {
        global $__tiny_api_conf__;

        $this->new_ref_defs_file = '/tmp/reference_defs.php';

        $this->last_ref_def_file =
            $__tiny_api_conf__[ 'reference definition file' ];
        $__tiny_api_conf__[ 'reference definition file' ] =
            $this->new_ref_defs_file;

        ob_start();
?>
define('A_B_VAL_1', 1);
define('A_B_VAL_2', 2);
function ___A_REF_B()
{
    return array
    (
        1 => 'val_1',
        2 => 'val_2'
    );
}
<?
        file_put_contents($this->new_ref_defs_file,
                          "<?=\n" . ob_get_clean() . '?>');
    }

    function __destruct()
    {
        if (is_file($this->new_ref_defs_file))
        {
            unlink($this->new_ref_defs_file);
        }
    }

    function test_refv_exceptions()
    {
        try
        {
            refv('');

            $this->fail('Was able to get reference data even though no '
                        . 'reference table was provided');
        }
        catch (tiny_api_Reference_Exception $e)
        {
            $this->assertEquals(
                'the reference table name you provided was empty',
                $e->get_text());
        }

        try
        {
            refv('a_ref_b', '');

            $this->fail('Was able to get reference data even though an empty '
                        . 'value was provided.');
        }
        catch (tiny_api_Reference_Exception $e)
        {
            $this->assertEquals(
                'the reference value you provided was empty',
                $e->get_text());
        }
    }

    function test_refv_reference_table()
    {
        $this->assertNull(refv('no_such_ref_table'));

        $ref_table = refv('a_ref_b');
        $this->assertTrue(is_array($ref_table));
        $this->assertTrue(array_key_exists(1, $ref_table));
        $this->assertTrue(array_key_exists(2, $ref_table));
        $this->assertEquals('val_1', $ref_table[ 1 ]);
        $this->assertEquals('val_2', $ref_table[ 2 ]);
    }

    function test_refv_decode()
    {
        $this->assertNull(refv('a_ref_b', 'no_such_value'));
        $this->assertEquals(2, refv('a_ref_b', 'val_2'));
    }

    function test_refv_encode()
    {
        $this->assertNull(refv('a_ref_b', -1));
        $this->assertEquals('val_1', refv('a_ref_b', 1));
    }
}
?>
