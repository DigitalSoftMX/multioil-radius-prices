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
use App\Models\AliasStation;

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
            //Muchas veces el siclo jeje correjir
            error_log('********stations: '.count($stations));
            $i = 0;
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

                /* ******Start station name con cree y google ****** */
                $key        = 'AIzaSyDAYDRUB8-MNmO6JAy0aHaNaOKmE5VZHpI';
                $type       = 'gas_station';
                $location   = $station['latitude'].','.$station['longitude'];
                $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=$key&location=$location&type=$type&radius=20";
                $content = file_get_contents($url);
                $apiPlacesGoogle = json_decode($content);
                if (count($apiPlacesGoogle->results) == 0) {
                    $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=$key&location=$location&type=$type&keyword=$type&radius=10";
                    $content = file_get_contents($url);
                    $apiPlacesGoogle = json_decode($content);
                    if (count($apiPlacesGoogle->results) == 0 && $apiPlacesGoogle->status == 'ZERO_RESULTS') {
                        $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=$key&location=$location&type=$type&radius=40";
                        $content = file_get_contents($url);
                        $apiPlacesGoogle = json_decode($content);
                    }
                }
                error_log('Num stations google: '.count($apiPlacesGoogle->results).' cree location: '.$location. ' place_id: '.$station['place_id']);
                //echo '<pre>';print_r(json_decode($content));echo '</pre>';die();
                if (!empty($apiPlacesGoogle)) {
                    $i++;
                    // error_log('Entra foreach station: '.$i);
                    foreach ($apiPlacesGoogle->results as $c) {
                        if (isset($c->business_status) && $c->business_status == 'OPERATIONAL') {
                            //Guardar si hay mas de una estacion en un radio 10
                            // error_log('google location: '.$c->geometry->location->lat.','.$c->geometry->location->lng . ' name: '.$c->name);
                            if (isset($c->name) && isset($c->place_id) && isset($c->user_ratings_total) && isset($c->vicinity)) {
                                if (count($apiPlacesGoogle->results) >= 1) {
                                    //Buscar en alias_station si existe no guaradar
                                    $alias_find = AliasStation::where('g_placeid','like','%'.$c->place_id.'%')->first();
                                    if (is_null($alias_find)) {
                                        //Obtener id de la cree no de table station
                                        $cree = Cree::where('place_id','=',$station['place_id'])->first();
                                        // error_log('Cree id:'.$cree->id.' name: '.$cree->name);
                                        $alias = AliasStation::create([
                                            'name'              => $c->name,
                                            'g_placeid'         => $c->place_id,
                                            'user_rating_total' => $c->user_ratings_total,
                                            'vicinity'          => $c->vicinity,
                                            'cree_id'           => $cree->id,
                                        ]);
                                        error_log('alias estacion: '. json_encode($alias));
                                    }
                                }
                                //Cambiar el name en tabla de la cree
                                /* if (count($apiPlacesGoogle->results) == 1) {
                                    $cree = Cree::where('place_id','=',$station['place_id'])->first();
                                    error_log('Cree name:'.$cree->name.' google name: '.$c->name);
                                    if ($cree->name != $c->name) {
                                        error_log('Cambiar name en la cree id: '.$cree->id);
                                        $cree->update([
                                            'name'  => $c->name,
                                        ]);
                                    }
                                } */
                            }
                        }
                    }
                    error_log('-----FIN data google-----');
                }
                // if ($i == 5) {
                //     return response()->json(['data apiPlacesGoogle'=>$apiPlacesGoogle],200);
                // }
                /* ************End Station name*************** */
            }

        } catch (Exception $e) {
            return response()->json(['error'=>$e],400);
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
                            // echo '<pre>'; print_r($p); echo '</pre>';die();
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

                            /* ***************INICIO*************** */
                                /* $key        = 'AIzaSyDAYDRUB8-MNmO6JAy0aHaNaOKmE5VZHpI';
                                $location   = $request->latitude.','.$request->longitude;
                                $type       = 'gas_station';
                                error_log('key: '.$key.' location: '.$location.' type:'.$type);
                                $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=$key&location=$location&type=$type&keyword=$type&radius=10";
                                $content = file_get_contents($url);
                                $apiPlacesGoogle = json_decode($content);

                                foreach ($apiPlacesGoogle->results as $c) {
                                    return response()->json(['data'=>$c],200);
                                } */
                            /* ***************FIN****************** */
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

    //Funcion para guardar el promedio mediante una lista
    public function updateList(Request $request)
    {
        //Pendiente
    }


}
