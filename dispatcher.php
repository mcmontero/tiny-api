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

require_once 'base/handler.php';
require_once 'base/response.php';
require_once 'tiny-api-conf.php';

// +------------------------------------------------------------+
// | INSTRUCTIONS                                               |
// +------------------------------------------------------------+

//
// Perform superficial request verification.
//

@$temp = explode('/', $_SERVER[ 'REQUEST_URI' ]);
if (count($temp) < 2)
{
    error_log('URL scheme ('
              . $_SERVER[ 'REQUEST_URI' ]
              . ') is not that of tiny api');

    http_response_code(TINY_API_RESPONSE_INTERNAL_SERVER_ERROR);
    exit(1);
}

@list($junk, $version, $entity, $accessor) = $temp;
if (!preg_match('/^[0-9\.]+$/', $version))
{
    if ($version == 'favicon.ico' &&
        !is_null($__tiny_api_conf__[ 'favicon.ico redirect url' ]))
    {
        http_response_code(TINY_API_RESPONSE_MOVED_PERMANENTLY);
        header('Location: '
               . $__tiny_api_conf__[ 'favicon.ico redirect url'  ]);
        exit(0);
    }
    else
    {
        error_log('version number is incorrect ('
                  . $_SERVER[ 'REQUEST_URI' ]
                  . ')');

        http_response_code(TINY_API_RESPONSE_INTERNAL_SERVER_ERROR);
        exit(1);
    }
}

//
// Find and create the handler.
//

$include_file = "$version/$entity.php";
$class_name   = "tiny_api_$entity" . '_Handler';

require_once $include_file;

//
// Execute and respond.
//

$class    = new $class_name();
$response = $class->execute();

if (!($response instanceof tiny_api_Base_Response))
{
    error_log('response not instance of tiny_api_Base_Response');

    http_response_code(TINY_API_RESPONSE_INTERNAL_SERVER_ERROR);
    exit(1);
}

http_response_code($response->get_code());
json_encode($response->get_data());
exit(0);
?>
