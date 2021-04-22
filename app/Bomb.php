<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bomb extends Model
{
    // Relacion con la isla
    public function island()
    {
        return $this->belongsTo(Island::class);
    }
}
