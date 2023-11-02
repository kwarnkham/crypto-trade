<?php

namespace App\Enums;

enum ExtractType: int
{
    case USDT = 1;
    case TRX = 2;


    public static function toArray()
    {
        return [1, 2];
    }
}
