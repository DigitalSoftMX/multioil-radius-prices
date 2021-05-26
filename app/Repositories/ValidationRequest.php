<?php

namespace App\Repositories;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ValidationRequest
{
    // Método para validar los datos del cliente a registrar
    public function validateDataUser(Request $request, $user = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'first_surname' => 'required|string',
            'second_surname' => 'required|string',
            'email' => [
                'required', 'email', Rule::unique((new User)->getTable())->ignore($user->id ?? null)
            ],
            'password' => [
                $user ? 'required_with:password_confirmation' : 'required', 'nullable', 'confirmed', 'min:8'
            ],
            'phone' => [
                'required', 'string', 'min:10', Rule::unique((new User)->getTable())->ignore($user->id ?? null)
            ],
            'birthdate' => 'required|date_format:Y-m-d',
            'sex' => 'required',
            'car' => 'required',
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return true;
    }
    // Metodo para la validadion de datos antes de registrar la venta
    public function validateSale(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'membership' => 'required|string',
            'gasoline' => 'required|string',
            'payment' => 'required|numeric',
            'liters' => 'required|numeric',
            'sale' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return true;
    }
    // Método para registrar la venta o cobro
    public function validatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'response' => 'required|string',
            'gasoline' => 'required|string',
            'payment' => 'required|numeric',
            'liters' => 'required|numeric',
            'sale' => 'required|numeric',
            'dispatcher_id' => 'required|integer',
            'no_bomb' => 'required|integer',
            'no_island' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }
    }
}
