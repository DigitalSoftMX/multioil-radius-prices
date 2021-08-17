<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ErrorSuccessLogout;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Exception;

class SaleController extends Controller
{
    private $user, $response;
    public function __construct(ErrorSuccessLogout $response)
    {
        $this->response = $response;
        $this->user = auth()->user();
        if ($this->user == null || $this->user->role_id != 3) {
            $this->response->logout(JWTAuth::getToken());
        }
    }

    // lista de precios de la estacion
    public function getPricesGasoline(Request $request)
    {
        $validator = Validator::make($request->only('id'), ['id' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->response->errorResponse($validator->errors(), 11);
        }
        try {
            ini_set("allow_url_fopen", 1);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://publicacionexterna.azurewebsites.net/publicaciones/prices');
            $contents = curl_exec($curl);
            $apiPrices = simplexml_load_string($contents);
            $prices = array();
            foreach ($apiPrices->place as $place) {
                if ($place['place_id'] == $request->id) {
                    foreach ($place->gas_price as $price) {
                        $prices["{$price['type']}"] = (float) $price;
                    }
                    // return $this->response->successReponse('prices', $prices);
                }
            }
            return count($prices) > 0 ?
                $this->response->successReponse('prices', $prices) :
                $this->response->errorResponse('Precios no disponibles', 404);;
            // return $prices;
        } catch (Exception $e) {
            return $this->response->errorResponse('Intente más tarde', 19);
        }
    }
}
