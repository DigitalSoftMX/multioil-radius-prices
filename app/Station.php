<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    // Relacino con las bombas
    public function _bombs()
    {
        return $this->hasMany(Bomb::class);
    }
    // Relacion con los turnos de la estacion
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
