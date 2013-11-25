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
// +--------------------+
// | tiny_api_Exception |
// +--------------------+
//

class tiny_api_Exception
extends Exception
{
    function __construct($message)
    {
        parent::__construct($this->format_message($message));
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function format_message($message)
    {
        return "\n====================================================="
               . "=========================\n"
               . $message
               . "\n===================================================="
               . "==========================\n";
    }
}

//
// +-------------------------------+
// | tiny_api_Dispatcher_Exception |
// +-------------------------------+
//

class tiny_api_Dispatcher_Exception extends tiny_api_Exception {}

//
// +-------------------------------+
// | tiny_api_Data_Store_Exception |
// +-------------------------------+
//

class tiny_Api_Data_Store_Exception extends tiny_api_Exception {}

//
// +------------------------+
// | tiny_api_Cli_Exception |
// +------------------------+
//

class tiny_Api_Cli_Exception extends tiny_api_Exception {}
?>