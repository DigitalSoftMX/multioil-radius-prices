<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Activities;
use App\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    //protected $activities;
    public function __construct(Activities $activities)
    {
        $this->activities = $activities;
    }
    public function login(Request $request)
    {
        if (($user = User::where('email', $request->email)->first()) == null) {
            return $this->activities->errorResponse('El usuario no existe');
        }
        return $this->activities->getToken($request, $user);
    }
    // Metodo para cerrar sesion
    public function logout(Request $request)
    {
        return $this->activities->logout($request->token, true);
    }
}
