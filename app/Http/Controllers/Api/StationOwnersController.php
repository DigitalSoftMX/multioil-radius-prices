<?php

namespace App\Http\Controllers\Api;

use App\Cree;
use App\Http\Controllers\Controller;
use App\Repositories\Activities;
use Illuminate\Http\Request;
use App\Repositories\ValidationRequest;
use App\Repositories\ErrorSuccessLogout;
use App\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class StationOwnersController extends Controller
{
    private $activities, $user, $response, $validationRequest;
    public function __construct(ValidationRequest $validationRequest, ErrorSuccessLogout $response, Activities $activities)
    {
        //$this->activities = $activities;
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

        $station = [];
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
            $prices = array();
            foreach ($stations as $station) {
                $station['place_id'] = $station['place_id'];
                $station['cre_id'] = $station['cre_id'];
                $station['name'] = $station['name'];
                $station['latitude'] = $station['latitude'];
                $station['longitude'] = $station['longitude'];
                foreach ($apiPrices->place as $place) {
                    if ($place['place_id'] == $station['place_id']) {
                        foreach ($place->gas_price as $price) {
                            $prices["{$price['type']}"] = number_format((float) $price, 2);
                        }
                        $station['prices'] =  $prices;
                    }
                }
                array_push($newStations, $station);
            }
        } catch (Exception $e) {
            // return $coordinates;
        }

        if (count($newStations) > 0) {
            return $this->response->successReponse('stations', $newStations);
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
}
