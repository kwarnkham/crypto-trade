<?php

namespace App\Enums;

enum WithdrawStatus: int
{
    case PENDING = 1;
    case CONFIRMED = 2;
    case COMPLETED = 3;
    case CANCELED = 4;
    case EXPIRED = 5;
}
