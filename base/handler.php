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
    private $dsh;

    function __construct()
    {
        $this->dsh = tiny_api_Data_Store_Provider::make()->get();
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    public function execute()
    {
        $this->secure();
    }

    public function delete()
    {
        $this->do_delete();
    }

    /**
     * Returns an associative array mapping the data store key name to the
     * query string parameter name.
     *
     * Example:
     *  return array(
     *      'user_id' => 'id'
     *  );
     */
    public function delete_id() {}

    public function get() {}
    public function post() {}
    public function put() {}

    /**
     * Provides a step in the process of handling a request that allows you
     * to perform application specific authentication.
     */
    public function secure() {}

    // +-------------------+
    // | Protected Methods |
    // +-------------------+

    final protected function do_delete()
    {
        $delete_id = $this->delete_id();
        $delete    = array();
        foreach ($delete_id as $db_col => $qs_param)
        {
            if (!isset($_REQUEST[ $qs_param ]))
            {
                error_log("could not find parameter ($qs_param) in request");
                exit(1);
            }

            $delete[ $db_col ] = $_REQUEST[ $qs_param ];
        }
    }
}
?>
