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

require_once 'base/services/http.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class base_Test_Http
extends PHPUnit_Framework_TestCase
{
    function test_setting_and_getting_cookie_class()
    {
        $this->assertNull(tiny_api_Http_Manager::get_instance()
                            ->get_cookie('no-such-cookie'));

        tiny_api_Http_Manager::get_instance()
            ->set_cookie('abc', 'def', 0, '/', 'domain.com');

        $this->assertEquals('def', tiny_api_Http_Manager::get_instance()
                                        ->get_cookie('abc'));
    }

    function test_setting_and_getting_cookie_helper_functions()
    {
        $this->assertNull(tiny_api_http_get_cookie('no-such-cookie'));

        tiny_api_http_set_cookie('abc', 'def', 0, '/', 'domain.com');
        $this->assertEquals('def', tiny_api_http_get_cookie('abc'));
    }

    function test_posting_http_param()
    {
        tiny_api_http_post_param('abc', 123);
        $this->assertTrue(array_key_exists('abc', $_POST));
        $this->assertEquals(123, $_POST[ 'abc' ]);
    }
}
?>
