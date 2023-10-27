<?php

namespace App\Utility;

class Formatter
{
    /**
     * @return string
     */
    public static function toMethodFormat($method)
    {
        return Utils::stripZero(substr(Utils::sha3($method), 0, 10));
    }

    /**
     * @return string
     */
    public static function toAddressFormat($address)
    {
        if (Utils::isAddress($address)) {
            $address = strtolower($address);

            if (Utils::isZeroPrefixed($address)) {
                $address = Utils::stripZero($address);
            }
        }

        return implode('', array_fill(0, 64 - strlen($address), 0)).$address;
    }

    /**
     * @param  int  $digit
     * @return string
     */
    public static function toIntegerFormat($value, $digit = 64)
    {
        $bn = Utils::toBn($value);
        $bnHex = $bn->toHex(true);
        $padded = mb_substr($bnHex, 0, 1);

        if ($padded !== 'f') {
            $padded = '0';
        }

        return implode('', array_fill(0, $digit - mb_strlen($bnHex), $padded)).$bnHex;
    }
}
