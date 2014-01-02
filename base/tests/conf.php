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

require_once 'base/conf.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class base_Test_Conf
extends PHPUnit_Framework_TestCase
{
    function test_is_data_store_mysql()
    {
        global $__tiny_api_conf__;

        $last_data_store = $__tiny_api_conf__[ 'data store' ];
        $__tiny_api_conf__[ 'data store' ] = 'mysql';

        $this->assertTrue(tiny_api_is_data_store_mysql());

        $__tiny_api_conf__[ 'data store' ] = $last_data_store;
    }
}
?>
