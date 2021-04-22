<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dispatcher extends Model
{
    protected $fillable = ['name', 'first_surname', 'second_surname', 'phone'];
    // Relacion con la estacion
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
