<?php

namespace App\Enums;

enum ResponseStatus: int
{
    case UNAUTHENTICATED = 401;
    case BAD_REQUEST = 400;
}
