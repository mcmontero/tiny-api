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

require_once 'base/context.php';
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
    private $content_type;
    private $jsonp;
    protected $id;

    function __construct()
    {
        $this->content_type = 'application/json';
        $this->jsonp        = (bool)array_key_exists('jsonp', $_GET);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    public function execute($accessor = null, $id = null)
    {
        $this->secure();

        $this->id = $id;

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

        if (!is_null($accessor))
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

    public function get_content_type()
    {
        return $this->content_type;
    }

    public function post()
    {
        return new tiny_api_Response_Not_Implemented();
    }

    public function put()
    {
        return new tiny_api_Response_Not_Implemented();
    }

    final public function response_as_jsonp()
    {
        return $this->jsonp;
    }

    /**
     * Provides a step in the process of handling a request that allows you
     * to perform application specific authentication.
     */
    public function secure() {}

    final public function set_id($id)
    {
        if (!env_unit_test())
        {
            throw new tiny_api_Exception('setting ID outside of the unit test '
                                         . 'context is not allowed');
        }

        $this->id = $id;
        return $this;
    }

    final public function text_html()
    {
        $this->content_type = 'text/html';
    }
}
?>
