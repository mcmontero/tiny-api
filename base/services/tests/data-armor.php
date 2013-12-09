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

require_once 'base/services/data-armor.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class services_Test_Data_Armor
extends PHPUnit_Framework_TestCase
{
    function test_data_armor_instantiation_exceptions()
    {
        try
        {
            tiny_api_Data_Armor::make('abc', 'def');

            $this->fail('Was able to instantiate tiny_api_Data_Armor even '
                        . 'though the encryption key was not 24 characters '
                        . 'long.');
        }
        catch (tiny_api_Data_Armor_Exception $e)
        {
            $this->assertEquals(
                'encryption key must be exactly 24 characters long',
                $e->get_text());
        }
    }

    function test_data_armor_lock_unlock()
    {
        $armored  = tiny_api_Data_Armor::make('123456789012345678901234',
                                             array('abc', 'def'))
                        ->lock();
        $unarmored = tiny_api_Data_Armor::make('123456789012345678901234',
                                               $armored)
                        ->unlock();

        $this->assertTrue(is_array($unarmored));
        $this->assertTrue(in_array('abc', $unarmored));
        $this->assertTrue(in_array('def', $unarmored));
    }

    function test_data_armor_unlock_ttl()
    {
        $armored  = tiny_api_Data_Armor::make('123456789012345678901234',
                                             array('abc', 'def'))
                        ->lock();

        try
        {
            tiny_api_Data_Armor::make('123456789012345678901234', $armored)
                ->unlock(-1);

            $this->fail('Was able to unlock armored data even though the TTL '
                        . 'should have expired it.');
        }
        catch (tiny_api_Data_Armor_Exception $e)
        {
            $this->assertEquals('token has expired', $e->get_text());
        }
    }

    function test_data_armor_unlock_format_exceptions()
    {
        try
        {
            tiny_api_Data_Armor::make('123456789012345678901234', 'abc')
                ->unlock();

            $this->fail('Was able to unlock armored data even though the '
                        . 'data provided was in the wrong format.');
        }
        catch (tiny_api_Data_Armor_Exception $e)
        {
            $this->assertEquals(
                'token format is incorrect (1)',
                $e->get_text());
        }
    }
}
?>
