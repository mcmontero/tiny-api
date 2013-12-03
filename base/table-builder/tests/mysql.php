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
                'the column "def" already exists',
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
            $this->assertEquals('the engine "def" is invalid', $e->get_text());
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
                'the table cannot be defined because it has no columns',
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

    function test_table_calling_set_attribute()
    {
        ob_start();
?>
create table abc
(
    def int,
    ghi int unsigned zerofill
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('abc')
                ->int('def')
                ->int('ghi', null, true, true)
                ->get_definition());
    }

    function test_table_help_attribute_methods()
    {
        ob_start();
?>
create table abc
(
    def int,
    ghi int unique,
    jkl int auto_increment,
    mno int default 123
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('abc')
                ->int('def')
                ->int('ghi')
                    ->uk()
                ->int('jkl')
                    ->ai()
                ->int('mno')
                    ->def(123)
                ->get_definition());
    }

    function test_table_active_column_is_primary_key()
    {
        ob_start();
?>
create table abc
(
    def int primary key
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('abc')
                ->int('def')
                    ->pk()
                ->get_definition());
    }

    function test_temporary_table()
    {
        ob_start();
?>
create temporary table abc
(
    id bigint unsigned not null auto_increment unique
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('abc')
                ->temp()
                ->id()
                ->get_definition());
    }

    function test_table_composite_primary_key_exceptions()
    {
        try
        {
            tiny_api_Table::make('abc')
                ->int('def')
                ->pk(array('def', 'ghi'))
                ->get_definition();

            $this->fail('Was able to get the definition for a table even '
                        . 'though one of the columns in the primary key '
                        . 'did not exist.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'column "ghi" cannot be used in primary key because it has not '
                . 'been defined',
                $e->get_text());
        }
    }

    function test_table_composite_primary_key()
    {
        ob_start();
?>
create table abc
(
    def int,
    ghi int,
    primary key abc_pk (def, ghi)
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('abc')
                ->int('def')
                ->int('ghi')
                ->pk(array('def', 'ghi'))
                ->get_definition());
    }

    function test_table_composite_unique_key_exceptions()
    {
        try
        {
            tiny_api_Table::make('abc')
                ->int('def')
                ->uk(array('def', 'ghi'))
                ->get_definition();

            $this->fail('Was able to get the definition for a table even '
                        . 'though one of the columns in a unique key did not '
                        . 'exist.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'column "ghi" cannot be used in unique key because it has not '
                . 'been defined',
                $e->get_text());
        }
    }

    function test_table_one_composite_unique_key()
    {
        ob_start();
?>
create table abc
(
    def int,
    ghi int,
    jkl int,
    unique key abc_0_uk (def, ghi)
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('abc')
                ->int('def')
                ->int('ghi')
                ->int('jkl')
                ->uk(array('def', 'ghi'))
                ->get_definition());
    }

    function test_table_multiple_composite_unique_keys()
    {
        ob_start();
?>
create table abc
(
    def int,
    ghi int,
    jkl int,
    unique key abc_0_uk (def, ghi),
    unique key abc_1_uk (ghi, jkl)
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('abc')
                ->int('def')
                ->int('ghi')
                ->int('jkl')
                ->uk(array('def', 'ghi'))
                ->uk(array('ghi', 'jkl'))
                ->get_definition());
    }
}
?>
