<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cree extends Model
{
    protected $table = 'crees';
    protected $fillable = ['name', 'place_id', 'cre_id', 'latitude', 'longitude'];

    // Relacion con los admins
    public function admins()
    {
        return $this->belongsToMany(User::class, 'admins_cree');
    }
    // Relacion con los precios de la cree
    public function prices()
    {
        return $this->hasOne(PriceCre::class, 'cree_id');
    }
}
