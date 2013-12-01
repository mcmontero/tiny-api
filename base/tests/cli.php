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

require_once 'base/cli.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class base_Test_Cli
extends PHPUnit_Framework_TestCase
{
    function test_cli_draw_header()
    {
        ob_start();
?>
// +--------------------------------------------------------------------------+
// | Abc                                                                      |
// +--------------------------------------------------------------------------+
<?
        $expected = ob_get_clean();

        ob_start();
        tiny_api_cli_draw_header('Abc');
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);
    }

    function test_cli_repeat_char()
    {
        $this->assertNull(tiny_api_cli_repeat_char('', 1));
        $this->assertNull(tiny_api_cli_repeat_char(null, 1));
        $this->assertNull(tiny_api_cli_repeat_char('|', 0));
        $this->assertNull(tiny_api_cli_repeat_char('|', -1));
        $this->assertEquals('*******', tiny_api_cli_repeat_char('*', 7));
    }
}
?>
