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
// | PUBLIC CLASSES                                             |
// +------------------------------------------------------------+

//
// +----------------------+
// | tiny_api_Data_Mapper |
// +----------------------+
//

class tiny_api_Data_Mapper
{
    private $elems;
    private $was_validated;

    function __construct()
    {
        $this->elems         = array();
        $this->was_validated = false;
    }

    static function make()
    {
        return new self();
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function char($name, $required, $max_length = null)
    {
        $elem = _tiny_api_Data_Mapper_Element::make(
                    $name, _tiny_api_Data_Mapper_Element::TYPE_CHAR);
        $this->process_attributes($elem, $required, $max_length);

        $this->elems[ $name ] = $elem;

        return $this;
    }

    final public function dtt($name, $required)
    {
        $elem = _tiny_api_Data_Mapper_Element::make(
                    $name, _tiny_api_Data_Mapper_Element::TYPE_DATETIME);
        $this->process_attributes($elem, $required);

        $this->elems[ $name ] = $elem;

        return $this;
    }

    final public function generate_post_data($values = array())
    {
        $this->generate_data($values);

        foreach ($this->elems as $name => $elem)
        {
            $_POST[ $name ] = $elem->get();
        }

        return $this;
    }

    final public function generate_put_data($values = array())
    {
        $this->generate_data($values);

        foreach ($this->elems as $name => $elem)
        {
            _tiny_api_Data_Mapper_Put_Manager::get_instance()
                ->set($name, $elem->get());
        }

        return $this;
    }

    final public function get()
    {
        if ($this->was_validated === false)
        {
            throw new tiny_api_Data_Mapper_Exception(
                        'you cannot get mapped data because it has not been '
                        . 'validated');
        }

        $data = array();
        foreach ($this->elems as $name => $elem)
        {
            $data[ $name ] = $elem->get();
        }

        return $data;
    }

    final public function get_elem($name)
    {
        if (!array_key_exists($name, $this->elems))
        {
            throw new tiny_api_Data_Mapper_Exception(
                        "no element exists with the name \"$name\"");
        }

        return $this->elems[ $name ];
    }

    final public function image($name, $required)
    {
        $elem = _tiny_api_Data_Mapper_Element::make(
                    $name, _tiny_api_Data_Mapper_Element::TYPE_IMAGE);
        $this->process_attributes($elem, $required);

        $this->elems[ $name ] = $elem;

        return $this;
    }

    final public function num($name, $required)
    {
        $elem = _tiny_api_Data_Mapper_Element::make(
                    $name, _tiny_api_Data_Mapper_Element::TYPE_NUMBER);
        $this->process_attributes($elem, $required);

        $this->elems[ $name ] = $elem;

        return $this;
    }

    final public function password($name, $required, $max_length = null)
    {
        $elem = _tiny_api_Data_Mapper_Element::make(
                    $name, _tiny_api_Data_Mapper_Element::TYPE_PASSWORD);
        $this->process_attributes($elem, $required, $max_length);

        $this->elems[ $name ] = $elem;

        return $this;
    }

    final public function remove_elem($name)
    {
        if (!array_key_exists($name, $this->elems))
        {
            throw new tiny_api_Data_Mapper_Exception(
                        "no element exists with the name \"$name\"");
        }

        unset($this->elems[ $name ]);
        return $this;
    }

    public function validate()
    {
        $this->was_validated = true;

        $errors = array();
        foreach ($this->elems as $name => $elem)
        {
            $error_id = $elem->validate();
            if ($error_id != _tiny_api_Data_Mapper_Element::ERROR_NONE)
            {
                $error = null;
                switch ($error_id)
                {
                    case _tiny_api_Data_Mapper_Element::ERROR_TYPE:
                        $error = 'type';
                        break;

                    case _tiny_api_Data_Mapper_Element::ERROR_REQUIRED:
                        $error = 'required';
                        break;

                    case _tiny_api_Data_Mapper_Element::ERROR_MAX_LENGTH:
                        $error = 'max_length';
                        break;

                    case _tiny_api_Data_Mapper_Element::ERROR_UPLOAD:
                        $error = $elem->get_upload_error();
                        break;

                    default:
                        throw new tiny_api_Data_Mapper_Exception(
                                    "unrecognized error ID \"$error_id\"");
                }

                $errors[ $name ] = $error;
            }
        }

        return !empty($errors) ? $errors : null;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function generate_data($values = array())
    {
        foreach ($this->elems as $name => $elem)
        {
            if (array_key_exists($name, $values))
            {
                $elem->set_value($values[ $name ]);
            }
            else
            {
                $elem->set_random_value();
            }
        }

        $this->validate();
    }

    private function process_attributes(_tiny_api_Data_Mapper_Element $elem,
                                        $required,
                                        $max_length = null)
    {
        if ($required)
        {
            $elem->required();
        }

        if (!is_null($max_length))
        {
            $elem->max_length($max_length);
        }

        return $this;
    }
}

// +------------------------------------------------------------+
// | PRIVATE CLASSES                                            |
// +------------------------------------------------------------+

//
// +-------------------------------+
// | _tiny_api_Data_Mapper_Element |
// +-------------------------------+
//

class _tiny_api_Data_Mapper_Element
{
    const TYPE_NUMBER   = 1;
    const TYPE_CHAR     = 2;
    const TYPE_PASSWORD = 3;
    const TYPE_DATETIME = 4;
    const TYPE_IMAGE    = 5;

    const ERROR_NONE       = 1;
    const ERROR_TYPE       = 2;
    const ERROR_REQUIRED   = 3;
    const ERROR_MAX_LENGTH = 4;
    const ERROR_UPLOAD     = 5;

    private $name;
    private $type_id;
    private $required;
    private $max_length;
    private $value;
    private $value_was_set;
    private $value_was_validated;
    private $upload_error;

    function __construct($name, $type_id)
    {
        $this->validate_type_id($type_id);

        $this->name                = $name;
        $this->type_id             = $type_id;
        $this->value_was_set       = false;
        $this->value_was_validated = false;
    }

    static function make($name, $type_id)
    {
        return new self($name, $type_id);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function get()
    {
        if ($this->value_was_set === false)
        {
            throw new tiny_api_Data_Mapper_Exception(
                        'cannot get value because a value has not been set');
        }

        if ($this->value_was_validated === false)
        {
            throw new tiny_api_Data_Mapper_Exception(
                        'cannot get value because it has not been validated');
        }

        if ($this->type_id == self::TYPE_NUMBER)
        {
            return $this->is_empty($this->value) ? null : $this->value;
        }
        else
        {
            return $this->value;
        }
    }

    final public function get_name()
    {
        return $this->name;
    }

    final public function get_upload_error()
    {
        return $this->upload_error;
    }

    final public function max_length($max_length)
    {
        if ($this->type_id != self::TYPE_CHAR &&
            $this->type_id != self::TYPE_PASSWORD)
        {
            throw new tiny_api_Data_Mapper_Exception(
                        'a maximum length can only be set for character and '
                        . 'password types');
        }

        $this->max_length = $max_length;
        return $this;
    }

    final public function required()
    {
        $this->required = true;
        return $this;
    }

    final public function set_random_value()
    {
        switch ($this->type_id)
        {
            case self::TYPE_NUMBER:
                $this->set_value(intval(mt_rand(1, 10)));
                break;

            case self::TYPE_CHAR:
            case self::TYPE_PASSWORD:
                $max_length = 4;
                if (!empty($this->max_length))
                {
                    $max_length = $this->max_length;
                }

                $this->set_value($this->random_string($max_length));
                break;

            case self::TYPE_DATETIME:
                $this->set_value(time() - (86400 * mt_rand(0, 10)));
                break;

            case self::TYPE_IMAGE:
                $this->set_value(
                    array
                    (
                        'name'     => $this->name,
                        'type'     => 'image/jpeg',
                        'tmp_name' => '/tmp/php' . $this->random_string(5),
                        'error'    => UPLOAD_ERR_OK,
                        'size'     => mt_rand(1000, 100000)
                    ));
                break;

            default:
                throw new tiny_api_Data_Mapper_Exception(
                            "unrecognized type ID \""
                            . $this->type_id
                            . '"');
        }

        return $this;
    }

    final public function set_value($value = null)
    {
        $this->value_was_set = true;

        if (!is_null($value))
        {
            $this->value = $value;
            return $this;
        }

        if ($this->type_id == self::TYPE_IMAGE)
        {
            $this->value = array_key_exists($this->name, $_FILES) ?
                                $_FILES[ $this->name ] : null;
        }
        else if (array_key_exists('REQUEST_METHOD', $_SERVER))
        {
            if ($_SERVER[ 'REQUEST_METHOD' ] == 'GET')
            {
                $this->value =
                    array_key_exists($this->name, $_GET) ?
                        $_GET[ $this->name ] : null;
            }
            else if ($_SERVER[ 'REQUEST_METHOD' ] == 'POST')
            {
                $this->value =
                    array_key_exists($this->name, $_POST) ?
                        $_POST[ $this->name ] : null;
            }
            else if ($_SERVER[ 'REQUEST_METHOD' ] == 'PUT')
            {
                $put = _tiny_api_Data_Mapper_Put_Manager::get_instance()
                            ->get_data();

                $this->value =
                    is_array($put) && array_key_exists($this->name, $put) ?
                                $put[ $this->name ] : null;
            }
        }

        return $this;
    }

    final public function validate()
    {
        $this->value_was_validated = true;

        if ($this->value_was_set === false)
        {
            $this->set_value();
        }

        if ($this->required)
        {
            if ($this->type_id == self::TYPE_IMAGE)
            {
                if (!array_key_exists($this->name, $_FILES) ||
                    $_FILES[ $this->name ][ 'error' ] !== 0)
                {
                    return self::ERROR_REQUIRED;
                }
            }
            else if ($this->is_empty($this->value))
            {
                if (is_array($this->value))
                {
                    foreach ($this->value as $value)
                    {
                        if ($this->is_empty($value))
                        {
                            return self::ERROR_REQUIRED;
                        }
                    }
                }
                else
                {
                    if ($this->is_empty($this->value))
                    {
                        return self::ERROR_REQUIRED;
                    }
                }
            }
        }

        if (!is_null($this->max_length))
        {
            if (is_array($this->value))
            {
                foreach ($this->value as $value)
                {
                    if (strlen($value) > $this->max_length)
                    {
                        return self::ERROR_MAX_LENGTH;
                    }
                }
            }
            else
            {
                if (strlen($this->value) > $this->max_length)
                {
                    return self::ERROR_MAX_LENGTH;
                }
            }
        }

        switch ($this->type_id)
        {
            case self::TYPE_NUMBER:
                if (is_array($this->value))
                {
                    foreach ($this->value as $value)
                    {
                        if (!$this->is_empty($value) &&
                            !preg_match('/^[0-9\.]+$/', $value))
                        {
                            return self::ERROR_TYPE;
                        }
                    }
                }
                else
                {
                    if (!$this->is_empty($this->value) &&
                        !preg_match('/^[0-9\.]+$/', $this->value))
                    {
                        return self::ERROR_TYPE;
                    }
                }
                break;

            case self::TYPE_IMAGE:
                switch ($this->value[ 'error' ])
                {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $this->upload_error = 'file is too big';
                        return self::ERROR_UPLOAD;

                    case UPLOAD_ERR_PARTIAL:
                        $this->upload_error = 'partial upload';
                        return self::ERROR_UPLOAD;

                    case UPLOAD_ERR_NO_FILE:
                        $this->upload_error = 'no file was uploaded';
                        return self::ERROR_UPLOAD;

                    case UPLOAD_ERR_NO_TMP_DIR:
                        $this->upload_error = 'no temporary directory';
                        return self::ERROR_UPLOAD;

                    case UPLOAD_ERR_CANT_WRITE:
                        $this->upload_error = 'failed to write';
                        return self::ERROR_UPLOAD;

                    case UPLOAD_ERR_EXTENSION:
                        $this->upload_error = 'extension';
                        return self::ERROR_UPLOAD;
                }

                list($type, $kind) = explode('/', $this->value[ 'type' ]);
                if ($type != 'image')
                {
                    return self::ERROR_TYPE;
                }
                break;
        }

        return self::ERROR_NONE;
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function is_empty($value)
    {
        return is_array($value) ? empty($value) : !strlen(strval($value)) > 0;
    }

    private function random_string($length)
    {
        $string = "";
        for ($i = 0; $i < $length; $i++)
        {
            switch (mt_rand(1,3))
            {
                case 1:
                    // 0-9
                    $string .= chr(mt_rand(48, 57));
                    break;

                case 2:
                    // A-Z
                    $string .= chr(mt_rand(65, 90));
                    break;

                case 3:
                    // a-z
                    $string .= chr(mt_rand(97, 122));
                    break;
            }
        }

        return $string;
    }

    private function validate_type_id($type_id)
    {
        if (!array_key_exists($type_id, array(self::TYPE_NUMBER   => 1,
                                              self::TYPE_CHAR     => 1,
                                              self::TYPE_PASSWORD => 1,
                                              self::TYPE_DATETIME => 1,
                                              self::TYPE_IMAGE    => 1)))
        {
            throw new tiny_api_Data_Mapper_Exception(
                        "unrecognized type ID \"$type_id\"");
        }
    }
}

//
// +-----------------------------------+
// | _tiny_api_Data_Mapper_Put_Manager |
// +-----------------------------------+
//

class _tiny_api_Data_Mapper_Put_Manager
{
    private static $instance;
    private $data;

    function __construct()
    {
        $this->reset();
    }

    static function get_instance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new _tiny_api_Data_Mapper_Put_Manager();
        }

        return self::$instance;
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function get_data()
    {
        if (is_null($this->data))
        {
            if (array_key_exists('REQUEST_METHOD', $_SERVER) &&
                $_SERVER[ 'REQUEST_METHOD' ] == 'PUT')
            {
                $this->data =
                    json_decode(file_get_contents('php://input'), true);
            }
            else
            {
                $this->data = array();
            }
        }

        return $this->data;
    }

    final public function set($name, $value)
    {
        if (is_null($this->data))
        {
            $this->data = array();
        }

        $this->data[ $name ] = $value;
        return $this;
    }

    final public function reset()
    {
        $this->data = null;
    }
}
?>
