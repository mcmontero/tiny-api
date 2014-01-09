<?php

// +------------------------------------------------------------+
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +----------------+
// | tiny_api_Table |
// +----------------+
//

class tiny_api_Table
{
    private $db_name;
    private $name;
    private $engine;
    private $columns;
    private $map;
    private $active_column;
    private $temporary;
    private $primary_key;
    private $unique_keys;
    private $foreign_keys;
    private $dependencies;
    private $indexes;
    private $rows;
    private $indexed_cols;
    private $charset;
    private $collation;

    function __construct($db_name, $name)
    {
        $this->db_name      = $db_name;
        $this->name         = $name;
        $this->engine       = 'innodb';
        $this->columns      = array();
        $this->map          = array();
        $this->temporary    = false;
        $this->primary_key  = array();
        $this->unique_keys  = array();
        $this->foreign_keys = array();
        $this->dependencies = array();
        $this->indexes      = array();
        $this->rows         = array();
        $this->indexed_cols = array();
        $this->charset      = 'utf8';
        $this->collation    = 'utf8_unicode_ci';
    }

    static function make($db_name, $name)
    {
        return new self($db_name, $name);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function ai()
    {
        $this->assert_active_column_is_set(__METHOD__);

        if (!($this->active_column instanceof _tiny_api_Mysql_Numeric_Column))
        {
            throw new tiny_api_Table_Builder_Exception(
                        'a non-numeric column cannot be set to auto increment');
        }

        $this->active_column->auto_increment();

        return $this;
    }

    final public function bin($name, $length, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->binary_type(_tiny_api_Mysql_String_Column::TYPE_BINARY,
                            $length));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function bit($name, $num_bits = null, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_BIT,
                               $num_bits));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function bint($name,
                               $max_display_width = null,
                               $not_null = false,
                               $unsigned = false,
                               $zero_fill = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_BIGINT,
                               $max_display_width));

        $this->set_attributes($not_null, $unsigned, $zero_fill);

        return $this;
    }

    final public function blob($name, $length, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->blob_type(_tiny_api_Mysql_String_Column::TYPE_BLOB,
                            $length));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function bool($name, $not_null = null)
    {
        $this->tint($name, 1, $not_null);
        return $this;
    }

    final public function char($name, $length, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->char_type(_tiny_api_Mysql_String_Column::TYPE_CHAR,
                            $length));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function created()
    {
        $this->dtt('date_created', true);

        return $this;
    }

    final public function dec($name,
                              $precision = null,
                              $scale = null,
                              $not_null = false,
                              $unsigned = false,
                              $zero_fill = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->decimal_type(_tiny_api_Mysql_Numeric_Column::TYPE_DECIMAL,
                               $precision, $scale));

        $this->set_attributes($not_null, $unsigned, $zero_fill);

        return $this;
    }

    final public function def($default)
    {
        $this->assert_active_column_is_set(__METHOD__);

        $this->active_column->default_value($default);
        return $this;
    }

    final public function double($name,
                                 $precision = null,
                                 $scale = null,
                                 $not_null = false,
                                 $unsigned = false,
                                 $zero_fill = false)
    {
        $this->dec($name, $precision, $scale, $not_null, $unsigned, $zero_fill);
        return $this;
    }

    final public function dt($name, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Date_Time_Column::make($name)
                ->date_time_type(_tiny_api_Mysql_Date_Time_Column::TYPE_DATE));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function dtt($name, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Date_Time_Column::make($name)
                ->date_time_type(
                    _tiny_api_Mysql_Date_Time_Column::TYPE_DATETIME));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function engine($engine)
    {
        $engine = strtolower($engine);
        if (!in_array($engine, array('myisam', 'innodb')))
        {
            throw new tiny_api_Table_Builder_Exception(
                        "the engine \"$engine\" is invalid");
        }

        $this->engine = $engine;
        return $this;
    }

    final public function enum($name, $list, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->list_type(_tiny_api_Mysql_String_Column::TYPE_ENUM,
                            $list));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function fk($parent_table,
                             $on_delete_cascade = true,
                             $cols = null,
                             $parent_cols = null)
    {
        if (is_null($cols))
        {
            $this->assert_active_column_is_set(__METHOD__);
            $cols = array($this->active_column->get_name());
        }

        $num_cols = count($cols);
        for ($i = 0; $i < $num_cols; $i++)
        {
            if (!array_key_exists($cols[ $i ], $this->map))
            {
                throw new tiny_api_Table_Builder_Exception(
                            "column \"" . $cols[ $i ] . "\" cannot be used in "
                            . 'foreign key because it has not been defined');
            }
        }

        if (is_null($parent_cols))
        {
            $parent_cols = array('id');
        }

        $this->foreign_keys[] = array(
            $parent_table,
            (bool)$on_delete_cascade,
            $cols,
            $parent_cols,
        );

        $this->dependencies[] = $parent_table;

        return $this;
    }

    final public function fixed($name,
                                $precision = null,
                                $scale = null,
                                $not_null = false,
                                $unsigned = false,
                                $zero_fill = false)
    {
        $this->dec($name, $precision, $scale, $not_null, $unsigned, $zero_fill);
        return $this;
    }

    final public function float($name,
                                $precision = null,
                                $not_null = false,
                                $unsigned = false,
                                $zero_fill = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->float_type($precision));

        $this->set_attributes($not_null, $unsigned, $zero_fill);

        return $this;
    }

    final public function get_db_name()
    {
        return $this->db_name;
    }

    final public function get_definition()
    {
        if (count($this->columns) == 0)
        {
            throw new tiny_api_Table_Builder_Exception(
                        'the table cannot be defined because it has no '
                        . 'columns');
        }

        $terms = array();
        foreach ($this->columns as $column)
        {
            $terms[] = '    ' . $column->get_definition();
        }

        if (!empty($this->unique_keys))
        {
            foreach ($this->unique_keys as $index => $unique_key)
            {
                $terms[] = '    unique key '
                           . $this->name
                           . "_$index"
                           . '_uk ('
                           . implode(', ', $unique_key)
                           . ')';
            }
        }

        if (!empty($this->primary_key))
        {
            $terms[] = '    primary key '
                       . $this->name
                       . '_pk ('
                       . implode(', ', $this->primary_key)
                       . ')';
        }

        $table_config = array();
        if (!is_null($this->engine))
        {
            $table_config[] = 'engine = ' . $this->engine;
        }

        if (!is_null($this->charset))
        {
            $table_config[] = 'default charset = ' . $this->charset;
        }

        if (!is_null($this->collation))
        {
            $table_config[] = 'collate = ' . $this->collation;
        }

        $table_config = implode(' ', $table_config);

        ob_start();
?>
create<?= $this->temporary ? ' temporary' : '' ?> table <?= $this->name . "\n" ?>
(
<?= implode(",\n", $terms) . "\n" ?>
)<?= !empty($table_config) ? " $table_config" : ''?>;
<?
        return trim(ob_get_clean());
    }

    final public function get_foreign_key_definitions()
    {
        if (empty($this->foreign_keys))
        {
            return array();
        }

        $fks = array();
        foreach ($this->foreign_keys as $index => $foreign_key)
        {
            list($parent_table, $on_delete_cascade, $cols, $parent_cols) =
                                    $foreign_key;

            $constraint_name = $this->name . "_$index" . '_fk';

            $fks[] = '   alter table ' . $this->name . "\n"
                     . 'add constraint '
                     . $this->name
                     . "_$index"
                     . "_fk\n"
                     . '   foreign key ('
                     . implode(', ', $cols)
                     . ")\n    references $parent_table"
                     . (!empty($parent_cols) ?
                        ' (' . implode(', ', $parent_cols) . ')' : '')
                     . ($on_delete_cascade ?
                        "\n     on delete cascade" : '');
        }

        return $fks;
    }

    final public function get_index_definitions()
    {
        $indexes = array();
        foreach ($this->indexes as $index => $cols)
        {
            $indexes[] = 'create index '
                         . $this->name
                         . "_$index"
                         . "_idx\n          on "
                         . $this->name
                         . "\n             ("
                         . implode(', ', $cols)
                         . ')';
        }

        return $indexes;
    }

    final public function get_insert_statements()
    {
        if (empty($this->rows))
        {
            return null;
        }

        $rows = array();
        foreach ($this->rows as $row)
        {
            ob_start();

            print 'insert into ' . $this->name . "\n(\n";

            $columns = array();
            foreach ($this->columns as $column)
            {
                $columns[] = '    ' . $column->get_name();
            }
            print implode(",\n", $columns);

            print "\n)\nvalues\n(\n";

            $values = array();
            foreach ($row as $value)
            {
                if (!array_key_exists($value, array('current_timestamp' => 1)))
                {
                    $values[] = "    '$value'";
                }
                else
                {
                    $values[] = "    $value";
                }
            }
            print implode(",\n", $values) . "\n);";

            $rows[] = ob_get_clean();
        }

        return $rows;
    }

    final public function get_dependencies()
    {
        return $this->dependencies;
    }

    final public function get_unindexed_foreign_keys()
    {
        $unindexed = array();
        foreach ($this->foreign_keys as $data)
        {
            list($parent_table, $on_delete_cascade, $cols, $parent_cols) =
                                    $data;

            if (!array_key_exists(implode(',', $cols), $this->indexed_cols))
            {
                $unindexed[] = array
                (
                    $this->name,
                    $parent_table,
                    $cols,
                    $parent_cols
                );
            }
        }

        return $unindexed;
    }

    final public function id($name, $unique = false, $serial = false)
    {
        if ($name != 'id' && !preg_match('/_id$/', $name))
        {
            throw new tiny_api_Table_Builder_Exception(
                        'an ID column must be named "id" or end in "_id"');
        }

        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_BIGINT)
                ->unsigned()
                ->not_null());

        if ($unique)
        {
            $this->active_column->unique();
        }

        if ($serial)
        {
            $this->active_column->auto_increment();
        }

        return $this;
    }

    final public function idx($cols = null)
    {
        if (is_null($cols))
        {
            $this->assert_active_column_is_set(__METHOD__);
            $cols = array($this->active_column->get_name());
        }

        $num_cols = count($cols);
        for ($i = 0; $i < $num_cols; $i++)
        {
            @list($col, $asc_desc) = explode(' ', $cols[ $i ]);
            if (!array_key_exists($col, $this->map))
            {
                throw new tiny_api_Table_Builder_Exception(
                            "column \"$col\" cannot be used in index because "
                            . 'it has not been defined');
            }

            if (!empty($asc_desc) && $asc_desc != 'asc' && $asc_desc != 'desc')
            {
                throw new tiny_api_Table_Builder_Exception(
                            'columns can only be modified using "asc" or '
                            . '"desc"');
            }
        }

        $this->indexes[] = $cols;
        $this->add_indexed_cols($cols);

        return $this;
    }

    final public function ins()
    {
        $num_table_cols = count($this->columns);
        $args           = func_get_args();
        foreach ($args as $row)
        {
            $num_cols = count($row);
            if ($num_cols != $num_table_cols)
            {
                throw new tiny_api_Table_Builder_Exception(
                            "this table has $num_table_cols column(s) but "
                            . "your insert data has $num_cols");
            }

            $this->rows[] = $row;
        }

        return $this;
    }

    final public function int($name,
                              $max_display_width = null,
                              $not_null = false,
                              $unsigned = false,
                              $zero_fill = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_INT,
                               $max_display_width));

        $this->set_attributes($not_null, $unsigned, $zero_fill);

        return $this;
    }

    final public function lblob($name, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->blob_type(_tiny_api_Mysql_String_Column::TYPE_LONGBLOB));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function ltext($name, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->text_type(_tiny_api_Mysql_String_Column::TYPE_LONGTEXT));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function mblob($name, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->blob_type(_tiny_api_Mysql_String_Column::TYPE_MEDIUMBLOB));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function mint($name,
                               $max_display_width = null,
                               $not_null = false,
                               $unsigned = false,
                               $zero_fill = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_MEDIUMINT,
                               $max_display_width));

        $this->set_attributes($not_null, $unsigned, $zero_fill);

        return $this;
    }

    final public function mtext($name, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->text_type(_tiny_api_Mysql_String_Column::TYPE_MEDIUMTEXT));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function pk($cols = null)
    {
        if (is_null($cols))
        {
            $this->assert_active_column_is_set(__METHOD__);
            $this->active_column->primary_key();
            $this->add_indexed_cols(array($this->active_column->get_name()));
        }
        else
        {
            $num_cols = count($cols);
            for ($i = 0; $i < $num_cols; $i++)
            {
                if (!array_key_exists($cols[ $i ], $this->map))
                {
                    throw new tiny_api_Table_Builder_Exception(
                                "column \"" . $cols[ $i ] . "\" cannot be used "
                                . "in primary key because it has not been "
                                . "defined");
                }

                $this->primary_key[] = $cols[ $i ];
            }

            $this->add_indexed_cols($this->primary_key);
        }

        return $this;
    }

    final public function serial($name = 'id')
    {
        $this->id($name, false, true)->pk();
        return $this;
    }

    final public function set($name, $list, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->list_type(_tiny_api_Mysql_String_Column::TYPE_SET,
                            $list));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function sint($name,
                               $max_display_width = null,
                               $not_null = false,
                               $unsigned = false,
                               $zero_fill = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::SMALLINT,
                               $max_display_width));

        $this->set_attributes($not_null, $unsigned, $zero_fill);

        return $this;
    }

    final public function tblob($name, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->blob_type(_tiny_api_Mysql_String_Column::TYPE_TINYBLOB));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function temp()
    {
        $this->temporary = true;
        return $this;
    }

    final public function text($name, $length = null, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->text_type(_tiny_api_Mysql_String_Column::TYPE_TEXT,
                            $length));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function tint($name,
                               $max_display_width = null,
                               $not_null = false,
                               $unsigned = false,
                               $zero_fill = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_TINYINT,
                               $max_display_width));

        $this->set_attributes($not_null, $unsigned, $zero_fill);

        return $this;
    }

    final public function ti($name, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Date_Time_Column::make($name)
                ->date_time_type(
                    _tiny_api_Mysql_Date_Time_Column::TYPE_TIME));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function ts($name, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Date_Time_Column::make($name)
                ->date_time_type(
                    _tiny_api_Mysql_Date_Time_Column::TYPE_TIMESTAMP));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function ttext($name, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->text_type(_tiny_api_Mysql_String_Column::TYPE_TINYTEXT));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function updated()
    {
        $this->ts('date_updated', true);

        $this->active_column->on_update('current_timestamp');

        return $this;
    }

    final public function uk($cols = null)
    {
        if (is_null($cols))
        {
            $this->assert_active_column_is_set(__METHOD__);
            $this->active_column->unique();
            $this->add_indexed_cols(array($this->active_column->get_name()));
        }
        else
        {
            $unique_key = array();
            $num_cols   = count($cols);
            for ($i = 0; $i < $num_cols; $i++)
            {
                if (!array_key_exists($cols[ $i ], $this->map))
                {
                    throw new tiny_api_Table_Builder_Exception(
                                "column \"" . $cols[ $i ] . "\" cannot be used "
                                . "in unique key because it has not been "
                                . "defined");
                }

                $unique_key[] = $cols[ $i ];
            }

            $this->unique_keys[] = $unique_key;
            $this->add_indexed_cols($unique_key);
        }

        return $this;
    }

    final public function vbin($name, $length, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->binary_type(_tiny_api_Mysql_String_Column::TYPE_VARBINARY,
                            $length));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function vchar($name, $length, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_String_Column::make($name)
                ->char_type(_tiny_api_Mysql_String_Column::TYPE_VARCHAR,
                            $length));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    final public function yr($name, $num_digits = 4, $not_null = false)
    {
        $this->add_column(
            _tiny_api_Mysql_Date_Time_Column::make($name)
                ->year($num_digits));

        $this->set_attributes($not_null, null, null);

        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function add_column(_tiny_api_Mysql_Column $column)
    {
        $this->validate_name_does_not_exist_and_register($column->get_name());

        $this->active_column = $column;
        $this->columns[]     = $this->active_column;

        return $this;
    }

    private function add_indexed_cols(array $cols)
    {
        $this->indexed_cols[ implode(',', $cols) ] = true;
        return $this;
    }

    private function assert_active_column_is_set($caller)
    {
        if (is_null($this->active_column) ||
            !($this->active_column instanceof _tiny_api_Mysql_Column))
        {
            throw new tiny_api_Table_Builder_Exception(
                        "call to \"$caller\" invalid until a column is "
                        . 'defined');
        }
    }

    private function set_attributes($not_null, $unsigned, $zero_fill)
    {
        if ($not_null)
        {
            $this->active_column->not_null();
        }

        if ($unsigned)
        {
            $this->active_column->unsigned();
        }

        if ($zero_fill)
        {
            $this->active_column->zero_fill();
        }
    }

    private function validate_name_does_not_exist_and_register($name)
    {
        if (array_key_exists($name, $this->map))
        {
            throw new tiny_api_Table_Builder_Exception(
                        "the column \"$name\" already exists");
        }

        $this->map[ $name ] = true;
    }
}

