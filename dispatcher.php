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
    exit(1);
}

@list($junk, $version, $entity, $accessor) = $temp;
if (!preg_match('/^[0-9\.]+$/', $version))
{
    error_log("version number ($version) is incorrect");
    exit(1);
}

//
// Find and create the handler.
//

$include_file = "$version/$entity.php";
$class_name   = "tiny_api_$entity" . '_handler';

require_once $include_file;

//
// Execute and respond.
//

$class    = new $class_name();
$response = $class->execute();
json_encode($response);
exit(0);
?>
