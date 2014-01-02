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

require_once 'base/data-store/mysql-myisam.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class base_Data_Store_Test_Mysql
extends PHPUnit_Framework_TestCase
{
    function test_commit_without_connect_exceptions()
    {
        $mysql = new tiny_api_Data_Store_Mysql_Myisam();

        try
        {
            $mysql->commit();

            $this->fail('Was able to commit a MySQL transaction even though '
                        . 'a connection was not established.');
        }
        catch (tiny_Api_Data_Store_Exception $e)
        {
            $this->assertEquals(
                'transaction cannot be committed because a database '
                . 'connection has not be established yet',
                $e->get_text());
        }
    }
}
?>
