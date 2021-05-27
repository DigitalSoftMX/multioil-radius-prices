<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'user_id', 'stripe_id', 'balance', 'currency', 'metadata',
        'amount_captured', 'application_fee_amount','created', 'livemode',
        'payment_method', 'status'
    ];
}
