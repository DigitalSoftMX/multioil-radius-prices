<?php

namespace App\Http\Controllers\Api;

use App\Deposit;
use App\Http\Controllers\Controller;
use App\Repositories\Activities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class DepositController extends Controller
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
            $deposits = $this->activities->getBalances($request, new Deposit, [['user_id', $user->id], ['status', 1]]);
            if (is_bool($deposits)) {
                return $this->activities->errorResponse('Las fechas son incorrectas.', 12);
            }
            if ($deposits->count() == 0) {
                return $this->activities->errorResponse('No cuenta con depositos en la cuenta', 13);
            }
            $balances = array();
            foreach ($deposits as $deposit) {
                $data['balance'] = $deposit->balance;
                $data['date'] = $deposit->created_at->format('Y-m-d');
                $data['hour'] = $deposit->created_at->format('H:i');
                array_push($balances, $data);
            }
            return $this->activities->successReponse('deposits', $balances);
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
            $validation = $this->activities->validateBalance($request);
            if (!(is_bool($validation))) {
                return $validation;
            }
            Deposit::create($request->merge(['user_id' => $user->id, 'status' => 1])->all());
            if (($balance = Deposit::where([['user_id', $user->id], ['status', 2]])->first()) != null) {
                $balance->balance += $request->balance;
                $balance->save();
            } else {
                Deposit::create($request->merge(['status' => 2])->all());
            }
            return $this->activities->successReponse('message', 'Abono realizado correctamente');
        }
        return $this->activities->logout(JWTAuth::getToken());
    }
}
