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

require_once 'base/services/data-mapper.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class base_Test_Data_Mapper
extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        _tiny_api_Data_Mapper_Put_Manager::get_instance()->reset();
    }

    function test_validating_data_mapper_element_type_id_exceptions()
    {
        try
        {
            _tiny_api_Data_Mapper_Element::make('a', -1);

            $this->fail('Was able to instantiate _tiny_api_Data_Mapper even '
                        . 'though the type ID provided was invalid.');
        }
        catch (tiny_api_Data_Mapper_Exception $e)
        {
            $this->assertEquals('unrecognized type ID "-1"', $e->get_text());
        }
    }

    function test_getting_value_without_setting_exceptions()
    {
        try
        {
            _tiny_api_Data_Mapper_Element::make(
                'abc', _tiny_api_Data_Mapper_Element::TYPE_NUMBER)
                    ->get();

            $this->fail('Was able to get a value without first setting it.');
        }
        catch (tiny_api_Data_Mapper_Exception $e)
        {
            $this->assertEquals(
                'cannot get value because a value has not been set',
                $e->get_text());
        }
    }

    function test_getting_without_validating_exceptions()
    {
        try
        {
            _tiny_api_Data_Mapper_Element::make(
                'abc', _tiny_api_Data_Mapper_Element::TYPE_NUMBER)
                    ->set_value(125.5)
                    ->get();

            $this->fail('Was able to get a value without validating it first.');
        }
        catch (tiny_api_Data_Mapper_Exception $e)
        {
            $this->assertEquals(
                'cannot get value because it has not been validated',
                $e->get_text());
        }
    }

    function test_max_length_exceptions()
    {
        try
        {
            _tiny_api_Data_Mapper_Element::make(
                'abc', _tiny_api_Data_Mapper_Element::TYPE_NUMBER)
                    ->max_length(123);

            $this->fail('Was able to set the maximum length of a number.');
        }
        catch (tiny_api_Data_Mapper_Exception $e)
        {
            $this->assertEquals(
                'a maximum length can only be set for character and password '
                . 'types', $e->get_text());
        }
    }

    function test_validation_error_max_length()
    {
        $this->assertEquals(
            _tiny_api_Data_Mapper_Element::ERROR_MAX_LENGTH,
            _tiny_api_Data_Mapper_Element::make(
                'abc', _tiny_api_Data_Mapper_Element::TYPE_CHAR)
                    ->max_length(1)
                    ->set_value('abc')
                    ->validate());
    }

    function test_validation_error_max_length_array()
    {
        $this->assertEquals(
            _tiny_api_Data_Mapper_Element::ERROR_MAX_LENGTH,
            _tiny_api_Data_Mapper_Element::make(
                'abc', _tiny_api_Data_Mapper_Element::TYPE_CHAR)
                    ->max_length(1)
                    ->set_value(array('a', 'bcd'))
                    ->validate());
    }

    function test_validation_error_required()
    {
        $this->assertEquals(
            _tiny_api_Data_Mapper_Element::ERROR_REQUIRED,
            _tiny_api_Data_Mapper_Element::make(
                'abc', _tiny_api_Data_Mapper_Element::TYPE_CHAR)
                    ->required()
                    ->validate());
    }

    function test_validation_error_type()
    {
        $this->assertEquals(
            _tiny_api_Data_Mapper_Element::ERROR_TYPE,
            _tiny_api_Data_Mapper_Element::make(
                'abc', _tiny_api_Data_Mapper_Element::TYPE_NUMBER)
                    ->set_value('abc')
                    ->validate());
    }

    function test_validation_error_type_array()
    {
        $this->assertEquals(
            _tiny_api_Data_Mapper_Element::ERROR_TYPE,
            _tiny_api_Data_Mapper_Element::make(
                'abc', _tiny_api_Data_Mapper_Element::TYPE_NUMBER)
                    ->set_value(array(123, 'abc'))
                    ->validate());
    }

    function test_getting_value_from_get_request()
    {
        $_SERVER[ 'REQUEST_METHOD' ] = 'GET';
        $_GET[ 'abc' ] = 123;

        $elem = _tiny_api_Data_Mapper_Element::make(
                    'abc', _tiny_api_Data_Mapper_Element::TYPE_NUMBER)
                        ->required()
                        ->set_value();
        $this->assertEquals(_tiny_api_Data_Mapper_Element::ERROR_NONE,
                            $elem->validate());

        $this->assertEquals(123, $elem->get());
    }

    function test_getting_value_from_get_request_array()
    {
        $_SERVER[ 'REQUEST_METHOD' ] = 'GET';
        $_GET[ 'abc[]' ] = array(123, 456);

        $elem = _tiny_api_Data_Mapper_Element::make(
                    'abc[]', _tiny_api_Data_Mapper_Element::TYPE_NUMBER)
                        ->required()
                        ->set_value();
        $this->assertEquals(_tiny_api_Data_Mapper_Element::ERROR_NONE,
                            $elem->validate());

        $value = $elem->get();
        $this->assertTrue(is_array($value));
        $this->assertEquals(123, $value[ 0 ]);
        $this->assertEquals(456, $value[ 1 ]);
    }

    function test_getting_value_from_put_request()
    {
        $_SERVER[ 'REQUEST_METHOD' ] = 'POST';
        $_POST[ 'abc' ] = 'def';

        $elem = _tiny_api_Data_Mapper_Element::make(
                    'abc', _tiny_api_Data_Mapper_Element::TYPE_CHAR)
                        ->required()
                        ->set_value();
        $this->assertEquals(_tiny_api_Data_Mapper_Element::ERROR_NONE,
                            $elem->validate());

        $this->assertEquals('def', $elem->get());
    }

    function test_getting_value_from_put_request_array()
    {
        $_SERVER[ 'REQUEST_METHOD' ] = 'POST';
        $_POST[ 'abc[]' ] = array('def', 'ghi');

        $elem = _tiny_api_Data_Mapper_Element::make(
                    'abc[]', _tiny_api_Data_Mapper_Element::TYPE_CHAR)
                        ->required()
                        ->set_value();
        $this->assertEquals(_tiny_api_Data_Mapper_Element::ERROR_NONE,
                            $elem->validate());

        $value = $elem->get();
        $this->assertTrue(is_array($value));
        $this->assertEquals('def', $value[ 0 ]);
        $this->assertEquals('ghi', $value[ 1 ]);
    }

    function test_getting_data_mapper_exceptions()
    {
        try
        {
            tiny_api_Data_Mapper::make()->get();

            $this->fail('Was able to get data mapped data even though no '
                        . 'validation occurred.');
        }
        catch (tiny_api_Data_Mapper_Exception $e)
        {
            $this->assertEquals(
                'you cannot get mapped data because it has not been validated',
                $e->get_text());
        }
    }

    function test_tiny_api_Data_Mapper_get()
    {
        $_SERVER[ 'REQUEST_METHOD' ] = 'GET';
        $_GET[ 'abc' ] = 'def';
        $_GET[ 'ghi' ] = 123;

        $mapper = tiny_api_Data_Mapper::make()
                    ->char('abc', true, 5)
                    ->num('ghi', true);

        $this->assertNull($mapper->validate());

        $data = $mapper->get();
        $this->assertTrue(is_array($data));
        $this->assertTrue(array_key_exists('abc', $data));
        $this->assertTrue(array_key_exists('ghi', $data));
        $this->assertEquals('def', $data[ 'abc' ]);
        $this->assertEquals(123, $data[ 'ghi' ]);
    }

    function test_tiny_api_Data_Mapper_post()
    {
        $_SERVER[ 'REQUEST_METHOD' ] = 'POST';
        $_POST[ 'abc' ] = 'def';
        $_POST[ 'ghi' ] = 123;

        $mapper = tiny_api_Data_Mapper::make()
                    ->char('abc', true, 5)
                    ->num('ghi', true);

        $this->assertNull($mapper->validate());

        $data = $mapper->get();
        $this->assertTrue(is_array($data));
        $this->assertTrue(array_key_exists('abc', $data));
        $this->assertTrue(array_key_exists('ghi', $data));
        $this->assertEquals('def', $data[ 'abc' ]);
        $this->assertEquals(123, $data[ 'ghi' ]);
    }
}
?>
