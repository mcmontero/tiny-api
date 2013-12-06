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
    private $name;
    private $engine;
    private $columns;
    private $map;
    private $active_column;
    private $temporary;
    private $primary_key;
    private $unique_keys;

    function __construct($name)
    {
        $this->name        = $name;
        $this->columns     = array();
        $this->map         = array();
        $this->temporary   = false;
        $this->primary_key = array();
        $this->unique_keys = array();
    }

    static function make($name)
    {
        return new self($name);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function ai()
    {
        if (!($this->active_column instanceof _tiny_api_Mysql_Numeric_Column))
        {
            throw new tiny_api_Table_Builder_Exception(
                        'a non-numeric column cannot be set to auto increment');
        }

        $this->active_column->auto_increment();

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

    final public function bool($name, $not_null = null)
    {
        $this->tint($name, 1, $not_null);
        return $this;
    }

    final public function created()
    {
        $this->dtt('date_created', true);

        $this->active_column->default_value('current_timestamp');

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

        ob_start();
?>
create<?= $this->temporary ? ' temporary' : '' ?> table <?= $this->name . "\n" ?>
(
<?= implode(",\n", $terms) . "\n" ?>
)<?= !is_null($this->engine) ? ' engine = ' . $this->engine . ';' : ';' ?>
<?
        return ob_get_clean();
    }

    final public function id()
    {
        $this->serial('id');
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

    final public function pk($cols = null)
    {
        if (is_null($cols))
        {
            $this->active_column->primary_key();
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
        }

        return $this;
    }

    final public function serial($name)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_BIGINT)
                ->unsigned()
                ->not_null()
                ->auto_increment()
                ->unique());

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

    final public function temp()
    {
        $this->temporary = true;
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

    final public function uk($cols = null)
    {
        if (is_null($cols))
        {
            $this->active_column->unique();
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
        }

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

        if ($this->unique === true)
        {
            $terms[] = 'unique';
        }

        if (!is_null($this->default))
        {
            $terms[] = $this->get_default_term();
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

        if ($this->unique === true)
        {
            $terms[] = 'unique';
        }

        if (!is_null($this->default))
        {
            $terms[] = $this->get_default_term();
        }

        if ($this->primary_key === true)
        {
            $terms[] = 'primary key';
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

    function __construct($name)
    {
        $this->name = $name;
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
        $reserved = array(
            'current_timestamp' => true,
        );

        return 'default '
               . (!array_key_exists($this->default, $reserved) ?
                    "'" . $this->default . "'" :
                    $this->default);
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
