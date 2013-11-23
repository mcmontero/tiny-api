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

require_once 'base/data-store/provider.php';

// +------------------------------------------------------------+
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +-----------------------+
// | tiny_api_Base_Handler |
// +-----------------------+
//

class tiny_api_Base_Handler
{
    protected $dsh;

    function __construct()
    {
        $this->dsh = tiny_api_Data_Store_Provider::make()
                        ->get_data_store_handle();
        if (is_null($this->dsh))
        {
            return new tiny_api_Response_Internal_Server_Error();
        }
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    public function execute($accessor = '')
    {
        $this->secure();

        if ($_SERVER[ 'REQUEST_METHOD' ] == 'DELETE')
        {
            $func = 'delete';
        }
        else if ($_SERVER[ 'REQUEST_METHOD' ] == 'GET')
        {
            $func = 'get';
        }
        else if ($_SERVER[ 'REQUEST_METHOD' ] == 'POST')
        {
            $func = 'post';
        }
        else if ($_SERVER[ 'REQUEST_METHOD' ] == 'PUT')
        {
            $func = 'put';
        }

        if (!empty($accessor))
        {
            $func .= "_$accessor";
        }

        return $this->$func();
    }

    public function delete()
    {
        return new tiny_api_Response_Not_Implemented();
    }

    public function get()
    {
        return new tiny_api_Response_Not_Implemented();
    }

    public function post()
    {
        return new tiny_api_Response_Not_Implemented();
    }

    public function put()
    {
        return new tiny_api_Response_Not_Implemented();
    }

    /**
     * Provides a step in the process of handling a request that allows you
     * to perform application specific authentication.
     */
    public function secure() {}
}
?>
