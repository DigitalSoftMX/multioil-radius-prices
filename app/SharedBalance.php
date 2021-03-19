<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SharedBalance extends Model
{
    protected $fillable = ['sponsor_id', 'beneficiary_id', 'balance', 'status'];
    // Relacion con el patrocinador
    public function sponsor()
    {
        return $this->belongsTo(User::class, 'sponsor_id', 'id');
    }
    // Relacion con el beneficiario
    public function beneficiary()
    {
        return $this->belongsTo(User::class, 'beneficiary_id', 'id');
    }
}
