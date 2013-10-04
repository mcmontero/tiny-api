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
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +--------------------------+
// | tiny_api_Base_Data_Store |
// +--------------------------+
//

abstract class tiny_api_Base_Data_Store
{
    function __construct() {}
}

//
// +------------------------------+
// | tiny_api_Data_Store_Provider |
// +------------------------------+
//

class tiny_api_Data_Store_Provider
{
    private $tiny_api_conf;
    private $dsh;

    function __construct()
    {
        global $__tiny_api_conf__;
        $this->tiny_api_conf = $__tiny_api_conf__;
    }

    public static function make()
    {
        return new self();
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function get_data_store_handle()
    {
        if ($this->tiny_api_conf[ 'data store' ] == 'mysql (myisam)')
        {
            if (is_null($this->dsh))
            {
                require_once 'base/data-store/mysql-myisam.php';
                $this->dsh = new tiny_api_Data_Store_Mysql_Myisam();
            }

            return $this->dsh;
        }
        else
        {
            error_log('Data store configured in tiny-api-conf.php ('
                      . $this->tiny_api_conf[ 'data store' ]
                      . ') is not presently supported');
            return null;
        }
    }
}
?>
