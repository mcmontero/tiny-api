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
// +-----------------------+
// | tiny_api_User_Handler |
// +-----------------------+
//

class tiny_api_User_Handler
extends tiny_api_Base_Handler
{
    function __construct()
    {
        parent::__construct();

        $this->dsh->select_db('test');
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function delete()
    {
        return tiny_api_Response_Ok::make()
                ->set_bool($this->dsh->delete('user_info', array('id = 1')));
    }

    final public function post()
    {
        return tiny_Api_Response_Ok::make()
                ->set_bool($this->dsh->update('user_info',
                                              array('name' => 'Sarah Montero'),
                                              array('id = 3')));
    }

    final public function put()
    {
        return tiny_api_Response_Ok::make()
                ->set_data(array('id' =>
                                 $this->dsh->create(
                                    'user_info',
                                    array('name' => 'Michael Montero'))));
    }

    final public function get()
    {
        return tiny_Api_Response_Ok::make()
                ->set_data($this->dsh->retrieve('user_info',
                                                array('id', 'name'),
                                                array('id = 1')));
    }
}
?>
