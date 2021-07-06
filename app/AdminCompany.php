<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminCompany extends Model
{
    protected $fillable = [ 'id', 'company_id', 'place_id', 'cre_id', 'name', 'alias', 'image','address','phone','email', 'latitude','longitude'];
    public function stations()
    {
        return $this->hasMany(Station::class, 'company_id');
    }
}
