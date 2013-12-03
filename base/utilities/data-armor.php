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
// +---------------------+
// | tiny_api_Data_Armor |
// +---------------------+
//

class tiny_api_Data_Armor
{
    private $key;
    private $data;

    function __construct($key, $data)
    {
        if (strlen($key) != 24)
        {
            throw new tiny_api_Data_Armor_Exception(
                        'encryption key must be exactly 24 characters long');
        }

        $this->key  = $key;
        $this->data = $data;
    }

    static function make($key, $data)
    {
        return new self($key, $data);
    }

    // +----------------+
    // | Public Methods |
    // +----------------+

    final public function lock()
    {
        $data = json_encode($this->data);
        $now  = time();
        $sha1 = sha1("$data$now");
        $data = $data . $this->get_data_delimiter() . $now;

        return urlencode($this->encrypt($data))
               . $this->get_token_delimiter()
               . $sha1;
    }

    final public function unlock($ttl = null)
    {
        @list($data, $sha1) = explode($this->get_token_delimiter(),
                                      $this->data);
        if (empty($data) || empty($sha1))
        {
            throw new tiny_api_Data_Armor_Exception(
                        'token format is incorrect (1)');
        }

        $data = $this->decrypt(urldecode($data));

        @list($data, $timestamp) = explode($this->get_data_delimiter(), $data);
        if (empty($data) || empty($timestamp))
        {
            throw new tiny_api_Data_Armor_Exception(
                        'token format is incorrect (2)');
        }

        if (sha1("$data$timestamp") != $sha1)
        {
            throw new tiny_api_Data_Armor_Exception(
                        'armored data has been tampered with');
        }

        if (!is_null($ttl))
        {
            $diff = time() - $timestamp;
            if ($diff > $ttl)
            {
                throw new tiny_api_Data_Armor_Exception('token has expired');
            }
        }

        return json_decode($data, true);
    }

    // +-----------------+
    // | Private Methods |
    // +-----------------+

    private function decrypt($data)
    {
        return trim(
                mcrypt_decrypt(MCRYPT_3DES,
                               $this->key,
                               pack('H*', $data),
                               MCRYPT_MODE_ECB,
                               $this->get_encryption_iv()));
    }

    private function encrypt($data)
    {
        return bin2hex(
                mcrypt_encrypt(MCRYPT_3DES,
                               $this->key,
                               $data,
                               MCRYPT_MODE_ECB,
                               $this->get_encryption_iv()));
    }

    private function get_data_delimiter()
    {
        return chr(2);
    }

    private function get_encryption_iv()
    {
        return mcrypt_create_iv(
                mcrypt_get_iv_size(
                    MCRYPT_3DES, MCRYPT_MODE_ECB),
                MCRYPT_RAND);
    }

    private function get_token_delimiter()
    {
        return '-';
    }
}
?>
