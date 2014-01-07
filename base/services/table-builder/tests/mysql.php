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

require_once 'base/services/table-builder/mysql.php';

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
            tiny_api_Table::make('db', 'abc')
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
            . 'auto_increment unique default \'1\'',
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
            . 'auto_increment unique default \'1\'',
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
            . 'auto_increment unique default \'1\'',
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
            . 'auto_increment unique default \'1\'',
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
            . 'auto_increment unique default \'1\'',
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
            . 'auto_increment unique default \'1\'',
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
            . 'auto_increment unique default \'1\'',
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
            . 'auto_increment unique default \'1\'',
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
            tiny_api_Table::make('db', 'abc')->engine('def');

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
            tiny_api_Table::make('db', 'abc')->get_definition();

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
            tiny_api_Table::make('db', 'abc')
                ->engine('InnoDB')
                ->id('id', true, true)
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
            tiny_api_Table::make('db', 'abc')
                ->engine('MyISAM')
                ->id('id', true, true)
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
            tiny_api_Table::make('db', 'abc')
                ->int('def')
                ->int('ghi', null, false, true, true)
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
    mno int default '123'
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('db', 'abc')
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
            tiny_api_Table::make('db', 'abc')
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
            tiny_api_Table::make('db', 'abc')
                ->temp()
                ->id('id', true, true)
                ->get_definition());
    }

    function test_table_composite_primary_key_exceptions()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')
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
            tiny_api_Table::make('db', 'abc')
                ->int('def')
                ->int('ghi')
                ->pk(array('def', 'ghi'))
                ->get_definition());
    }

    function test_table_composite_unique_key_exceptions()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')
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
            tiny_api_Table::make('db', 'abc')
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
            tiny_api_Table::make('db', 'abc')
                ->int('def')
                ->int('ghi')
                ->int('jkl')
                ->uk(array('def', 'ghi'))
                ->uk(array('ghi', 'jkl'))
                ->get_definition());
    }

    function test_date_time_column_date_time_type_exception()
    {
        try
        {
            _tiny_api_Mysql_Date_Time_Column::make('abc')
                ->date_time_type(-1);

            $this->fail('Was able to set a date time type even though the '
                        . 'type ID provided was invalid.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals('the type ID provided was invalid',
                                $e->get_text());
        }
    }

    function test_date_time_column_date()
    {
        $this->assertEquals(
            'abcdef date not null unique default \'1\'',
            _tiny_api_Mysql_Date_Time_Column::make('abcdef')
                ->date_time_type(_tiny_api_Mysql_Date_Time_Column::TYPE_DATE)
                ->default_value('1')
                ->not_null()
                ->unique()
                ->get_definition());
    }

    function test_date_time_column_datetime()
    {
        $this->assertEquals(
            'abcdef datetime not null unique default \'1\'',
            _tiny_api_Mysql_Date_Time_Column::make('abcdef')
                ->date_time_type(
                    _tiny_api_Mysql_Date_Time_Column::TYPE_DATETIME)
                ->default_value('1')
                ->not_null()
                ->unique()
                ->get_definition());
    }

    function test_date_time_column_timestamp()
    {
        $this->assertEquals(
            'abcdef timestamp not null unique default \'1\'',
            _tiny_api_Mysql_Date_Time_Column::make('abcdef')
                ->date_time_type(
                    _tiny_api_Mysql_Date_Time_Column::TYPE_TIMESTAMP)
                ->default_value('1')
                ->not_null()
                ->unique()
                ->get_definition());
    }

    function test_date_time_column_time()
    {
        $this->assertEquals(
            'abcdef time not null unique default \'1\'',
            _tiny_api_Mysql_Date_Time_Column::make('abcdef')
                ->date_time_type(_tiny_api_Mysql_Date_Time_Column::TYPE_TIME)
                ->default_value('1')
                ->not_null()
                ->unique()
                ->get_definition());
    }

    function test_date_time_column_year_2()
    {
        $this->assertEquals(
            'abcdef year(2) not null unique default \'1\'',
            _tiny_api_Mysql_Date_Time_Column::make('abcdef')
                ->year(2)
                ->default_value('1')
                ->not_null()
                ->unique()
                ->get_definition());
    }

    function test_date_time_column_year_4()
    {
        $this->assertEquals(
            'abcdef year(4) not null unique default \'1\'',
            _tiny_api_Mysql_Date_Time_Column::make('abcdef')
                ->year(4)
                ->default_value('1')
                ->not_null()
                ->unique()
                ->get_definition());
    }

    function test_table_date_columns_year_4()
    {
        ob_start();
?>
create table abc
(
    def date not null,
    ghi datetime not null,
    jkl timestamp not null,
    mno time not null,
    pqr year(4) not null
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('db', 'abc')
                ->dt('def', true)
                ->dtt('ghi', true)
                ->ts('jkl', true)
                ->ti('mno', true)
                ->yr('pqr', 4, true)
                ->get_definition());
    }

    function test_table_date_columns_year_2()
    {
        ob_start();
?>
create table abc
(
    def date,
    ghi datetime,
    jkl timestamp,
    mno time,
    pqr year(2)
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('db', 'abc')
                ->dt('def')
                ->dtt('ghi')
                ->ts('jkl')
                ->ti('mno')
                ->yr('pqr', 2)
                ->get_definition());
    }

    function test_getting_default_term_from_column_non_reserved()
    {
        $col = new _tiny_api_Mysql_Column('abc');
        $col->default_value('def');

        $this->assertEquals("default 'def'", $col->get_default_term());
    }

    function test_getting_default_term_from_column_current_datetime()
    {
        $col = new _tiny_api_Mysql_Column('abc');
        $col->default_value('current_timestamp');

        $this->assertEquals("default current_timestamp",
                            $col->get_default_term());
    }

    function test_table_created()
    {
        ob_start();
?>
create table abc
(
    id bigint unsigned not null auto_increment unique,
    date_created datetime not null
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('db', 'abc')
                ->id('id', true, true)
                ->created()
                ->get_definition());
    }

    function test_getting_on_update_term()
    {
        $col = new _tiny_api_Mysql_Column('abc');
        $col->on_update('current_timestamp');

        $this->assertEquals('on update current_timestamp',
                            $col->get_on_update_term());
    }

    function test_table_updated()
    {
        ob_start();
?>
create table abc
(
    id bigint unsigned not null auto_increment unique,
    date_updated timestamp not null on update current_timestamp
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('db', 'abc')
                ->id('id', true, true)
                ->updated()
                ->get_definition());
    }

    function test_string_validate_type_id_exceptions()
    {
        try
        {
            _tiny_api_Mysql_String_Column::make('abc')->binary_type(-1);

            $this->fail('Was able to set binary type even though ID provided '
                        . 'was invalid.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals('the type ID provided was invalid',
                                $e->get_text());
        }

        try
        {
            _tiny_api_Mysql_String_Column::make('abc')->blob_type(-1);

            $this->fail('Was able to set blob type even though ID provided was '
                        . 'invalid.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals('the type ID provided was invalid',
                                $e->get_text());
        }

        try
        {
            _tiny_api_Mysql_String_Column::make('abc')->char_type(-1);

            $this->fail('Was able to set char type even though ID provided was '
                        . 'invalid.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals('the type ID provided was invalid',
                                $e->get_text());
        }

        try
        {
            _tiny_api_Mysql_String_Column::make('abc')->list_type(-1, array());

            $this->fail('Was able to set list type even though ID provided was '
                        . 'invalid.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals('the type ID provided was invalid',
                                $e->get_text());
        }

        try
        {
            _tiny_api_Mysql_String_Column::make('abc')->text_type(-1);

            $this->fail('Was able to set text type even though ID provided was '
                        . 'invalid.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals('the type ID provided was invalid',
                                $e->get_text());
        }
    }

    function test_string_blob_type_exceptions()
    {
        $types = array(
            _tiny_api_Mysql_String_Column::TYPE_TINYBLOB,
            _tiny_api_Mysql_String_Column::TYPE_MEDIUMBLOB,
            _tiny_api_Mysql_String_Column::TYPE_LONGBLOB,
        );

        foreach ($types as $type_id)
        {
            try
            {
                _tiny_api_Mysql_String_Column::make('abc')
                    ->blob_type($type_id, 15);

                $this->fail('Was able to specify length even though it is not '
                            . 'allowed for a non-blob column.');
            }
            catch (tiny_api_Table_Builder_Exception $e)
            {
                $this->assertEquals(
                    'you can only specify the length if the column is blob',
                    $e->get_text());
            }
        }
    }

    function test_string_text_type_exceptions()
    {
        $types = array(
            _tiny_api_Mysql_String_Column::TYPE_TINYTEXT,
            _tiny_api_Mysql_String_Column::TYPE_MEDIUMTEXT,
            _tiny_api_Mysql_String_Column::TYPE_LONGTEXT,
        );

        foreach ($types as $type_id)
        {
            try
            {
                _tiny_api_Mysql_String_Column::make('abc')
                    ->text_type($type_id, 15);

                $this->fail('Was able to specify length even though it is not '
                            . 'allowed for a non-text column.');
            }
            catch (tiny_api_Table_Builder_Exception $e)
            {
                $this->assertEquals(
                    'you can only specify the length if the column is text',
                    $e->get_text());
            }
        }
    }

    function test_string_binary_binary()
    {
        $this->assertEquals(
            'abc binary(15) character set def collate ghi',
            _tiny_api_Mysql_String_Column::make('abc')
                ->binary_type(_tiny_api_Mysql_String_Column::TYPE_BINARY, 15)
                ->charset('def')
                ->collation('ghi')
                ->get_definition());
    }

    function test_string_binary_varbinary()
    {
        $this->assertEquals(
            'abc varbinary(15) character set def collate ghi',
            _tiny_api_Mysql_String_Column::make('abc')
                ->binary_type(_tiny_api_Mysql_String_Column::TYPE_VARBINARY, 15)
                ->charset('def')
                ->collation('ghi')
                ->get_definition());
    }

    function test_string_blob_tinyblob()
    {
        $this->assertEquals(
            'abc tinyblob character set def collate ghi',
            _tiny_api_Mysql_String_Column::make('abc')
                ->blob_type(_tiny_api_Mysql_String_Column::TYPE_TINYBLOB)
                ->charset('def')
                ->collation('ghi')
                ->get_definition());
    }

    function test_string_blob_blob()
    {
        $this->assertEquals(
            'abc blob(15) character set def collate ghi',
            _tiny_api_Mysql_String_Column::make('abc')
                ->blob_type(_tiny_api_Mysql_String_Column::TYPE_BLOB, 15)
                ->charset('def')
                ->collation('ghi')
                ->get_definition());
    }

    function test_string_blob_mediumblob()
    {
        $this->assertEquals(
            'abc mediumblob character set def collate ghi',
            _tiny_api_Mysql_String_Column::make('abc')
                ->blob_type(_tiny_api_Mysql_String_Column::TYPE_MEDIUMBLOB)
                ->charset('def')
                ->collation('ghi')
                ->get_definition());
    }

    function test_string_blob_longblob()
    {
        $this->assertEquals(
            'abc longblob character set def collate ghi',
            _tiny_api_Mysql_String_Column::make('abc')
                ->blob_type(_tiny_api_Mysql_String_Column::TYPE_LONGBLOB)
                ->charset('def')
                ->collation('ghi')
                ->get_definition());
    }

    function test_string_char_char()
    {
        $this->assertEquals(
            'abc char(15) character set def collate ghi',
            _tiny_api_Mysql_String_Column::make('abc')
                ->char_type(_tiny_api_Mysql_String_Column::TYPE_CHAR, 15)
                ->charset('def')
                ->collation('ghi')
                ->get_definition());
    }

    function test_string_char_varchar()
    {
        $this->assertEquals(
            'abc varchar(15) character set def collate ghi',
            _tiny_api_Mysql_String_Column::make('abc')
                ->char_type(_tiny_api_Mysql_String_Column::TYPE_VARCHAR, 15)
                ->charset('def')
                ->collation('ghi')
                ->get_definition());
    }

    function test_string_list_enum()
    {
        $this->assertEquals(
            'abc enum(\'x\', \'y\') character set def collate ghi',
            _tiny_api_Mysql_String_Column::make('abc')
                ->list_type(_tiny_api_Mysql_String_Column::TYPE_ENUM,
                            array('x', 'y'))
                ->charset('def')
                ->collation('ghi')
                ->get_definition());
    }

    function test_string_list_set()
    {
        $this->assertEquals(
            'abc set(\'x\', \'y\') character set def collate ghi',
            _tiny_api_Mysql_String_Column::make('abc')
                ->list_type(_tiny_api_Mysql_String_Column::TYPE_SET,
                            array('x', 'y'))
                ->charset('def')
                ->collation('ghi')
                ->get_definition());
    }

    function test_string_types_in_a_table()
    {
        ob_start();
?>
create table abc
(
    def char(15) not null,
    ghi varchar(16) not null,
    jkl binary(17) not null,
    mno varbinary(18) not null,
    pqr tinyblob not null,
    stu blob(19) not null,
    vwx mediumblob not null,
    yza longblob not null,
    bcd tinytext not null,
    efg text(20) not null,
    hij mediumtext not null,
    klm longtext not null,
    nop enum('a', 'b') not null,
    qrs set('c', 'd') not null
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('db', 'abc')
                ->char('def', 15, true)
                ->vchar('ghi', 16, true)
                ->bin('jkl', 17, true)
                ->vbin('mno', 18, true)
                ->tblob('pqr', true)
                ->blob('stu', 19, true)
                ->mblob('vwx', true)
                ->lblob('yza', true)
                ->ttext('bcd', true)
                ->text('efg', 20, true)
                ->mtext('hij', true)
                ->ltext('klm', true)
                ->enum('nop', array('a', 'b'), true)
                ->set('qrs', array('c', 'd'), true)
                ->get_definition());
    }

    function test_ref_table_exceptions()
    {
        try
        {
            tiny_api_Ref_Table::make('db', 'abc');

            $this->fail('Was able to create a reference table even though '
                        . 'the table name was non-standard.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'the name of the reference table must contain "_ref_"',
                $e->get_text());
        }

        try
        {
            tiny_api_Ref_Table::make('db', 'abc_ref_def')->add('a', 'b');

            $this->fail('Was able to create a reference table even though '
                        . 'the ID provided was not an integer.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'the ID value provided must be an integer',
                $e->get_text());
        }

        try
        {
            tiny_api_Ref_Table::make('db', 'abc_ref_def')
                ->add(1, 'a')
                ->add(1, 'b');

            $this->fail('Was able to create a reference table even though '
                        . 'a duplicate ID value was used.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals('the ID "1" is already defined',
                                $e->get_text());
        }

        try
        {
            tiny_api_Ref_Table::make('db', 'abc_ref_def')
                ->add(1, 'a', 1)
                ->add(2, 'b', 1);

            $this->fail('Was able to create a reference table even though '
                        . 'a duplicate display order was used.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals('the display order "1" is already defined',
                                $e->get_text());
        }
    }

    function test_ref_table()
    {
        ob_start();
?>
create table abc_ref_def
(
    id bigint unsigned not null auto_increment unique,
    value varchar(100) not null,
    display_order int
);

insert into abc_ref_def
(
    id,
    value,
    display_order
)
values
(
    1,
    'one',
    1
);
insert into abc_ref_def
(
    id,
    value,
    display_order
)
values
(
    2,
    'two',
    2
);
insert into abc_ref_def
(
    id,
    value,
    display_order
)
values
(
    3,
    'three',
    3
);
<?
        $this->assertEquals(
            ob_get_clean(),
            tiny_api_Ref_Table::make('db', 'abc_ref_def')
                ->add(1, 'one', 1)
                ->add(2, 'two', 2)
                ->add(3, 'three', 3)
                ->get_definition());
    }

    function test_table_ai_active_column_is_set()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')->ai();

            $this->fail('Was able to set a column as auto-increment even '
                        . 'though no column was defined.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'call to "tiny_api_Table::ai" invalid until a column is '
                . 'defined',
                $e->get_text());
        }
    }

    function test_table_def_active_column_is_set()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')->def('def');

            $this->fail('Was able to set a default value for a column even '
                        . 'though no column was defined.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'call to "tiny_api_Table::def" invalid until a column is '
                . 'defined',
                $e->get_text());
        }
    }

    function test_table_pk_active_column_is_set()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')->pk();

            $this->fail('Was able to set a column as primary key even though '
                        . 'no column was defined.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'call to "tiny_api_Table::pk" invalid until a column is '
                . 'defined',
                $e->get_text());
        }
    }

    function test_table_uk_active_column_is_set()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')->uk();

            $this->fail('Was able to set a column as a unique key even though '
                        . 'no column was defined.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'call to "tiny_api_Table::uk" invalid until a column is '
                . 'defined',
                $e->get_text());
        }
    }

    function test_table_fk_active_column_is_set()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')->fk('def');

            $this->fail('Was able to set a column as a foreign key even though '
                        . 'no column was defined.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'call to "tiny_api_Table::fk" invalid until a column is '
                . 'defined',
                $e->get_text());
        }
    }

    function test_table_foreign_key_and_dependencies_active_column()
    {
        ob_start();
?>
create table abc
(
    id bigint unsigned not null auto_increment unique
);
<?
        $expected = trim(ob_get_clean());
        $table    = tiny_api_Table::make('db', 'abc')
                        ->id('id', true, true)
                        ->fk('def');

        $this->assertEquals($expected, $table->get_definition());

        ob_start();
?>
   alter table abc
add constraint abc_0_fk
   foreign key (id)
    references def (id)
     on delete cascade
<?
        $expected = trim(ob_get_clean(), "\t\n\r\0\x0B");
        $fks      = $table->get_foreign_key_definitions();

        $this->assertEquals(1, count($fks));
        $this->assertEquals($expected, $fks[ 0 ]);

        $deps = $table->get_dependencies();
        $this->assertTrue(is_array($deps));
        $this->assertEquals(1, count($deps));
        $this->assertTrue(in_array('def', $deps));
    }

    function test_table_foreign_key_full_definition()
    {
        ob_start();
?>
   alter table abc
add constraint abc_0_fk
   foreign key (col_a, col_b)
    references def (col_c, col_d)
<?
        $expected = trim(ob_get_clean(), "\t\n\r\0\x0B");
        $fks      = tiny_api_Table::make('db', 'abc')
                        ->int('col_a')
                        ->int('col_b')
                        ->fk('def',
                             false,
                             array('col_a', 'col_b'),
                             array('col_c', 'col_d'))
                        ->get_foreign_key_definitions();

        $this->assertEquals(1, count($fks));
        $this->assertEquals($expected, $fks[ 0 ]);
    }

    function test_table_foreign_key_exceptions()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')->fk('def', true, array('ghi'));

            $this->fail('Was able to create a foreign key even though the '
                        . 'column provided did not exist.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'column "ghi" cannot be used in foreign key because it has '
                . 'not been defined',
                $e->get_text());
        }
    }

    function test_table_idx_active_column_is_set()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')->idx();

            $this->fail('Was able to set a column as an index even though no '
                        . 'column was defined.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'call to "tiny_api_Table::idx" invalid until a column is '
                . 'defined',
                $e->get_text());
        }
    }

    function test_table_index_exceptions()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')->idx(array('def'));

            $this->fail('Was able to create an index even though the column '
                        . 'provided did not exist.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'column "def" cannot be used in index because it has not been '
                . 'defined',
                $e->get_text());
        }

        try
        {
            tiny_api_Table::make('db', 'abc')
                ->int('col_a')
                ->idx(array('col_a x'));

            $this->fail('Was able to create an index with an invalid column '
                        . 'modifier for asc/desc.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals('columns can only be modified using "asc" or '
                                . '"desc"',
                                $e->get_text());
        }
    }

    function test_table_getting_index_definitions()
    {
        $table   = tiny_api_Table::make('db', 'abc')
                    ->int('col_a')
                    ->int('col_b')
                        ->idx()
                    ->idx(array('col_a asc', 'col_b desc'));
        $indexes = $table->get_index_definitions();

        $this->assertTrue(is_array($indexes));
        $this->assertEquals(2, count($indexes));
        $this->assertEquals(
            "create index abc_0_idx\n          on abc\n             (col_b)",
            $indexes[ 0 ]);
        $this->assertEquals(
            "create index abc_1_idx\n          on abc\n"
            . "             (col_a asc, col_b desc)",
            $indexes[ 1 ]);
    }

    function test_text_type_with_no_length()
    {
        $this->assertEquals(
            'abc text',
            _tiny_api_Mysql_String_Column::make('abc')
                ->text_type(_tiny_api_Mysql_String_Column::TYPE_TEXT)
                ->get_definition());
    }

    function test_getting_db_name_from_ref_table()
    {
        $this->assertEquals(
            'db',
            tiny_api_Ref_Table::make('db', 'abc_ref_def')->get_db_name());
    }

    function test_getting_db_name_from_table()
    {
        $this->assertEquals(
            'db',
            tiny_api_Table::make('db', 'abc')->get_db_name());
    }

    function test_table_serial()
    {
        ob_start();
?>
create table abc
(
    id bigint unsigned not null auto_increment unique primary key
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('db', 'abc')
                ->serial()
                ->get_definition());
    }

    function test_table_serial_modified_name()
    {
        ob_start();
?>
create table abc
(
    a_id bigint unsigned not null auto_increment unique primary key
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('db', 'abc')
                ->serial('a_id')
                ->get_definition());
    }

    function test_id_column_exceptions()
    {
        try
        {
            tiny_api_Table::make('db', 'abc')->id('def');

            $this->fail('Was able to create ID column even though the name '
                        . 'provided was invalid.');
        }
        catch (tiny_api_Table_Builder_Exception $e)
        {
            $this->assertEquals(
                'an ID column must be named "id" or end in "_id"',
                $e->get_text());
        }
    }

    function test_table_id_all_defaults()
    {
        ob_start();
?>
create table abc
(
    id bigint unsigned not null
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('db', 'abc')
                ->id('id')
                ->get_definition());
    }

    function test_table_id()
    {
        ob_start();
?>
create table abc
(
    a_id bigint unsigned not null auto_increment unique
);
<?
        $this->assertEquals(
            trim(ob_get_clean()),
            tiny_api_Table::make('db', 'abc')
                ->id('a_id', true, true)
                ->get_definition());
    }

    function test_pk_and_fk_on_same_active_column()
    {
        $table = tiny_api_Table::make('db', 'abc')
                    ->id('id', true, true)
                        ->pk()
                        ->fk('def');

        ob_start();
?>
create table abc
(
    id bigint unsigned not null auto_increment unique primary key
);
<?
        $this->assertEquals(trim(ob_get_clean()), $table->get_definition());

        ob_start();
?>
   alter table abc
add constraint abc_0_fk
   foreign key (id)
    references def (id)
     on delete cascade
<?
        $fks = $table->get_foreign_key_definitions();
        $this->assertEquals(1, count($fks));
        $this->assertEquals(trim(ob_get_clean(), "\t\n\r\0\x0B"), $fks[ 0 ]);
    }
}
?>
