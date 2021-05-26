<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'user_id', 'stripe_id', 'balance', 'balance_transaction', 'currency', 'metadata', 'payment_intent',
        'refunded', 'stripe_status', 'amount_captured', 'amount_refunded', 'application', 'application_fee',
        'application_fee_amount', 'calculated_statement_descriptor', 'created', 'failure_message', 'livemode',
        'order', 'paid', 'payment_method', 'receipt_number', 'receipt_url', 'status'
    ];
}
