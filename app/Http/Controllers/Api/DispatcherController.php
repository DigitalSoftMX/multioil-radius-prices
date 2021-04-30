<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Activities;
use App\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Schedule;
use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DispatcherController extends Controller
{
    private $activities, $user, $station;
    public function __construct(Activities $activities)
    {
        $this->activities = $activities;
        $this->user = Auth::user();
        if ($this->user != null && $this->user->role_id == 4) {
            $this->station = $this->user->dispatcher->station;
        } else {
            $this->activities->logout(JWTAuth::getToken());
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $schedule = Schedule::where('station_id', $this->station->id)->whereTime('start', '<=', now()->format('H:i'))->whereTime('end', '>=', now()->format('H:i'))->first();
        if (($time = $this->user->times->last()) != null) {
            $sales = Sale::where([['dispatcher_id', $this->user->id], ['time_id', $time->id]])->whereDate('created_at', now()->format('Y-m-d'))->get();
        }
        $data['name'] = $this->user->name . ' ' . $this->user->first_surname;
        $data['station'] = $this->station->name;
        $data['schedule'] = $schedule->name;
        $data['number_sales'] = (isset($sales)) ? count($sales) : 0;
        $data['total_sales'] = (isset($sales)) ? $sales->sum('payment') : 0;
        return $this->activities->successReponse('dispatcher', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        $data['name'] = $this->user->name;
        $data['first_surname'] = $this->user->first_surname;
        $data['second_surname'] = $this->user->second_surname;
        $data['phone'] = $this->user->phone;
        return $this->activities->successReponse('data', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'first_surname' => 'required|string',
            'second_surname' => 'required|string',
            'phone' => ['required', 'string', 'min:10', Rule::unique((new User)->getTable())->ignore($this->user->id ?? null)],
        ]);
        if ($validator->fails()) {
            return $this->activities->errorResponse($validator->errors(), 11);
        }
        $this->user->update($request->only(['name', 'first_surname', 'second_surname', 'phone']));
        return $this->activities->successReponse('message', 'Perfil actualizado correctamente');
    }
}
