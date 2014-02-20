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
// | INSTRUCTIONS                                               |
// +------------------------------------------------------------+

//
// Determine the server variable to use for the URL.
//

$url = array_key_exists('REDIRECT_URL', $_SERVER) ?
        $_SERVER[ 'REDIRECT_URL' ] : $_SERVER[ 'SCRIPT_URL' ];

//
// Handle /favicon.ico requests.
//

_tiny_api_dispatcher_favicon($url);

//
// Perform superficial request verification.
//

@$temp = explode('/', $url);
if (count($temp) < 3)
{
    error_log(new tiny_api_Dispatcher_Exception(
                    'URL scheme ['
                    . $_SERVER[ 'SCRIPT_URL' ]
                    . '] is not that of tiny api'));

    http_response_code(TINY_API_RESPONSE_INTERNAL_SERVER_ERROR);
    exit(1);
}

//
// Determine the components of this request (version number and entity, and
// possibly, the accessor and ID).
//

$version  = $temp[ 1 ];
$entity   = $temp[ 2 ];
$accessor = array_slice($temp, 3, -1);

$last = end($temp);
if ($last == $entity)
{
    $last = null;
}

list($accessor, $id) = _tiny_api_dispatcher_accessor_and_id($accessor, $last);

//
// Route and respond.
//

_tiny_api_dispatcher_route_and_respond($version, $entity, $accessor, $id);

exit(0);

// +------------------------------------------------------------+
// | PRIVATE FUNCTIONS                                          |
// +------------------------------------------------------------+

/**
 * Determines the "depth" of the request to create the accessor and handles
 * the last element of the request as either an integer ID value or part of
 * the accessor.
 */
function _tiny_api_dispatcher_accessor_and_id($accessor, $last)
{
    $id = null;
    if (preg_match('/^[0-9]+$/', $last))
    {
        $id = intval($last);
    }
    else if (!empty($last))
    {
        $accessor[] = $last;
    }

    return array((empty($accessor) ? null : $accessor), $id);
}

/**
 * Determines if the request being made is for favicon.ico and handles the
 * requests if tiny api is configured to do so.
 */
function _tiny_api_dispatcher_favicon($url)
{
    global $__tiny_api_conf__;

    if ($url == '/favicon.ico')
    {
        if (!is_null($__tiny_api_conf__[ 'favicon.ico redirect url' ]))
        {
            http_response_code(TINY_API_RESPONSE_MOVED_PERMANENTLY);
            header('Location: '
                   . $__tiny_api_conf__[ 'favicon.ico redirect url'  ]);
            exit(0);
        }
        else
        {
            error_log(new tiny_api_Dispatcher_Exception(
                            'version number ['
                            . $_SERVER[ 'SCRIPT_URL' ]
                            . '] is incorrect'));

            http_response_code(TINY_API_RESPONSE_INTERNAL_SERVER_ERROR);
            exit(1);
        }
    }
}

function _tiny_api_dispatcher_route_and_respond($version,
                                                $entity,
                                                $accessor,
                                                $id)
{
    //
    // Prepare data for routing.
    //

    if (!is_null($accessor))
    {
        $accessor = implode('_', $accessor);
    }

    //
    // Include the PHP file that contains the handler.
    //

    @include_once "$version/" . preg_replace('/_/', '-', $entity) . ".php";
    if (isset($php_errormsg))
    {
        http_response_code(TINY_API_RESPONSE_NOT_FOUND);
        exit(1);
    }

    //
    // Create and execute the handler and response.
    //

    $class_name = "tiny_api_$entity" . '_Handler';
    $class      = new $class_name();
    $response   = $class->execute($accessor, $id);

    if (!($response instanceof tiny_api_Base_Response))
    {
        error_log(
            new tiny_api_Dispatcher_Exception(
                    'response not instance of tiny_api_Base_Response'));

        http_response_code(TINY_API_RESPONSE_INTERNAL_SERVER_ERROR);
        exit(1);
    }

    header('Content-Type: ' . $class->get_content_type());
    http_response_code($response->get_code());
    $response = json_encode($response->get_data());

    print ($class->response_as_jsonp() ?
            "__jsonp_handler__($response);" : $response);
}
?>
