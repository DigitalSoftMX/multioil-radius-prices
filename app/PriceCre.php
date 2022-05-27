<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PriceCre extends Model
{
    protected $table = 'price_cres';
    protected $fillable = ['cree_id', 'regular', 'premium', 'diesel'];

    public function cree()
    {
        $this->belongsTo(Cree::class,);
    }
}
