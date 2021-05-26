<?php

namespace App\Repositories;

use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class ErrorSuccessLogout
{
    // Método para cerrar sesion por token
    public function logout($token, $success = false)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return ($success) ? $this->successReponse('message', 'Cierre de sesion correcto') : $this->errorResponse('Usuario no autorizado', 403);
        } catch (Exception $e) {
            return $this->errorResponse('Token invalido', 400);
        }
    }
    // Metodo para una respuesta errónea del servidor
    public function successReponse($name, $data, $rol = null)
    {
        return ($rol != null) ?
            response()->json([
                'ok' => true,
                $name => $data,
                'rol' => $rol
            ]) :
            response()->json(['ok' => true, $name => $data]);
    }
    // Metodo para una respuesta correcta del servidor
    public function errorResponse($message, $code)
    {
        return response()->json(['ok' => false, 'code' => $code, 'message' => $message]);
    }
}
