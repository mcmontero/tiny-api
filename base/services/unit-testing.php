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

require_once 'PHPUnit/Autoload.php';

// +------------------------------------------------------------+
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +------------------------------------------+
// | PHPUnit_Framework_Transactional_TestCase |
// +------------------------------------------+
//

class PHPUnit_Framework_Transactional_TestCase
extends PHPUnit_Framework_TestCase
{
    function __construct()
    {
        parent::__construct();
    }

    // +-------------------+
    // | Protected Methods |
    // +-------------------+

    protected function set_up() {}

    final protected function setUp()
    {
        dsh()->begin_transaction();

        $this->set_up();
    }

    protected function tear_down() {}

    final protected function tearDown()
    {
        $this->tear_down();

        dsh()->rollback();
    }
}

//
// +----------------------------+
// | tiny_api_Unit_Test_Manager |
// +----------------------------+
//

class tiny_api_Unit_Test_Manager
{
    private $cli;
    private $total_run_time;
    private $total_tests;
    private $enable_tap;
    private $enable_stop_on_failure;
    private $phpunit_bootstrap;

    function __construct(tiny_api_Cli $cli)
    {
        $this->cli                    = $cli;
        $this->total_run_time         = 0;
        $this->total_tests            = 0;
        $this->enable_tap             = true;
        $this->enable_stop_on_failure = true;
        $this->phpunit_bootstrap      = $this->configure_phpunit_bootstrap();
    }

    static function make(tiny_api_Cli $cli)
    {
        return new self($cli);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function disable_stop_on_failure()
    {
        $this->enable_stop_on_failure = false;
        return $this;
    }

    final public function disable_tap()
    {
        $this->enable_tap = false;
        return $this;
    }

    final public function execute($files)
    {
        foreach ($files as $file)
        {
            $this->cli->notice($file);

            $file_run_time_start = microtime(true);
            $num_file_tests      = 0;
            $output              = null;

            exec("/usr/bin/phpunit "
                 . $this->get_phpunit_options()
                 . " $file 2>&1",
                 $output,
                 $retval);
            if ($retval)
            {
                if ($this->enable_tap)
                {
                    if (!_tiny_api_pretty_print_string_equality_failure(
                                    $this->cli, $output))
                    {
                        foreach ($output as $line)
                        {
                            print "    $line\n";

                            if (preg_match('/^not ok.*::(.*?)$/',
                                           $line, $matches))
                            {
                                print "\n====================================="
                                      . "====================================="
                                      . "====\n";

                                exec('/usr/bin/phpunit '
                                     . '--filter ' . $matches[ 1 ] . ' '
                                     . $file . ' 2>&1',
                                     $error, $retval);

                                foreach ($error as $line)
                                {
                                    print "$line\n";
                                }

                                print "======================================="
                                      . "====================================="
                                      . "==\n";

                                exit(1);
                            }
                        }
                    }
                }
                else
                {
                    $num_output = count($output);
                    for ($i = 1; $i < $num_output; $i++)
                    {
                        print '    ' . $output[ $i ] . "\n";
                    }
                }

                exit(1);
            }
            $file_run_time_stop    = microtime(true);
            $this->total_run_time += ($file_run_time_stop -
                                      $file_run_time_start);

            $num_output = count($output);
            for ($i = 1; $i < $num_output; $i++)
            {
                if ($this->enable_tap)
                {
                    if (!preg_match('/^(\d+)\.\.(\d+)$/',
                        $output[ $i ], $matches))
                    {
                        print '  ' . $output[ $i ] . "\n";
                    }
                    else
                    {
                        $this->total_tests += $matches[ 2 ];
                        $num_file_tests    += $matches[ 2 ];
                    }
                }
                else
                {
                    if (preg_match('/OK \((\d+) tests?, /',
                                   $output[ $i ], $matches))
                    {
                        $this->total_tests = $matches[ 1 ];
                        $num_file_tests    = $matches[ 1 ];
                    }

                    print '  ' . $output[ $i ] . "\n";
                }
            }

            print '  num tests: '
                  . number_format($num_file_tests)
                  . ' | '
                  . 'elapsed: '
                  . ($file_run_time_stop - $file_run_time_start)
                  . "\n\n";
        }

        return $this;
    }

    final public function print_summary()
    {
        $this->cli->notice('  Total number of tests executed: '
                           . number_format($this->total_tests));
        $this->cli->notice('Total elapsed time for all tests: '
                           . $this->total_run_time);
        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function configure_phpunit_bootstrap()
    {
        global $__tiny_api_conf__;

        if (!array_key_exists('unit test bootstrap file', $__tiny_api_conf__) ||
            empty($__tiny_api_conf__[ 'unit test bootstrap file' ]))
        {
            return null;
        }

        $phpunit_bootstrap =
            stream_resolve_include_path(
                $__tiny_api_conf__[ 'unit test bootstrap file' ]);
        if ($phpunit_bootstrap == false)
        {
            throw new tiny_api_Unit_Test_Exception(
                        'could not find unit test bootstrap file "'
                        . $__tiny_api_conf__[ 'unit test bootstrap file' ]
                        . '"');
        }

        return $phpunit_bootstrap;
    }

    private function get_phpunit_options()
    {
        $options = array();

        if ($this->enable_tap)
        {
            $options[] = '--tap';
        }

        if ($this->enable_stop_on_failure)
        {
            $options[] = '--stop-on-failure';
        }

        if (!is_null($this->phpunit_bootstrap))
        {
            $options[] = '--bootstrap ' . $this->phpunit_bootstrap;
        }

        return implode(' ', $options);
    }
}

// +------------------------------------------------------------+
// | PRIVATE FUNCTIONS                                          |
// +------------------------------------------------------------+

function _tiny_api_pretty_print_string_equality_failure(tiny_api_Cli $cli,
                                                        $output)
{
    foreach ($output as $index => $line)
    {
        if (preg_match('/Failed asserting that two strings are equal/', $line))
        {
            $failed_unit_test = $output[ $index - 2 ];

            $got =
                explode(
                    "\n",
                    preg_replace(
                        '/\'|"$/msi', '',
                        preg_replace(
                            '/^[ ]+got: [\'|"]?/', '',
                            preg_replace(
                                '/\\\n/', "\n",
                                $output[ $index + 3 ]))));
            $expected =
                explode(
                    "\n",
                    preg_replace(
                        '/\'|"$/msi', '',
                        preg_replace(
                            '/^[ ]+expected: [\'|"]?/', '',
                            preg_replace(
                                '/\\\n/', "\n",
                                $output[ $index + 4 ]))));

            // Why is this code here?  It's possible that either got or
            // expected has more lines than the other.  In that case, we want
            // to drive the comparison why whichever one has more lines.
            $source = $expected;
            $target = $got;
            if (count($got) > count($expected))
            {
                $source = $got;
                $target = $expected;
            }

            print "  $failed_unit_test\n\n";
            print "    message: 'Failed asserting that two strings are "
                  . "equal.'\n";
            print "    severity: fail\n\n";

            foreach ($source as $index => $line)
            {
                if (!array_key_exists($index, $target))
                {
                    $cli->notice("!=  $line\n        line did not exist", 1);
                }
                else if ($line == $target[ $index ])
                {
                    $cli->notice("==  $line\n        " . $target[ $index ], 1);
                }
                else
                {
                    $cli->notice("!=  $line\n        " . $target[ $index ], 1);
                }
            }

            return true;
        }
    }

    return false;
}
?>
