<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminStation extends Model
{
    /*public function station()
    {
        return $this->belongsToMany(Station::class, 'stations');
    }*/
    protected $fillable = ['radio', 'ids'];
    // relacion con la estacion
    public function station()
    {
        return $this->hasOne(Station::class, 'id', 'station_id');
    }
}
