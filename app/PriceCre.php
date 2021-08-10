<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceCre extends Model
{
    protected $fillable = ['cree_id', 'regular', 'premium', 'diesel'];
}
