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

require_once 'base/exception.php';
require_once 'base/handler.php';
require_once 'base/response.php';

// +------------------------------------------------------------+
// | INSTRUCTIONS                                               |
// +------------------------------------------------------------+

$__tiny_api_conf__ = array
(
    /**
     * Defines the underlying data store into which all entities are stored.
     *
     * Supported values include:
     *
     *  mysql
     *      configure "mysql connection data" below.
     *
     *  postgresql
     *      configure "postgres connection data" below.
     */
    'data store' =>
        'mysql',
        //'postgresql',

    /**
     * Special case handling via redirect for favicon.ico requests that hit
     * the tiny api dispatcher.  If this value is null, no redirection will
     * occur.
     */
    'favicon.ico redirect url' =>
        'http://google.com/images/google_favicon_128.png',

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
     * An array that maps a defined configuration name to the necessary
     * MySQL login credentials so that multiple database servers can be
     * used.  This includes the ability to read from a slave but write to a
     * master or distribute reads over sharded slaves.
     */
    'mysql connection data' => array
    (
        'local' => array('', '', ''),
    ),

    /**
     * The value provided here should match exactly what would normally
     * be passed to the PHP function pg_pconnect().
     */
    'postgresql connection data' => array
    (
        'local' => 'host=localhost port=5432 user=postgres password=abcd1234',
    ),

    /**
     * A list of schema names that the RDBMS builder should manage.  If the
     * RDBMS builder is in use you must provide values here.
     */
    'rdbms builder schemas' => array
    (
    ),

    /**
     * The RDBMS builder can compile the reference tables created with
     * tiny_api_Ref_Table into PHP definitions so that no database
     * interactions are required to interact with them.  If this value is
     * null, reference definitions will not be compiled.
     */
    'reference definition file' =>
        null,
);
?>
