<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Http\Controllers\Controller;
use App\Repositories\Activities;
use App\SharedBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class SharedController extends Controller
{
    protected $activities;
    public function __construct(Activities $activities)
    {
        $this->activities = $activities;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (($user = Auth::user())->role_id == 5) {
            $deposits = null;
            if ($request->value == 'sent') {
                $deposits = $this->activities->getBalances($request, new SharedBalance, [['sponsor_id', $user->id], ['status', 3]]);
            }
            if ($request->value == 'received') {
                if ($request->start == '' || $request->end == '') {
                    $deposits = $this->activities->getBalances($request, new SharedBalance, [['beneficiary_id', $user->id], ['status', 2]], true);
                } else {
                    $deposits = $this->activities->getBalances($request, new SharedBalance, [['beneficiary_id', $user->id], ['status', 3]]);
                }
            }
            if (is_bool($deposits)) {
                return $this->activities->errorResponse('Las fechas son incorrectas.');
            }
            if ($deposits->count() == 0) {
                return $this->activities->errorResponse('No cuenta con depósitos en la cuenta');
            }
            $balances = array();
            foreach ($deposits as $deposit) {
                if ($request->start == '' || $request->end == '') {
                    $data['id'] = $deposit->id;
                }
                $data[($request->value == 'sent') ? 'beneficiary' : 'sponsor'] = ($request->value == 'sent') ?
                    $deposit->beneficiary->name . ' ' . $deposit->beneficiary->client->first_surname : $deposit->sponsor->name . ' ' . $deposit->sponsor->client->first_surname;
                $data['membership'] = ($request->value == 'sent') ? $deposit->beneficiary->client->membership : $deposit->sponsor->client->membership;
                $data['balance'] = $deposit->balance;
                $data['date'] = $deposit->created_at->format('Y-m-d');
                array_push($balances, $data);
            }
            return $this->activities->successReponse('balances', $balances);
        }
        return $this->activities->logout(JWTAuth::getToken());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($user = Auth::user())->role_id == 5) {
            $count = $user->deposits->where('status', 2)->first();
            if ($count->balance < $request->balance) {
                return $this->activities->errorResponse('Saldo insuficiente para compartir');
            }
            if ($request->balance <= 0 || $request->balance == '') {
                return $this->activities->errorResponse('El saldo a compartir debe ser mayor a cero');
            }
            if ($request->membership == $user->client->membership) {
                return $this->activities->errorResponse('La membresia del usuario no existe');
            }
            if (($beneficiary = Client::where('membership', $request->membership)->first()) != null) {
                SharedBalance::create($request->merge(['sponsor_id' => $user->id, 'beneficiary_id' => $beneficiary->user->id, 'status' => 3])->all());
                if (($balance = SharedBalance::where([['sponsor_id', $user->id], ['beneficiary_id', $beneficiary->user->id], ['status', 2]])->first()) != null) {
                    $balance->balance += $request->balance;
                    $balance->save();
                } else {
                    SharedBalance::create($request->merge(['status' => 2])->all());
                }
                $count->balance -= $request->balance;
                $count->save();
                return $this->activities->successReponse('message', 'Saldo compartido correctamente');
            }
            return $this->activities->errorResponse('La membresia del usuario no existe');
        }
        return $this->activities->logout(JWTAuth::getToken());
    }  

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
