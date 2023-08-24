<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\HasApiTokens;

class Admin extends User
{
    use HasFactory, HasApiTokens;

    protected $hidden = ['password'];
    protected $guarded = ['id'];
}
