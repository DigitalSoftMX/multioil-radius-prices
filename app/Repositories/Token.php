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
        if ($user->role_id == 3) {
            // Validando el ingreso a un administrador de estación
            if ($user->stations->company->lock == 1) {
                $this->logout(JWTAuth::getToken(), false);
                return $this->errorResponse('Lo sentimos la empresa a la que está asociado esta suspendida, contacte al administrador', 403);
            }
            if ($user->stations->station->lock == 1) {
                $this->logout(JWTAuth::getToken(), false);
                return $this->errorResponse('Lo sentimos la estación a la que está asociado esta suspendida, contacte al administrador', 403);
            }
            if ($user->block == 1) {
                $this->logout(JWTAuth::getToken(), false);
                return $this->errorResponse('Lo sentimos su cuenta esta suspendida, contacte al administrador', 403);
            }
            $user->stations->update($request->only('ids'));
        }
        return $this->successReponse('token', $token, $user->rol->name);
    }
}
