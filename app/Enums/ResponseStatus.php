<?php

namespace App\Enums;

enum ResponseStatus: int
{
    case BAD_REQUEST = 400;
    case UNAUTHENTICATED = 401;
    case NOT_FOUND = 404;
}
