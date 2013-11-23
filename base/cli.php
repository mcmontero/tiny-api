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

// +------------------------------------------------------------+
// | PUBLIC FUNCTIONS                                           |
// +------------------------------------------------------------+

function tiny_api_cli_draw_header($title, $width = 79)
{
?>
// +<?= tiny_api_cli_repeat_char('-', $width - 5) ?>+
<?
    $header = "// | $title";
    $header_length = strlen($header);

    // -2 because we're adding a space before the text and after.
    for ($i = 0; $i < ($width - 2 - $header_length); $i++)
    {
        $header .= " ";
    }

    print "$header |\n";
?>
// +<?= tiny_api_cli_repeat_char('-', $width - 5) ?>+
<?
}

function tiny_api_cli_main(tiny_api_Cli_Conf $conf, $main)
{
    if (!function_exists($main))
    {
        throw new tiny_api_Cli_Exception(
            "main function \"$main\" does not exist");
    }

    try
    {
        call_user_func($main, tiny_api_Cli::make($conf));
    }
    catch (Exception $e)
    {
        error_log($e->__toString());
        throw $e;
    }
}

// +------------------------------------------------------------+
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +--------------+
// | tiny_api_Cli |
// +--------------+
//

class tiny_api_Cli_Conf
{
    private $options;
    private $description;
    private $args;

    function __construct()
    {
        $this->options = array();
        $this->args    = array();
    }