//
// +--------------------+
// | tiny_api_Ref_Table |
// +--------------------+
//

class tiny_api_Ref_Table
extends tiny_api_Table
{
    private $ids;
    private $display_orders;
    private $display_order;

    function __construct($db_name, $name)
    {
        $this->validate_name($name);

        parent::__construct($db_name, $name);

        $this->ids            = array();
        $this->display_orders = array();
        $this->display_order  = 1;

        $this->id('id', true, true)
             ->vchar('value', 100, true)
             ->int('display_order');
    }

    static function make($db_name, $name)
    {
        return new self($db_name, $name);
    }

    final public function add($id, $value, $display_order = null)
    {
        if (!is_int($id))
        {
            throw new tiny_api_Table_Builder_Exception(
                        'the ID value provided must be an integer');
        }

        if (array_key_exists($id, $this->ids))
        {
            throw new tiny_api_Table_Builder_Exception(
                        "the ID \"$id\" is already defined");
        }

        if (array_key_exists($display_order, $this->display_orders))
        {
            throw new tiny_api_Table_Builder_Exception(
                        "the display order \"$display_order\" is already "
                        . "defined");
        }

        if (is_null($display_order))
        {
            $display_order = $this->display_order++;
        }

        call_user_func(array($this, 'ins'), array($id, $value, $display_order));

        $this->ids[ $id ]                       = true;
        $this->display_orders[ $display_order ] = true;

        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function validate_name($name)
    {
        if (!preg_match('/^(\w)+_ref_/', $name))
        {
            throw new tiny_api_Table_Builder_Exception(
                        'the name of the reference table must contain '
                        . '"_ref_"');
        }
    }
}


// +------------------------------------------------------------+
// | PRIVATE CLASSES                                            |
// +------------------------------------------------------------+

//
// +--------------------------------+
// | _tiny_api_Mysql_Numeric_Column |
// +--------------------------------+
//

class _tiny_api_Mysql_Numeric_Column
extends _tiny_api_Mysql_Column
{
    const TYPE_BIT       = 1;
    const TYPE_TINYINT   = 2;
    const TYPE_SMALLINT  = 3;
    const TYPE_MEDIUMINT = 4;
    const TYPE_INT       = 5;
    const TYPE_BIGINT    = 6;
    const TYPE_DECIMAL   = 7;
    const TYPE_FLOAT     = 8;
    const TYPE_DOUBLE    = 9;
    const TYPE_REAL      = 10;

    private $type_id;
    private $max_display_width;
    private $precision;
    private $scale;
    private $unsigned;
    private $zero_fill;
    private $auto_increment;

    function __construct($name)
    {
        parent::__construct($name);
    }

    static function make($name)
    {
        return new self($name);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function auto_increment()
    {
        $this->auto_increment = true;
        return $this;
    }

    /**
     * Precision is the total number of digits.  Scale is the number of digits
     * after the decimal place.
     */
    final public function decimal_type($type_id, $precision, $scale)
    {
        $this->validate_type_id($type_id);

        $this->type_id   = $type_id;
        $this->precision = $precision;
        $this->scale     = $scale;
        return $this;
    }

    final public function float_type($precision)
    {
        $this->type_id   = self::TYPE_FLOAT;
        $this->precision = $precision;
        return $this;
    }

    final public function get_definition()
    {
        $terms = array($this->name);

        switch ($this->type_id)
        {
            case self::TYPE_BIT:
                $terms[] = $this->with_max_display_width('bit');
                break;

            case self::TYPE_TINYINT:
                $terms[] = $this->with_max_display_width('tinyint');
                break;

            case self::TYPE_SMALLINT:
                $terms[] = $this->with_max_display_width('smallint');
                break;

            case self::TYPE_MEDIUMINT:
                $terms[] = $this->with_max_display_width('mediumint');
                break;

            case self::TYPE_INT:
                $terms[] = $this->with_max_display_width('int');
                break;

            case self::TYPE_BIGINT:
                $terms[] = $this->with_max_display_width('bigint');
                break;

            case self::TYPE_DECIMAL:
                $terms[] = $this->with_precision_and_scale('decimal');
                break;

            case self::TYPE_FLOAT:
                $terms[] = $this->with_precision('float');
                break;

            case self::TYPE_DOUBLE:
                $terms[] = $this->with_precision_and_scale('double');
                break;

            case self::TYPE_REAL:
                $terms[] = $this->with_precision_and_scale('real');
                break;

            default:
                throw new tiny_api_Table_Builder_Exception(
                            'unrecognized numeric column type '
                            . "\"" . $this->type_id . "\"");
        }

        if ($this->unsigned === true)
        {
            $terms[] = 'unsigned';
        }

        if ($this->zero_fill === true)
        {
            $terms[] = 'zerofill';
        }

        if ($this->not_null === true)
        {
            $terms[] = 'not null';
        }

        if ($this->auto_increment === true)
        {
            $terms[] = 'auto_increment';
        }

        if ($this->primary_key === false && $this->unique === true)
        {
            $terms[] = 'unique';
        }

        $default_term = $this->get_default_term();
        if (!empty($default_term))
        {
            $terms[] = $default_term;
        }

        if ($this->primary_key === true)
        {
            $terms[] = 'primary key';
        }

        return implode(' ', $terms);
    }

    final public function integer_type($type_id, $max_display_width = null)
    {
        $this->validate_type_id($type_id);

        $this->type_id           = $type_id;
        $this->max_display_width = $max_display_width;
        return $this;
    }

    final public function unsigned()
    {
        $this->unsigned = true;
        return $this;
    }

    final public function zero_fill()
    {
        $this->zero_fill = true;
        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function validate_type_id($type_id)
    {
        if (!in_array($type_id, array(self::TYPE_BIT,
                                      self::TYPE_TINYINT,
                                      self::TYPE_SMALLINT,
                                      self::TYPE_MEDIUMINT,
                                      self::TYPE_INT,
                                      self::TYPE_BIGINT,
                                      self::TYPE_DECIMAL,
                                      self::TYPE_FLOAT,
                                      self::TYPE_DOUBLE)))
        {
            throw new tiny_api_Table_Builder_Exception('the type ID provided '
                                                       . 'was invalid');
        }
    }

    private function with_max_display_width($type)
    {
        return !is_null($this->max_display_width) ?
                "$type(" . $this->max_display_width . ')' : $type;
    }

    private function with_precision($type)
    {
        return !is_null($this->precision) ?
                "$type(" . $this->precision . ')' : $type;
    }

    private function with_precision_and_scale($type)
    {
        return !is_null($this->precision) && !is_null($this->scale) ?
                "$type(" . $this->precision . ', ' . $this->scale . ')' : $type;
    }
}

//
// +----------------------------------+
// | _tiny_api_Mysql_Date_Time_Column |
// +----------------------------------+
//

class _tiny_api_Mysql_Date_Time_Column
extends _tiny_api_Mysql_Column
{
    const TYPE_DATE      = 1;
    const TYPE_DATETIME  = 2;
    const TYPE_TIMESTAMP = 3;
    const TYPE_TIME      = 4;
    const TYPE_YEAR      = 5;

    private $type_id;
    private $num_digits;

    function __construct($name)
    {
        parent::__construct($name);
    }

    static function make($name)
    {
        return new self($name);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function date_time_type($type_id)
    {
        $this->validate_type_id($type_id);

        $this->type_id = $type_id;
        return $this;
    }

    final public function get_definition()
    {
        $terms = array($this->name);

        switch ($this->type_id)
        {
            case self::TYPE_DATE:
                $terms[] = 'date';
                break;

            case self::TYPE_DATETIME:
                $terms[] = 'datetime';
                break;

            case self::TYPE_TIMESTAMP:
                $terms[] = 'timestamp';
                break;

            case self::TYPE_TIME:
                $terms[] = 'time';
                break;

            case self::TYPE_YEAR:
                $terms[] = 'year(' . $this->num_digits . ')';
                break;

            default:
                throw new tiny_api_Table_Builder_Exception(
                            'unrecognized date time column type '
                            . "\"" . $this->type_id . "\"");
        }

        if ($this->not_null === true)
        {
            $terms[] = 'not null';
        }

        if ($this->primary_key === false && $this->unique === true)
        {
            $terms[] = 'unique';
        }

        $default_term = $this->get_default_term();
        if (!empty($default_term))
        {
            $terms[] = $default_term;
        }

        if ($this->primary_key === true)
        {
            $terms[] = 'primary key';
        }

        if (!is_null($this->on_update))
        {
            $terms[] = $this->get_on_update_term();
        }

        return implode(' ', $terms);
    }

    final public function year($num_digits = 4)
    {
        $this->type_id    = self::TYPE_YEAR;
        $this->num_digits = $num_digits;
        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function validate_type_id($type_id)
    {
        if (!in_array($type_id, array(self::TYPE_DATE,
                                      self::TYPE_DATETIME,
                                      self::TYPE_TIMESTAMP,
                                      self::TYPE_TIME,
                                      self::TYPE_YEAR)))
        {
            throw new tiny_api_Table_Builder_Exception('the type ID provided '
                                                       . 'was invalid');
        }
    }
}

//
// +-------------------------------+
// | _tiny_api_Mysql_String_Column |
// +-------------------------------+
//

class _tiny_api_Mysql_String_Column
extends _tiny_api_Mysql_Column
{
    const TYPE_CHAR       = 1;
    const TYPE_VARCHAR    = 2;
    const TYPE_BINARY     = 3;
    const TYPE_VARBINARY  = 4;
    const TYPE_TINYBLOB   = 5;
    const TYPE_BLOB       = 6;
    const TYPE_MEDIUMBLOB = 7;
    const TYPE_LONGBLOB   = 8;
    const TYPE_TINYTEXT   = 9;
    const TYPE_TEXT       = 10;
    const TYPE_MEDIUMTEXT = 11;
    const TYPE_LONGTEXT   = 12;
    const TYPE_ENUM       = 13;
    const TYPE_SET        = 14;

    private $type_id;
    private $length;
    private $charset;
    private $collation;
    private $list;

    function __construct($name)
    {
        parent::__construct($name);

        $this->collation = 'utf8_unicode_ci';
    }

    static function make($name)
    {
        return new self($name);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function binary_type($type_id, $length = null)
    {
        $this->validate_type_id($type_id);

        $this->type_id = $type_id;
        $this->length  = $length;
        return $this;
    }

    final public function blob_type($type_id, $length = null)
    {
        $this->validate_type_id($type_id);

        if ($type_id != self::TYPE_BLOB && !is_null($length))
        {
            throw new tiny_api_Table_Builder_Exception(
                        'you can only specify the length if the column is '
                        . 'blob');
        }

        $this->type_id = $type_id;
        $this->length  = $length;
        return $this;
    }

    final public function char_type($type_id, $length = null)
    {
        $this->validate_type_id($type_id);

        $this->type_id = $type_id;
        $this->length  = $length;
        return $this;
    }

    final public function charset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    final public function collation($name)
    {
        $this->collation = $name;
        return $this;
    }

    final public function get_definition()
    {
        $terms = array($this->name);

        switch ($this->type_id)
        {
            case self::TYPE_CHAR:
                $terms[] = 'char(' . $this->length . ')';
                break;

            case self::TYPE_VARCHAR:
                $terms[] = 'varchar(' . $this->length . ')';
                break;

            case self::TYPE_BINARY:
                $terms[] = 'binary(' . $this->length . ')';
                break;

            case self::TYPE_VARBINARY:
                $terms[] = 'varbinary(' . $this->length . ')';
                break;

            case self::TYPE_TINYBLOB:
                $terms[] = 'tinyblob';
                break;

            case self::TYPE_BLOB:
                $terms[] = 'blob(' . $this->length . ')';
                break;

            case self::TYPE_MEDIUMBLOB:
                $terms[] = 'mediumblob';
                break;

            case self::TYPE_LONGBLOB:
                $terms[] = 'longblob';
                break;

            case self::TYPE_TINYTEXT:
                $terms[] = 'tinytext';
                break;

            case self::TYPE_TEXT:
                $terms[] = 'text'
                           . (!empty($this->length) ?
                                '(' . $this->length . ')' : '');
                break;

            case self::TYPE_MEDIUMTEXT:
                $terms[] = 'mediumtext';
                break;

            case self::TYPE_LONGTEXT:
                $terms[] = 'longtext';
                break;

            case self::TYPE_ENUM:
                $terms[] = 'enum(' . $this->format_list() . ')';
                break;

            case self::TYPE_SET:
                $terms[] = 'set(' . $this->format_list() . ')';
                break;

            default:
                throw new tiny_api_Table_Builder_Exception(
                            'unrecognized string column type '
                            . "\"" . $this->type_id . "\"");
        }

        if (!is_null($this->not_null))
        {
            $terms[] = 'not null';
        }

        if ($this->primary_key === false && $this->unique === true)
        {
            $terms[] = 'unique';
        }

        if (!is_null($this->charset))
        {
            $terms[] = 'character set ' . $this->charset;
        }

        if (!is_null($this->collation))
        {
            $terms[] = 'collate ' . $this->collation;
        }

        $default_term = $this->get_default_term();
        if (!empty($default_term))
        {
            $terms[] = $default_term;
        }

        if ($this->primary_key === true)
        {
            $terms[] = 'primary key';
        }

        return implode(' ', $terms);
    }

    final public function list_type($type_id, array $values)
    {
        $this->validate_type_id($type_id);

        $this->type_id = $type_id;
        $this->list    = $values;
        return $this;
    }

    final public function text_type($type_id, $length = null)
    {
        $this->validate_type_id($type_id);

        if ($type_id != self::TYPE_TEXT && !is_null($length))
        {
            throw new tiny_api_Table_Builder_Exception(
                        'you can only specify the length if the column is '
                        . 'text');
        }

        $this->type_id = $type_id;
        $this->length  = $length;
        return $this;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function format_list()
    {
        $list = array();
        foreach ($this->list as $value)
        {
            $list[] = "'$value'";
        }

        return implode(', ', $list);
    }

    private function validate_type_id($type_id)
    {
        if (!in_array($type_id, array(self::TYPE_CHAR,
                                      self::TYPE_VARCHAR,
                                      self::TYPE_BINARY,
                                      self::TYPE_VARBINARY,
                                      self::TYPE_TINYBLOB,
                                      self::TYPE_BLOB,
                                      self::TYPE_MEDIUMBLOB,
                                      self::TYPE_LONGBLOB,
                                      self::TYPE_TINYTEXT,
                                      self::TYPE_TEXT,
                                      self::TYPE_MEDIUMTEXT,
                                      self::TYPE_LONGTEXT,
                                      self::TYPE_ENUM,
                                      self::TYPE_SET)))
        {
            throw new tiny_api_Table_Builder_Exception('the type ID provided '
                                                       . 'was invalid');
        }
    }
}

//
// +------------------------+
// | _tiny_api_Mysql_Column |
// +------------------------+
//

class _tiny_api_Mysql_Column
{
    protected $name;
    protected $not_null;
    protected $unique;
    protected $default;
    protected $primary_key;
    protected $on_update;

    function __construct($name)
    {
        $this->name        = $name;
        $this->primary_key = false;
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function default_value($default)
    {
        $this->default = $default;
        return $this;
    }

    final public function get_default_term()
    {
        if (!empty($this->default))
        {
            $reserved = array
            (
                'current_timestamp' => true,
            );

            return 'default '
                   . (!array_key_exists($this->default, $reserved) ?
                        "'" . $this->default . "'" :
                        $this->default);
        }
        else
        {
            return empty($this->not_null) || $this->not_null === false ?
                                'default null' : '';
        }
    }

    final public function get_on_update_term()
    {
        return empty($this->on_update) ? null : 'on update ' . $this->on_update;
    }

    final public function get_name()
    {
        return $this->name;
    }

    final public function not_null()
    {
        $this->not_null = true;
        return $this;
    }

    final public function on_update($value)
    {
        $this->on_update = $value;
        return $this;
    }

    final public function primary_key()
    {
        $this->primary_key = true;
        return $this;
    }

    final public function unique()
    {
        $this->unique = true;
        return $this;
    }
}
?>
