<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegisterTime extends Model
{
    protected $fillable = ['user_id', 'station_id', 'schedule_id', 'status'];
}
