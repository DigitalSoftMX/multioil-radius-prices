<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['company_id', 'station_id', 'sale', 'gasoline', 'payment', 'liters', 'client_id', 'dispatcher_id', 'time_id', 'schedule_id', 'no_island', 'no_bomb', 'sponsor_id'];
    // Relacion con la estacion
    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
