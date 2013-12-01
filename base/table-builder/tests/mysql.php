<?php

// +------------------------------------------------------------+
// | INCLUDES                                                   |
// +------------------------------------------------------------+

require_once 'base/table-builder/mysql.php';

// +------------------------------------------------------------+
// | TESTS                                                      |
// +------------------------------------------------------------+

class table_Builder_Test_Mysql
extends PHPUnit_Framework_TestCase
{
    function test_mysql_table_add_column_dupe()
    {
        try
        {
            tiny_api_Table::make('abc')
                ->bit('def')
                ->bint('def');

            $this->fail('Was able to add two columns with the same name.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'The column "def" already exists.',
                $e->get_text());
        }
    }

    function test_mysql_numeric_column_bit()
    {
        $this->assertEquals(
            'abcdef bit unsigned zerofill not null '
            . 'auto_increment unique default 1',
            _tiny_api_Mysql_Numeric_Column::make('abcdef')
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_BIT)
                ->default_value('1')
                ->auto_increment()
                ->not_null()
                ->unique()
                ->unsigned()
                ->zero_fill()
                ->get_definition());
    }

    function test_mysql_numeric_column_bint()
    {
        $this->assertEquals(
            'abcdef bigint(13) unsigned zerofill not null '
            . 'auto_increment unique default 1',
            _tiny_api_Mysql_Numeric_Column::make('abcdef')
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_BIGINT, 13)
                ->default_value('1')
                ->auto_increment()
                ->not_null()
                ->unique()
                ->unsigned()
                ->zero_fill()
                ->get_definition());
    }

    function test_mysql_numeric_column_mint()
    {
        $this->assertEquals(
            'abcdef mediumint(13) unsigned zerofill not null '
            . 'auto_increment unique default 1',
            _tiny_api_Mysql_Numeric_Column::make('abcdef')
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_MEDIUMINT,
                               13)
                ->default_value('1')
                ->auto_increment()
                ->not_null()
                ->unique()
                ->unsigned()
                ->zero_fill()
                ->get_definition());
    }

    function test_mysql_numeric_column_int()
    {
        $this->assertEquals(
            'abcdef int(13) unsigned zerofill not null '
            . 'auto_increment unique default 1',
            _tiny_api_Mysql_Numeric_Column::make('abcdef')
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_INT, 13)
                ->default_value('1')
                ->auto_increment()
                ->not_null()
                ->unique()
                ->unsigned()
                ->zero_fill()
                ->get_definition());
    }

    function test_mysql_numeric_column_sint()
    {
        $this->assertEquals(
            'abcdef smallint(13) unsigned zerofill not null '
            . 'auto_increment unique default 1',
            _tiny_api_Mysql_Numeric_Column::make('abcdef')
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_SMALLINT,
                               13)
                ->default_value('1')
                ->auto_increment()
                ->not_null()
                ->unique()
                ->unsigned()
                ->zero_fill()
                ->get_definition());
    }

    function test_mysql_numeric_column_tint()
    {
        $this->assertEquals(
            'abcdef tinyint(13) unsigned zerofill not null '
            . 'auto_increment unique default 1',
            _tiny_api_Mysql_Numeric_Column::make('abcdef')
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_TINYINT, 13)
                ->default_value('1')
                ->auto_increment()
                ->not_null()
                ->unique()
                ->unsigned()
                ->zero_fill()
                ->get_definition());
    }

    function test_mysql_numeric_column_dec()
    {
        $this->assertEquals(
            'abcdef decimal(12, 34) unsigned zerofill not null '
            . 'auto_increment unique default 1',
            _tiny_api_Mysql_Numeric_Column::make('abcdef')
                ->decimal_type(_tiny_api_Mysql_Numeric_Column::TYPE_DECIMAL,
                               12, 34)
                ->default_value('1')
                ->auto_increment()
                ->not_null()
                ->unique()
                ->unsigned()
                ->zero_fill()
                ->get_definition());
    }

    function test_mysql_numeric_column_float()
    {
        $this->assertEquals(
            'abcdef float(12) unsigned zerofill not null '
            . 'auto_increment unique default 1',
            _tiny_api_Mysql_Numeric_Column::make('abcdef')
                ->float_type(12)
                ->default_value('1')
                ->auto_increment()
                ->not_null()
                ->unique()
                ->unsigned()
                ->zero_fill()
                ->get_definition());
    }

    function test_table_engine_exceptions()
    {
        try
        {
            tiny_api_Table::make('abc')->engine('def');

            $this->fail('Was able to set the engine to an invalid value.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals('The engine "def" is invalid.', $e->get_text());
        }
    }

    function test_table_get_definition_exceptions()
    {
        try
        {
            tiny_api_Table::make('abc')->get_definition();

            $this->fail('Was able to get table definition even though no '
                        . 'columns were provided.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'The table cannot be defined because it has no columns.',
                $e->get_text());
        }
    }

    function test_table_simple()
    {
        ob_start();
?>
create table abc
(
    id bigint unsigned not null auto_increment unique
) engine = innodb;
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('abc')
                ->engine('InnoDB')
                ->id()
                ->get_definition());
    }

    function test_table_multi_numeric_columns()
    {
        ob_start();
?>
create table abc
(
    id bigint unsigned not null auto_increment unique,
    def tinyint(1),
    ghi float(12)
) engine = myisam;
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('abc')
                ->engine('MyISAM')
                ->id()
                ->bool('def')
                ->float('ghi', 12)
                ->get_definition());
    }
}
?>
