<?php

namespace App\Repositories;

use App\Client;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Activities
{
    // Método para obtner el token de un usuario
    public function getToken($request, $user)
    {
        if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('El correo electrónico o la contraseña son incorrectos');
        }
        $user->update(['remember_token' => $token]);
        if ($user->role_id == 5) {
            $user->client->update($request->only('ids'));
        }
        return $this->successReponse('token', $token, $user->rol->name);
    }
    // Método para cerrar sesion por token
    public function logout($token, $success = false)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken($token));
            return ($success) ? $this->successReponse('message', 'Cierre de sesion correcto') : $this->errorResponse('Usuario no autorizado');
        } catch (Exception $e) {
            return $this->errorResponse('Token invalido');
        }
    }
    public function validateDataUser(Request $request, $user = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'first_surname' => 'required|string',
            'second_surname' => 'required|string',
            'email' => [
                'required', 'email', Rule::unique((new User)->getTable())->ignore($user->id ?? null)
            ],
            'birthdate' => 'required|date_format:Y-m-d',
            'sex' => 'required',
            'phone' => 'required|string|min:10',
            'address' => 'required',
            'car' => 'required',
            'RFC' => 'required',
            'business_email' => [
                'required', 'email', Rule::unique((new Client)->getTable())->ignore($user->client->id ?? null)
            ],
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }
        return true;
    }
    // Metodo para validar las fechas de consulta
    public function getBalances(Request $request, $model, $query, $all = false)
    {
        if (!$all) {
            $validator = Validator::make($request->only(['start', 'end']), [
                'start' => 'required|date_format:Y-m-d',
                'end' => 'required|date_format:Y-m-d',
            ]);
            if ($request->start > $request->end || $validator->fails()) {
                return false;
            }
        }
        $deposits = $all ? $model::where($query)->get() : $model::where($query)->whereDate('created_at', '>=', $request->start)->whereDate('created_at', '<=', $request->end)->get();
        return $deposits->sortByDesc('created_at');
    }
    // Método para validar abonos
    public function validateBalance(Request $request)
    {
        $validator = Validator::make($request->only('balance'), [
            'balance' => 'required|integer|min:100|exclude_if:balance,0'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors());
        }
        return true;
    }
    // Metodo para una respuesta correcta del servidor
    public function errorResponse($message)
    {
        return response()->json(['ok' => false, 'message' => $message]);
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
}