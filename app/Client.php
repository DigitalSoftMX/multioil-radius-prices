<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['user_id', 'membership', 'points', 'birthdate', 'sex', 'phone', 'address', 'car', 'RFC', 'business_email', 'ids'];
    // Relacion con el usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
