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
// | PUBLIC FUNCTIONS                                           |
// +------------------------------------------------------------+

function env_local()
{
    return _tiny_api_context_env_matches(tiny_api_Context::LOCAL);
}

function env_staging()
{
    return _tiny_api_context_env_matches(tiny_api_Context::STAGING);
}

function env_qa()
{
    return _tiny_api_context_env_matches(tiny_api_Context::QA);
}

function env_prod()
{
    return _tiny_api_context_env_matches(tiny_api_Context::PRODUCTION);
}

function env_not_prod()
{
    return !_tiny_api_context_env_matches(tiny_api_Context::PRODUCTION);
}

function env_cli()
{
    return tiny_api_Context::get_instance()->is_cli();
}

function env_web()
{
    return tiny_api_Context::get_instance()->is_web();
}

function env_unit_test()
{
    return tiny_api_Context::get_instance()->is_unit_test();
}

// +------------------------------------------------------------+
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +------------------+
// | tiny_api_Context |
// +------------------+
//

class tiny_api_Context
{
    const LOCAL      = 'local';
    const STAGING    = 'staging';
    const QA         = 'qa';
    const PRODUCTION = 'production';

    private static $instance;
    private $server_env;
    private $is_cli;
    private $is_web;
    private $is_unit_test;

    function __construct()
    {
        $this->reset();
    }

    static function get_instance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new tiny_api_Context();
        }

        return self::$instance;
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function get_server_env()
    {
        if (is_null($this->server_env))
        {
            $this->server_env = @getenv('APP_SERVER_ENV');
            if (empty($this->server_env))
            {
                throw new tiny_api_Context_Exception(
                            'could not find environment variable called '
                            . '"APP_SERVER_ENV"');
            }

            if (!array_key_exists($this->server_env,
                                  array(self::LOCAL      => 1,
                                        self::STAGING    => 2,
                                        self::QA         => 3,
                                        self::PRODUCTION => 4)))
            {
                throw new tiny_api_Context_Exception(
                            'application server environment "'
                            . $this->server_env
                            . '" is not valid');
            }
        }

        return $this->server_env;
    }

    final public function is_cli()
    {
        return $this->is_cli;
    }

    final public function is_unit_test()
    {
        return $this->is_unit_test;
    }

    final public function is_web()
    {
        return $this->is_web;
    }

    final public function set_unit_test()
    {
        $this->is_unit_test = true;
        return $this;
    }

    final public function reset()
    {
        $this->server_env   = null;
        $this->is_cli       = false;
        $this->is_web       = false;
        $this->is_unit_test = false;

        if (!empty($_SERVER[ 'DOCUMENT_ROOT' ]))
        {
            $this->is_web = true;
        }
        else
        {
            $this->is_cli = true;
        }
    }
}

// +------------------------------------------------------------+
// | PRIVATE FUNCTIONS                                          |
// +------------------------------------------------------------+

function _tiny_api_context_env_matches($env)
{
    return tiny_api_Context::get_instance()->get_server_env() == $env;
}
?>
