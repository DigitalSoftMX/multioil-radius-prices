<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
//use App\Repositories\Activities;
use App\Repositories\ValidationRequest;
use App\Repositories\ErrorSuccessLogout;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Station;
 

class StationOwnersController extends Controller
{
    private $activities, $user, $response, $validationRequest;
    public function __construct(ValidationRequest $validationRequest, ErrorSuccessLogout $response)
    {
        //$this->activities = $activities;
        $this->validationRequest = $validationRequest;
        $this->response = $response;
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
        if($this->user->stations != null) {
            if($this->user->stations->station->count() == 0)
                return $this->response->errorResponse('No hay estaciones asignadas.', 13);
            return $this->response->successReponse('station', $this->user->stations->station->makeHidden(['lock','islands','bombs','commission_ds','commission_client','bill','created_at','updated_at',]));
        }else{
            return $this->response->errorResponse('No hay empresa asignada.', 13);
        }
    }

    // función para obtener las estaciónes cerca de unas cordenadas
    public function placeCloseToMe(Request $request){
        $validation = $this->validationRequest->validateCoordinates($request);
        if (!(is_bool($validation))) {
            return $this->response->errorResponse($validation, 11);
        }

        $stations = array();

        $station = [];
        try {

            ini_set("allow_url_fopen", 1);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://publicacionexterna.azurewebsites.net/publicaciones/places');
            $contents = curl_exec($curl);
            $apiPlaces = simplexml_load_string($contents);
            foreach ($apiPlaces->place as $place) {
                if($place['place_id'] != $request->placeid){
                    if($this->getDistanceBetweenPoints($request->latitude, $request->longitude, $place->location->y,$place->location->x, $request->radius)){
                        $station['place_id'] = intval($place['place_id']);
                        $station['cre_id'] = strval($place->cre_id);
                        $station['name'] = strval($place->name);
                        $station['latitude'] = floatval($place->location->y);
                        $station['longitude'] = floatval($place->location->x);
                        array_push($stations, $station);
                    }
                }
            }

            if(count($stations) == 0){
                return $this->response->errorResponse('No hay estaciones cerca.', 13);
            }
        
            $station = [];
            $newStations = [];
            
            curl_setopt($curl, CURLOPT_URL, 'https://publicacionexterna.azurewebsites.net/publicaciones/prices');
            $contents = curl_exec($curl);
            $apiPrices = simplexml_load_string($contents);
            $prices = array();
            foreach($stations as $station){
                $station['place_id'] = $station['place_id'];
                $station['cre_id'] = $station['cre_id'];
                $station['name'] = $station['name'];
                $station['latitude'] = $station['latitude'];
                $station['longitude'] = $station['longitude'];
                foreach ($apiPrices->place as $place) {
                    if ($place['place_id'] == $station['place_id']) {
                        foreach ($place->gas_price as $price) {
                            $prices["{$price['type']}"] = (float) $price;
                        }
                        $station['prices'] =  $prices;
                    }
                }
                array_push($newStations, $station);
            }
        } catch (Exception $e) {
            return $coordinates;
        }

        if(count($newStations) > 0){
            return $this->response->successReponse('stations',$newStations);
        }

        return $this->response->errorResponse('No hay estaciones cerca.', 13);
    }

    public function degreesToRadians($degrees){
        return $degrees * pi() / 180;
    }

    // función para medir la distancia entre dos coordenadas
    public function getDistanceBetweenPoints($lat1, $lng1, $lat2, $lng2, $radius){
        // El radio del planeta tierra en metros.
        $R = 6378137;
        $dLat = $this->degreesToRadians($lat2 - $lat1);
        $dLong = $this->degreesToRadians($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)  + cos($this->degreesToRadians($lat1))  *  cos($this->degreesToRadians($lat1))  * sin($dLong / 2)  *  sin($dLong / 2);
    
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $R * $c;
      
        if($distance < $radius){
          return true;
        }
    
        return false;
    }

    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
