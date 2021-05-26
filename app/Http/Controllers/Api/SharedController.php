<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Activities;
use App\Repositories\ErrorSuccessLogout;
use App\SharedBalance;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class SharedController extends Controller
{
    private $activities, $user, $response;
    public function __construct(Activities $activities, ErrorSuccessLogout $response)
    {
        $this->activities = $activities;
        $this->response = $response;
        $this->user = auth()->user();
        if ($this->user == null || $this->user->role_id != 5) {
            $this->response->logout(JWTAuth::getToken());
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $deposits = null;
        if ($request->value == 'sent') {
            $deposits = $this->activities->getBalances($request, new SharedBalance, [['sponsor_id', $this->user->id], ['status', 3]]);
        }
        if ($request->value == 'received') {
            if ($request->start == '' || $request->end == '') {
                $deposits = $this->activities->getBalances($request, new SharedBalance, [['beneficiary_id', $this->user->id], ['status', 2]], true);
            } else {
                $deposits = $this->activities->getBalances($request, new SharedBalance, [['beneficiary_id', $this->user->id], ['status', 3]]);
            }
        }
        if (is_bool($deposits)) {
            return $this->response->errorResponse('Las fechas son incorrectas.', 12);
        }
        if ($deposits->count() == 0) {
            return $this->response->errorResponse('No cuenta con depósitos en la cuenta', 13);
        }
        $balances = array();
        foreach ($deposits as $deposit) {
            $data[($request->value == 'sent') ? 'beneficiary' : 'sponsor'] = ($request->value == 'sent') ?
                $deposit->beneficiary->name . ' ' . $deposit->beneficiary->client->first_surname : $deposit->sponsor->name . ' ' . $deposit->sponsor->client->first_surname;
            $data['membership'] = ($request->value == 'sent') ? $deposit->beneficiary->client->membership : $deposit->sponsor->client->membership;
            $data['balance'] = $deposit->balance;
            $data['date'] = $deposit->created_at->format('Y-m-d');
            array_push($balances, $data);
        }
        return $this->response->successReponse('balances', $balances);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $count = $this->user->deposits->where('status', 2)->first();
        if ($count == null || $count->balance < $request->balance) {
            return $this->response->errorResponse('Saldo insuficiente para compartir', 15);
        }
        $validator = Validator::make($request->all(), [
            'balance' => 'required|integer|min:50|exclude_if:balance,0',
        ]);
        if ($validator->fails()) {
            return $this->response->errorResponse($validator->errors(), 11);
        }
        if (($beneficiary = User::find($request->id)) != null && $request->id != $this->user->id) {
            SharedBalance::create($request->merge(['sponsor_id' => $this->user->id, 'beneficiary_id' => $beneficiary->id, 'status' => 3])->all());
            if (($balance = SharedBalance::where([['sponsor_id', $this->user->id], ['beneficiary_id', $beneficiary->id], ['status', 2]])->first()) != null) {
                $balance->balance += $request->balance;
                $balance->save();
            } else {
                SharedBalance::create($request->merge(['status' => 2])->all());
            }
            $count->balance -= $request->balance;
            $count->save();
            return $this->response->successReponse('message', 'Saldo compartido correctamente');
        }
        return $this->response->errorResponse('La membresia del usuario no existe', 404);
    }
}
