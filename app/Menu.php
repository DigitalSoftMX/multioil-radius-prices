<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['name_modulo', 'desplegable', 'ruta', 'id_role', 'icono'];
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
