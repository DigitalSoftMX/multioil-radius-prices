<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Http\Controllers\Controller;
use App\Repositories\Token;
use App\Repositories\ValidationRequest;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientController extends Controller
{
    private $validationRequest, $response;
    public function __construct(ValidationRequest $validationRequest, Token $response)
    {
        $this->validationRequest = $validationRequest;
        $this->response = $response;
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validation = $this->validationRequest->validateDataUser($request);
        if (!(is_bool($validation))) {
            return $this->response->errorResponse($validation, 11);
        }
        while (true) {
            $membership = 'C' . substr(Carbon::now()->format('Y'), 2) . rand(1000000, 9999999);
            if (!(Client::where('membership', $membership)->exists())) {
                $request->merge(['membership' => $membership]);
                break;
            }
        }
        $password = $request->password;
        $user = User::create($request->merge(['password' => bcrypt($request->password), 'role_id' => 5])->all());
        Client::create($request->merge(['user_id' => $user->id, 'points' => 0])->all());
        $request->merge(['password' => $password]);
        return $this->response->getToken($request, $user);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (($user = auth()->user())->role_id == 3) {
            $data['name'] = $user->name . ' ' . $user->first_surname . ' ' . $user->second_surname;
            // $data['membership'] = $user->client->membership;
            // $data['current_balance'] = $user->deposits->where('status', 2)->sum('balance');
            // $data['beneficiary'] = $user->beneficiary->where('status', 2)->sum('balance');
            return $this->response->successReponse('user', $data);
        }
        return $this->response->logout(JWTAuth::getToken());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        if (($user = auth()->user())->role_id == 3) {
            $data['name'] = $user->name;
            $data['first_surname'] = $user->first_surname;
            $data['second_surname'] = $user->second_surname;
            $data['email'] = $user->email;
            // $data['birthdate'] = $user->client->birthdate;
            // $data['sex'] = $user->client->sex;
            $data['phone'] = $user->phone;
            // $data['car'] = $user->client->car;
            return $this->response->successReponse('user', $data);
        }
        return $this->response->logout(JWTAuth::getToken());
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
        if (($user = auth()->user())->role_id == 3) {
            $validation = $this->validationRequest->validateDataUser($request, $user);
            if (!(is_bool($validation))) {
                return $this->response->errorResponse($validation, 11);
            }
            if ($request->password != '') {
                $request->merge(['password' => bcrypt($request->password)]);
                $user->update($request->only(['password']));
            }
            $user->update($request->only(['name', 'first_surname', 'second_surname', 'email', 'phone']));
            // $user->client->update($request->except(['user_id', 'membership', 'points', 'ids']));
            if ($request->password != '') {
                $this->response->logout(JWTAuth::getToken(), true);
                return $this->response->successReponse('message', 'Perfil y contraseña actualizados correctamente, Inicie sesion nuevamente');
            }
            return $this->response->successReponse('message', 'Perfil actualizado correctamente');
        }
        return $this->response->logout(JWTAuth::getToken());
    }
}
