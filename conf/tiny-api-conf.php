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

// +------------------------------------------------------------+
// | INSTRUCTIONS                                               |
// +------------------------------------------------------------+

$__tiny_api_conf__ = array(

    /**
     * Defines the underlying data store into which all entities are stored.
     *
     * Supported values include:
     *
     *  mysql (myisam)
     *      configure "mysql.default_host", "mysql.default_user", and
     *      "mysql.default_password" in php.ini
     *
     *  postgresql
     *      configure "postgresql connection string" located in this file
     */
    'data store' =>
        'mysql (myisam)',
        //'postgresql',

    /**
     * Special case handling via redirect for favicon.ico requests that hit
     * the tiny api dispatcher.  If this value is null, no redirection will
     * occur.
     */
    'favicon.ico redirect url' =>
        'http://google.com//images/google_favicon_128.png',

    /**
     * An array of Memcached servers to use for caching.  The array should
     * be in the following format:
     *
     *  array('[IP address 1]:[Port 1]',
     *        '[IP address 2]:[Port 2]',
     *        ...
     *        '[IP address N]:[Port N])
     */
    'memcached servers' =>
        array('127.0.0.1:11211'),

    /**
     * The value provided here should match exactly what would normally
     * be passed to the PHP function pg_pconnect().
     */
    'postgresql connection string' =>
        'host=localhost port=5432 user=postgres password=abcd1234',

);
?>