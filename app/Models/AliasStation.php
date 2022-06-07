<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AliasStation extends Model
{
    protected $table = 'alias_station';

    protected $fillable = [
        'name',
        'g_placeid',
        'user_rating_total',
        'vicinity',
        'cree_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
