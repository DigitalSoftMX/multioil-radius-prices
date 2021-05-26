<?php

namespace App\Repositories;

use Tymon\JWTAuth\Facades\JWTAuth;

class Token extends ErrorSuccessLogout
{
    // Método para obtner el token de un usuario
    public function getToken($request, $user)
    {
        if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('El correo electrónico o la contraseña son incorrectos', 10);
        }
        $user->update(['remember_token' => $token]);
        if ($user->role_id == 5) {
            $user->client->update($request->only('ids'));
        }
        // verificar si la empresa a la que pertenece el despachador esta bloqueado
        return $this->successReponse('token', $token, $user->rol->name);
    }
}
