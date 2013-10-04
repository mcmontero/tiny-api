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
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function get(tiny_api_Base_Data_Store $dsh)
    {
        error_log('execution of ' . __METHOD__ . '() succeeded');
        return new tiny_api_Response_Ok();
    }
}
?>
