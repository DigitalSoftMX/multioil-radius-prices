<?php

namespace App\Repositories;

use App\Station;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ValidationRequest
{
    // Método para validar los datos del cliente a registrar
    public function validateDataUser(Request $request, $station = false, $user = null)
    {
        if ($station) {
            $validator = Validator::make($request->all(), [
                'alias' => 'required|string|min:3',
                'address' => 'required|string|min:3',
                'phone' => [
                    'required', 'string', 'min:10', Rule::unique((new Station)->getTable())->ignore($user->id ?? null)
                ],
                'email' => [
                    'required', 'email', Rule::unique((new Station)->getTable())->ignore($user->id ?? null)
                ],
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3',
                'first_surname' => 'required|string|min:3',
                'second_surname' => 'required|string|min:3',
                'email' => [
                    'required', 'email', Rule::unique((new User)->getTable())->ignore($user->id ?? null)
                ],
                'phone' => [
                    'required', 'string', 'min:10', Rule::unique((new User)->getTable())->ignore($user->id ?? null)
                ],
                'password' => [
                    $user ? 'required_with:password_confirmation' : 'required', 'nullable', 'confirmed', 'min:8'
                ],
            ]);
        }
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
        return true;
    }
    // Metodo para la validadion de datos de las estaciones cercanas
    public function validateCoordinates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'placeid' => 'required|string',
            'radius' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return true;
    }
    // Validacion para rango de estaciones
    public function validateRadio(Request $request)
    {
        $validator = Validator::make($request->only('radio'), ['radio' => 'required|integer']);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return true;
    }
}
