<?php

// +------------------------------------------------------------+
// | PUBLIC FUNCTIONS                                           |
// +------------------------------------------------------------+

/**
 * To use this function, pass in the following sets of parameters:
 *
 *  * $ref_table_name : the name of the reference table
 *    $value          : null
 *      This will return an array representing the entire reference table.
 *
 *  * $ref_table_name : the name of the reference table
 *    $value          : integer ID
 *      Decodes the integer ID into the string reference value.
 *
 *  * $ref_table_name : the name of the reference table
 *    $value          : string reference value
 *      Encode the string reference value into the ID.
 */
function refv($ref_table_name, $value = null)
{
    global $__tiny_api_conf__;

    if (!array_key_exists('reference definition file', $__tiny_api_conf__) ||
        is_null($__tiny_api_conf__[ 'reference definition file' ]))
    {
        return null;
    }

    if (empty($ref_table_name))
    {
        throw new tiny_api_Reference_Exception(
                    'the reference table name you provided was empty');
    }

    if (!is_null($value) && empty($value))
    {
        throw new tiny_api_Reference_Exception(
                    'the reference value you provided was empty');
    }

    require_once $__tiny_api_conf__[ 'reference definition file' ];
    $func = '___' . strtoupper($ref_table_name);

    if (!function_exists($func))
    {
        return null;
    }

    $ref_table = $func();

    if (is_null($value))
    {
        return $ref_table;
    }
    else if (is_int($value))
    {
        // Decode from the ID to the value.
        return array_key_exists($value, $ref_table) ?
                    $ref_table[ $value ] : null;
    }
    else
    {
        // Encode from the value to the ID.
        $data = array_flip($ref_table);
        return array_key_exists($value, $data) ? $data[ $value ] : null;
    }
}
?>
