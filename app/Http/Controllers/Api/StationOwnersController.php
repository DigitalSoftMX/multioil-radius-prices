<?php

namespace App\Http\Controllers\Api;

use App\Cree;
use App\Http\Controllers\Controller;
use App\Repositories\Activities;
use Illuminate\Http\Request;
use App\Repositories\ValidationRequest;
use App\Repositories\ErrorSuccessLogout;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class StationOwnersController extends Controller
{
    private $activities, $user, $response, $validationRequest;
    public function __construct(ValidationRequest $validationRequest, ErrorSuccessLogout $response, Activities $activities)
    {
        $this->validationRequest = $validationRequest;
        $this->response = $response;
        $this->activities = $activities;
        $this->user = auth()->user();
        if ($this->user == null || $this->user->role_id != 3) {
            $this->response->logout(JWTAuth::getToken());
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if ($this->user->stations != null) {
            if ($this->user->stations->station->count() == 0)
                return $this->response->errorResponse('No hay estaciones asignadas.', 13);
            return $this->response->successReponse('station', $this->user->stations->station->makeHidden(['lock', 'islands', 'bombs', 'commission_ds', 'commission_client', 'bill', 'created_at', 'updated_at',]));
        } else {
            return $this->response->errorResponse('No hay empresa asignada.', 13);
        }
    }

    // función para obtener las estaciónes cerca de unas cordenadas
    public function placeCloseToMe(Request $request)
    {
        $validation = $this->validationRequest->validateCoordinates($request);
        if (!(is_bool($validation))) {
            return $this->response->errorResponse($validation, 11);
        }

        $stations = array();

        try {
            $stations = $this->activities->getStationsCloseToMe($request->placeid, $request->latitude, $request->longitude, $request->radius);
            if (count($stations) == 0) {
                return $this->response->errorResponse('No hay estaciones cerca.', 13);
            }
            $station = [];
            $newStations = [];
            ini_set("allow_url_fopen", 1);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://publicacionexterna.azurewebsites.net/publicaciones/prices');
            $contents = curl_exec($curl);
            $apiPrices = simplexml_load_string($contents);
            $promPrices = array();
            foreach ($stations as $station) {
                $station['place_id'] = $station['place_id'];
                $station['cre_id'] = $station['cre_id'];
                $station['name'] = $station['name'];
                $station['latitude'] = $station['latitude'];
                $station['longitude'] = $station['longitude'];
                $prices = array();
                foreach ($apiPrices->place as $place) {
                    if ($place['place_id'] == $station['place_id']) {
                        foreach ($place->gas_price as $price) {
                            $prices["{$price['type']}"] = number_format((float) $price, 2);
                        }
                        $station['prices'] =  $prices;
                    }
                }
                array_push($promPrices, $station['prices']);
                array_push($newStations, $station);
            }

        } catch (Exception $e) {
            // return $coordinates;
        }

        if (count($newStations) > 0) {
            //Promedio radio
            if (is_array($promPrices)) {
                $regular = array(); $premium = array(); $diesel = array();
                foreach($promPrices as $p_prices){
                    if (isset($p_prices['regular']) && !is_null($p_prices['regular'])) {
                        array_push($regular,$p_prices['regular']);
                    }
                    if (isset($p_prices['premium']) && !is_null($p_prices['premium'])) {
                        array_push($premium, $p_prices['premium']);
                    }
                    if (isset($p_prices['diesel']) && !is_null($p_prices['diesel'])) {
                        array_push($diesel, $p_prices['diesel']);
                    }
                }
                $newPromPrices = array();
                if (is_array($regular)) {
                    $newPromPrices['regular'] = array_count_values($regular);
                }
                if (is_array($premium)) {
                    $newPromPrices['premium'] = array_count_values($premium);
                }
                if (is_array($diesel)) {
                    $newPromPrices['diesel'] = array_count_values($diesel);
                }
                $data = ['stations'=> $newStations, 'promPrices'=>$newPromPrices];
            }
            return $this->response->successReponse('data', $data);
        }

        return $this->response->errorResponse('No hay estaciones cerca.', 13);
    }


    // Establece el rango en distancia entre el usuario estacion y las estaciones de competencia
    public function setRadio(Request $request)
    {
        $validation = $this->validationRequest->validateRadio($request);
        if (!(is_bool($validation))) {
            return $this->response->errorResponse($validation, 11);
        }
        $this->user->stations->update($request->only('radio'));
        $placeId = $this->user->stations->station->place_id;
        $latitude = $this->user->stations->station->latitude;
        $longitude = $this->user->stations->station->longitude;
        $radio = $this->user->stations->radio;

        $stations = $this->activities->getStationsCloseToMe($placeId, $latitude, $longitude, $radio);
        // Registro de las estaciones cerca
        foreach ($stations as $s) {
            if (!Cree::where('place_id', $s['place_id'])->exists()) {
                $cree = Cree::create($s);
                $this->user->stationscree()->attach($cree->id);
            }
        }
        // Registro de relacion con las estaciones
        foreach ($stations as $s) {
            $cree = Cree::where('place_id', $s['place_id'])->first()->id;
            if (!$this->user->stationscree->contains($cree)) {
                $this->user->stationscree()->attach($cree);
            }
        }
        $this->activities->notificationPricesAndOwners($this->user->stationscree);
        return $this->response->successReponse('message', 'Rango de estación actualizado.');
    }

    // función para obtener las estaciónes cerca de unas cordenadas
    public function stationsNearLocation(Request $request)
    {
        $validation = $this->validationRequest->validateCoordinates($request);
        if (!(is_bool($validation))) {
            return $this->response->errorResponse($validation, 11);
        }
        $stations = array();
        try {
            $stations = Cree::all();
            if (count($stations) == 0) {
                return $this->response->errorResponse('No hay estaciones cerca.', 13);
            }
            $station    = [];
            $newStations = [];
            $promPrices = array();
            foreach ($stations as $temp) {
                if (!empty($temp)) {
                    if ($request->placeid != $temp['place_id']) {
                        if ($this->activities->getDistanceBetweenPoints($request->latitude, $request->longitude,$temp['latitude'],$temp['longitude'],$request->radius)){
                            $station['place_id']    = $temp['place_id'];
                            $station['cre_id']      = $temp['cre_id'];
                            $station['name']        = $temp['name'];
                            $station['latitude']    = $temp['latitude'];
                            $station['longitude']   = $temp['longitude'];
                            // echo '<pre>';
                            // print_r($p);
                            // echo '</pre>';die();
                            $prices = [];
                            if ($temp->prices) {
                                if (!is_null($temp->prices['regular'])) {
                                    $prices['regular'] = number_format((float) $temp->prices['regular'], 2);
                                }
                                if (!is_null($temp->prices['premium'])) {
                                    $prices['premium'] = number_format((float) $temp->prices['premium'], 2);
                                }
                                if (!is_null($temp->prices['diesel'])) {
                                    $prices['diesel'] = number_format((float) $temp->prices['diesel'], 2);
                                }
                                $station['prices'] = $prices;
                                array_push($promPrices, $prices);
                            }
                            array_push($newStations, $station);
                        }
                    }
                }
            }

        if (count($newStations) > 0) {
            //Promedio radio
            if (is_array($promPrices)) {
                $regular = array(); $premium = array(); $diesel = array();
                foreach($promPrices as $p_prices){
                    if (isset($p_prices['regular']) && !is_null($p_prices['regular'])) {
                        array_push($regular,$p_prices['regular']);
                    }
                    if (isset($p_prices['premium']) && !is_null($p_prices['premium'])) {
                        array_push($premium, $p_prices['premium']);
                    }
                    if (isset($p_prices['diesel']) && !is_null($p_prices['diesel'])) {
                        array_push($diesel, $p_prices['diesel']);
                    }
                }
                $newPromPrices = array();
                if (is_array($regular)) {
                    $newPromPrices['regular'] = array_count_values($regular);
                }
                if (is_array($premium)) {
                    $newPromPrices['premium'] = array_count_values($premium);
                }
                if (is_array($diesel)) {
                    $newPromPrices['diesel'] = array_count_values($diesel);
                }
                $data = ['stations'=> $newStations, 'promPrices'=>$newPromPrices];
            }else {
                return response()->json(['data'=>'No es array','message'=>'Success'], 200);
            }
            return $this->response->successReponse('data', $data);
        }
        } catch (Exception $e) {
            // return $coordinates;
            // return response()->json(['error'=>$e],400);
        }
        return $this->response->errorResponse('No hay estaciones cerca.', 13);
    }
}
