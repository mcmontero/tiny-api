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

require_once 'base/handler.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class base_Test_Handler
extends PHPUnit_Framework_TestCase
{
    function test_content_type()
    {
        $handler = new tiny_api_Base_Handler();
        $this->assertEquals('application/json', $handler->get_content_type());

        $handler->text_htmL();
        $this->assertEquals('text/html', $handler->get_content_type());
    }

    function test_jsonp()
    {
        $handler = new tiny_api_Base_Handler();
        $this->assertFalse($handler->response_as_jsonp());

        $_GET[ 'jsonp' ] = 1;

        $handler = new tiny_api_Base_Handler();
        $this->assertTrue($handler->response_as_jsonp());
    }
}
?>
