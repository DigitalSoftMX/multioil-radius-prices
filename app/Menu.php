<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['name_module', 'display', 'route', 'id_role', 'icon'];
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