    static function make()
    {
        return new self();
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function add_description($description)
    {
        $this->description = $description;
        return $this;
    }

    final public function add_option($name, $description, $required)
    {
        if (!preg_match('/^--/', $name))
        {
            $name = "--$name";
        }

        $this->options[ $name ] = array($description, (bool)$required);
        return $this;
    }

    final public function get_arg($name)
    {
        if (!array_key_exists($name, $this->options))
        {
            throw new tiny_api_Cli_Exception(
                "an option named \"$name\" has not been configured");
        }

        return array_key_exists($name, $this->args) ?
                $this->args[ $name ] : null;
    }

    final public function parse_options()
    {
        global $argv;

        $num_argv = count($argv);
        for ($i = 1; $i < $num_argv; $i++)
        {
            @list($option, $value) = explode('=', $argv[ $i ]);
            if ($option == '--help')
            {
                $this->usage();
            }

            $this->args[ $option ] = (empty($value) ? true : $value);
        }

        foreach ($this->options as $arg => $conf)
        {
            list($description, $required) = $conf;

            if ($required && !array_key_exists($arg, $this->args))
            {
                $this->usage();
            }
        }
    }

    final public function unlimited_memory()
    {
        ini_set('memory_limit', -1);
        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function format_description($description,
                                        $max_length,
                                        $prepend = '')
    {
        $words = explode(' ', $description);
        $len   = strlen($prepend);
        $text  = $prepend;
        foreach ($words as $word)
        {
            if (($len + strlen($word)) > $max_length)
            {
                $text .= "\n$prepend";
                $len   = 0;
            }

            $text .= "$word ";
            $len  += strlen($word);
        }

        return $text;
    }

    private function usage()
    {
        global $argv;

        $this->options[ '--help' ] = array('Get help using this CLI.', false);

        print"\n";
        tiny_api_cli_draw_header('usage: ' . basename($argv[ 0 ]));
        print "\n";

        if (!empty($this->description))
        {
            print $this->format_description($this->description, 60, '    ');
            print "\n\n";
        }


        foreach ($this->options as $arg => $conf)
        {
            list($description, $required) = $conf;

            printf("%s%-30s%-10s%-33s\n\n",
                   '    ',
                   $arg,
                   ($required ? 'required' : ''),
                   $this->format_description($description, 28));
        }

        exit(0);
    }
}

//
// +--------------+
// | tiny_api_Cli |
// +--------------+
//

class tiny_api_Cli
{
    const STATUS_OK    = 1;
    const STATUS_WARN  = 2;
    const STATUS_ERROR = 3;

    private $status_id;
    private $conf;
    private $flock;
    private $pid_lock;
    private $pid_lock_running;
    private $started;
    private $enable_status;

    function __construct(tiny_api_Cli_Conf $conf)
    {
        $this->status_id     = self::STATUS_OK;
        $this->conf          = $conf;
        $this->started       = time();
        $this->enable_status = false;

        $this->conf->parse_options();
        $this->pid_lock();

        $this->enable_status = true;
    }

    function __destruct()
    {
        if ($this->enable_status)
        {
            $this->status();
        }

        @unlink($this->pid_lock);
        @unlink($this->pid_lock_running);
    }

    static function make(tiny_api_Cli_Conf $conf)
    {
        return new self($conf);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function cli_exit()
    {
        exit(($this->status_id == self::STATUS_OK ? 0 : 1));
    }

    final public function error($message, $indent = null)
    {
        $this->set_status_id(self::STATUS_ERROR);

        $this->print_message($message, '!', $indent);
        return $this;
    }

    final public function header($title)
    {
        print "\n";
        tiny_api_cli_draw_header($title);
        print "\n";
    }

    final public function linebreak()
    {
        print "\n";
    }

    final public function notice($message, $indent = null)
    {
        $this->print_message($message, '+', $indent);
        return $this;
    }

    final public function status()
    {
        $elapsed   = time() - $this->started;
        $indicator = '';
        $message   = '';
        switch ($this->status_id)
        {
            case self::STATUS_OK:
                $indicator = '+';
                $message   = 'successfully';
                break;

            case self::STATUS_WARN:
                $indicator = '*';
                $message   = 'with warnings';
                break;

            case self::STATUS_ERROR:
                $indicator = '!';
                $message   = 'with errors';
                break;
        }

        print "\n$indicator Execution completed $message in "
              . number_format(substr($elapsed, 0, 5))
              . "s!\n\n";
    }

    final public function time_marker($num_iterations = null)
    {
        $this->notice('----- Marker '
                      . (!is_null($num_iterations)? "$num_iterations " : '')
                      . '['
                      . date('M d, Y @H:i:s')
                      . ']');
    }

    final public function warn($message, $indent = null)
    {
        $this->set_status_id(self::STATUS_WARN);

        $this->print_message($message, '*', $indent);
        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function pid_lock()
    {
        global $argv;

        $params                 = implode(',', array_slice($argv, 1));
        $pid_lock_base_dir      = '/var/run/cli';
        $pid_lock_base_name     = "$pid_lock_base_dir/"
                                  . basename($argv[ 0 ])
                                  . (!empty($params) ?
                                        '-'
                                        . preg_replace('/[^A-Za-z0-9_]/',
                                                       '_', $params) :
                                        '');
        $this->pid_lock         = "$pid_lock_base_name.pid_lock";
        $this->pid_lock_running = "$pid_lock_base_name.running";

        if (!is_dir($pid_lock_base_dir) &&
            !mkdir($pid_lock_base_dir, 0755, true))
        {
            throw new tiny_api_Cli_Exception(
                'could not create PID lock base directory '
                . "\"$pid_lock_base_dir\"");
        }

        $this->flock = fopen($this->pid_lock, 'w');
        if (!$this->flock || !flock($this->flock, LOCK_EX + LOCK_NB))
        {
            $this->pid_lock_failed();
        }

        // Write the active PID into the lock file for reference.
        fwrite($this->flock, getmypid() . "\n");
        fflush($this->flock);

        // The PID lock file (above) can be overwritten by a second process
        // that attempts to run even though a lock is already being held.  In
        // that case, create a permanent file that will not be overwritten in
        // the same fashion.
        file_put_contents($this->pid_lock_running, getmypid() . "\n");
    }

    private function pid_lock_failed()
    {
        $this->enable_status = false;

        print "\n* Process is already running!\n";
        print "* Could not acquire PID lock on:\n    "
              . $this->pid_lock
              . "\n";
        print "* The lock is held by PID "
              . trim(file_get_contents($this->pid_lock_running))
              . ".\n\n";

        exit(0);
    }

    private function print_message($message, $char, $indent = null)
    {
        if (!is_null($indent))
        {
            print tiny_api_cli_repeat_char(' ', ($indent * 4));
            print "$message\n";
        }
        else
        {
            print "$char $message\n";
        }
    }

    private function set_status_id($status_id)
    {
        if ($status_id > $this->status_id)
        {
            $this->status_id = $status_id;
        }
    }
}

// +------------------------------------------------------------+
// | PRIVATE FUNCTIONS                                          |
// +------------------------------------------------------------+

function tiny_api_cli_repeat_char($char, $num_times)
{
    if (empty($char) || is_null($char) || $num_times <= 0)
    {
        return null;
    }

    $string = '';
    for ($i = 0; $i < $num_times; $i++)
    {
        $string .= $char;
    }

    return $string;
}
?>
