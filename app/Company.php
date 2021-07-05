<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'alias', 'business_address', 'phone', 'email', 'stations', 'logo','lock'];
}
