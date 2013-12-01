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

    function __construct($name)
    {
        $this->name    = $name;
        $this->columns = array();
        $this->map     = array();
    }

    static function make($name)
    {
        return new self($name);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function bit($name, $num_bits = null)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_BIT,
                               $num_bits));

        return $this;
    }

    final public function bint($name, $max_display_width = null)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_BIGINT,
                               $max_display_width));

        return $this;
    }

    final public function bool($name)
    {
        $this->tint($name, 1);
        return $this;
    }

    final public function dec($name, $precision = null, $scale = null)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->decimal_type(_tiny_api_Mysql_Numeric_Column::TYPE_DECIMAL,
                               $precision, $scale));

        return $this;
    }

    final public function double($name, $precision = null, $scale = null)
    {
        $this->dec($name, $precision, $scale);
        return $this;
    }

    final public function engine($engine)
    {
        $engine = strtolower($engine);
        if (!in_array($engine, array('myisam', 'innodb')))
        {
            throw new tiny_api_Table_Builder_Exception(
                        "The engine \"$engine\" is invalid.");
        }

        $this->engine = $engine;
        return $this;
    }

    final public function fixed($name, $precision = null, $scale = null)
    {
        $this->dec($name, $precision, $scale);
        return $this;
    }

    final public function float($name, $precision = null)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->float_type($precision));

        return $this;
    }

    final public function get_definition()
    {
        if (count($this->columns) == 0)
        {
            throw new tiny_api_Table_Builder_Exception(
                        'The table cannot be defined because it has no '
                        . 'columns.');
        }

        $columns = array();
        foreach ($this->columns as $column)
        {
            $columns[] = '    ' . $column->get_definition();
        }

        ob_start();
?>
create table <?= $this->name . "\n" ?>
(
<?= implode(",\n", $columns) . "\n" ?>
)<?= !is_null($this->engine) ? ' engine = ' . $this->engine . ';' : ';' ?>
<?
        return ob_get_clean();
    }

    final public function id()
    {
        $this->serial('id');
        return $this;
    }

    final public function int($name, $max_display_width = null)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_INT,
                               $max_display_width));

        return $this;
    }

    final public function mint($name, $max_display_width = null)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_MEDIUMINT,
                               $max_display_width));

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

    final public function sint($name, $max_display_width = null)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::SMALLINT,
                               $max_display_width));

        return $this;
    }

    final public function tint($name, $max_display_width = null)
    {
        $this->add_column(
            _tiny_api_Mysql_Numeric_Column::make($name)
                ->integer_type(_tiny_api_Mysql_Numeric_Column::TYPE_TINYINT,
                               $max_display_width));

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

    private function validate_name_does_not_exist_and_register($name)
    {
        if (array_key_exists($name, $this->map))
        {
            throw new tiny_api_Table_Builder_Exception(
                        "The column \"$name\" already exists.");
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
                            'Unrecognized numeric column type '
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
            $terms[] = 'default ' . $this->default;
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
            throw new tiny_api_Table_Builder_Exception('The type ID provided '
                                                       . 'was invalid.');
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

    final public function get_name()
    {
        return $this->name;
    }

    final public function not_null()
    {
        $this->not_null = true;
        return $this;
    }

    final public function unique()
    {
        $this->unique = true;
        return $this;
    }
}
?>
