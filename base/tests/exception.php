<?php

// +------------------------------------------------------------+
// | INCLUDES                                                   |
// +------------------------------------------------------------+

require_once 'base/exception.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class base_Test_Exception
extends PHPUnit_Framework_TestCase
{
    function test_getting_text_from_exception()
    {
        $e = new tiny_api_Exception('Hello World!');

        $this->assertEquals('Hello World!', $e->get_text());
    }
}
?>
