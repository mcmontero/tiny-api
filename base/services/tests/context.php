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

require_once 'base/services/context.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class base_Test_Context
extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        putenv('APP_SERVER_ENV=');
        tiny_api_Context::get_instance()->reset();
    }

    function test_get_server_env_exceptions()
    {
        try
        {
            tiny_api_Context::get_instance()->get_server_env();

            $this->fail('Was able to get server environment even though one '
                        . 'was not set.');
        }
        catch (tiny_api_Context_Exception $e)
        {
            $this->assertEquals(
                'could not find environment variable called "APP_SERVER_ENV"',
                $e->get_text());
        }

        tiny_api_Context::get_instance()->reset();
        putenv('APP_SERVER_ENV=invalid');

        try
        {
            tiny_api_Context::get_instance()->get_server_env();

            $this->fail('Was able to get server environment even though the '
                        . 'value set was invalid.');
        }
        catch (tiny_api_Context_Exception $e)
        {
            $this->assertEquals(
                    'application server environment "invalid" is not valid',
                    $e->get_text());
        }
    }

    function test_context_is_cli()
    {
        $this->assertTrue(tiny_api_Context::get_instance()->is_cli());
        $this->assertTrue(env_cli());
        $this->assertFalse(env_web());
    }

    function test_context_local()
    {
        putenv('APP_SERVER_ENV=local');

        $this->assertEquals(tiny_api_Context::LOCAL,
                            tiny_api_Context::get_instance()->get_server_env());
        $this->assertTrue(env_local());
    }

    function test_context_staging()
    {
        putenv('APP_SERVER_ENV=staging');

        $this->assertEquals(tiny_api_Context::STAGING,
                            tiny_api_Context::get_instance()->get_server_env());
        $this->assertTrue(env_staging());
    }

    function test_context_qa()
    {
        putenv('APP_SERVER_ENV=qa');

        $this->assertEquals(tiny_api_Context::QA,
                            tiny_api_Context::get_instance()->get_server_env());
        $this->assertTrue(env_qa());
    }

    function test_context_production()
    {
        putenv('APP_SERVER_ENV=production');

        $this->assertEquals(tiny_api_Context::PRODUCTION,
                            tiny_api_Context::get_instance()->get_server_env());
        $this->assertTrue(env_prod());
    }

    function test_context_unit_test()
    {
        tiny_api_Context::get_instance()->set_unit_test();
        $this->assertTrue(env_unit_test());
    }
}
?>
