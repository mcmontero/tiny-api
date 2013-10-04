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
// | DEFINITIONS                                                |
// +------------------------------------------------------------+

define('TINY_API_RESPONSE_OK',                    200);
define('TINY_API_RESPONSE_CREATED',               201);
define('TINY_API_RESPONSE_ACCEPTED',              202);
define('TINY_API_RESPONSE_NON_AUTH_INFO',         203);
define('TINY_API_RESPONSE_NO_CONTENT',            204);
define('TINY_API_RESPONSE_RESET_CONTENT',         205);
define('TINY_API_RESPONSE_PARTIAL_CONTENT',       206);
define('TINY_API_RESPONSE_MOVED_PERMANENTLY',     301);
define('TINY_API_RESPONSE_FOUND',                 302);
define('TINY_API_RESPONSE_NOT_MODIFIED',          304);
define('TINY_API_RESPONSE_TEMP_REDIRECT',         307);
define('TINY_API_RESPONSE_BAD_REQUEST',           400);
define('TINY_API_RESPONSE_UNAUTHORIZED',          401);
define('TINY_API_RESPONSE_PAYMENT_REQUIRED',      402);
define('TINY_API_RESPONSE_FORBIDDEN',             403);
define('TINY_API_RESPONSE_NOT_FOUND',             404);
define('TINY_API_RESPONSE_METHOD_NOT_ALLOWED',    405);
define('TINY_API_RESPONSE_NOT_ACCEPTABLE',        406);
define('TINY_API_RESPONSE_INTERNAL_SERVER_ERROR', 500);
define('TINY_API_RESPONSE_NOT_IMPLEMENTED',       501);
define('TINY_API_RESPONSE_SERVICE_UNAVAILABLE',   502);

// +------------------------------------------------------------+
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +------------------------+
// | tiny_api_Base_Response |
// +------------------------+
//

class tiny_api_Base_Response
{
    protected $code;
    protected $data;

    function __construct()
    {
        $this->code = TINY_API_RESPONSE_INTERNAL_SERVER_ERROR;
        $this->data = array('msg' => 'request cannot be handled');
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function get_code()
    {
        return $this->code;
    }

    final public function get_data()
    {
        return $this->data;
    }

    final public function set_data(array $data)
    {
        $this->data = $data;
        return $this;
    }
}

//
// +----------------------+
// | tiny_api_Response_Ok |
// +----------------------+
//

class tiny_api_Response_Ok
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_OK;
        $this->data = array('msg' => 'ok');
    }
}

//
// +---------------------------+
// | tiny_api_Response_Created |
// +---------------------------+
//

class tiny_api_Response_Created
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_CREATED;
        $this->data = array('msg' => 'created');
    }
}

//
// +----------------------------+
// | tiny_api_Response_Accepted |
// +----------------------------+
//

class tiny_api_Response_Accepted
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_ACCEPTED;
        $this->data = array('msg' => 'accepted');
    }
}

//
// +---------------------------------+
// | tiny_api_Response_Non_Auth_Info |
// +---------------------------------+
//

class tiny_api_Response_Non_Auth_Info
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_NON_AUTH_INFO;
        $this->data = array('msg' => 'non-authoritative information');
    }
}

//
// +------------------------------+
// | tiny_api_Response_No_Content |
// +------------------------------+
//

class tiny_api_Response_No_Content
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_NO_CONTENT;
        $this->data = array('msg' => 'no content');
    }
}

//
// +---------------------------------+
// | tiny_api_Response_Reset_Content |
// +---------------------------------+
//

class tiny_api_Response_Reset_Content
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_RESET_CONTENT;
        $this->data = array('msg' => 'reset content');
    }
}

//
// +-----------------------------------+
// | tiny_api_Response_Partial_Content |
// +-----------------------------------+
//

class tiny_api_Response_Partial_Content
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_PARTIAL_CONTENT;
        $this->data = array('msg' => 'partial content');
    }
}

//
// +-------------------------------------+
// | tiny_api_Response_Moved_Permanently |
// +-------------------------------------+
//

class tiny_api_Response_Moved_Permanently
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_MOVED_PERMANENTLY;
        $this->data = array('msg' => 'moved permanently');
    }
}

//
// +-------------------------+
// | tiny_api_Response_Found |
// +-------------------------+
//

class tiny_api_Response_Found
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_FOUND;
        $this->data = array('msg' => 'found');
    }
}

//
// +--------------------------------+
// | tiny_api_Response_Not_Modified |
// +--------------------------------+
//

class tiny_api_Response_Not_Modified
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_NOT_MODIFIED;
        $this->data = array('msg' => 'not modified');
    }
}

//
// +---------------------------------+
// | tiny_api_Response_Temp_Redirect |
// +---------------------------------+
//

class tiny_api_Response_Temp_Redirect
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_TEMP_REDIRECT;
        $this->data = array('msg' => 'temporary redirect');
    }
}

//
// +-------------------------------+
// | tiny_api_Response_Bad_Request |
// +-------------------------------+
//

class tiny_api_Response_Base_Request
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_BAD_REQUEST;
        $this->data = array('msg' => 'bad request');
    }
}

//
// +--------------------------------+
// | tiny_api_Response_Unauthorized |
// +--------------------------------+
//

class tiny_api_Response_Unauthorized
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_UNAUTHORIZED;
        $this->data = array('msg' => 'unauthorized');
    }
}

//
// +------------------------------------+
// | tiny_api_Response_Payment_Required |
// +------------------------------------+
//

class tiny_api_Response_Payment_Required
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_PAYMENT_REQUIRED;
        $this->data = array('msg' => 'payment required');
    }
}

//
// +-----------------------------+
// | tiny_api_Response_Forbidden |
// +-----------------------------+
//

class tiny_api_Response_Forbidden
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_FORBIDDEN;
        $this->data = array('msg' => 'forbidden');
    }
}

//
// +-----------------------------+
// | tiny_api_Response_Not_Found |
// +-----------------------------+
//

class tiny_api_Response_Not_Found
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_NOT_FOUND;
        $this->data = array('msg' => 'not found');
    }
}

//
// +--------------------------------------+
// | tiny_api_Response_Method_Not_Allowed |
// +--------------------------------------+
//

class tiny_api_Response_Method_Not_Allowed
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_METHOD_NOT_ALLOWED;
        $this->data = array('msg' => 'method not allowed');
    }
}

//
// +----------------------------------+
// | tiny_api_Response_Not_Acceptable |
// +----------------------------------+
//

class tiny_api_Response_Not_Acceptable
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_NOT_ACCEPTABLE;
        $this->data = array('msg' => 'not acceptable');
    }
}

//
// +-----------------------------------------+
// | tiny_api_Response_Internal_Server_Error |
// +-----------------------------------------+
//

class tiny_api_Response_Internal_Server_Error
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_INTERNAL_SERVER_ERROR;
        $this->data = array('msg' => 'internal server error');
    }
}

//
// +-----------------------------------+
// | tiny_api_Response_Not_Implemented |
// +-----------------------------------+
//

class tiny_api_Response_Not_Implemented
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_NOT_IMPLEMENTED;
        $this->data = array('msg' => 'not implemented');
    }
}

//
// +---------------------------------------+
// | tiny_api_Response_Service_Unavailable |
// +---------------------------------------+
//

class tiny_api_Response_Service_Unavailable
extends tiny_api_Base_Response
{
    function __construct()
    {
        parent::__construct();

        $this->code = TINY_API_RESPONSE_SERVICE_UNAVAILABLE;
        $this->data = array('msg' => 'service unavailable');
    }
}
?>
