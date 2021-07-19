<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Activities;
use App\Repositories\Token;
use App\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private $token;
    public function __construct(Token $token, Activities $activities)
    {
        $this->token = $token;
        $this->activities = $activities;
    }
    public function login(Request $request)
    {
        if (($user = User::where('email', $request->email)->first()) == null) {
            return $this->token->errorResponse('El usuario no existe', 404);
        }
        return $this->token->getToken($request, $user);
    }
    // Metodo para cerrar sesion
    public function logout(Request $request)
    {
        return $this->token->logout($request->token, true);
    }
}
