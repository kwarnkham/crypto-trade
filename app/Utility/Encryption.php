<?php

namespace App\Utility;

class Encryption
{
    public static function encrypt(string $data, $aesKey)
    {
        // Encrypt the string using AES/ECB/PKCS5Padding
        return openssl_encrypt($data, 'AES-128-ECB', $aesKey, 0);
    }

    public static function decrypt(string $data, $aesKey)
    {
        return openssl_decrypt($data, 'AES-128-ECB', $aesKey, 0);
    }
}
