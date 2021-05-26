<?php

namespace App\Http\Controllers\Api;

use App\Bomb;
use App\Client;
use App\Http\Controllers\Controller;
use App\RegisterTime;
use App\Repositories\Activities;
use App\Repositories\ErrorSuccessLogout;
use App\Repositories\ValidationRequest;
use App\Sale;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Schedule;
use App\User;
use Illuminate\Support\Facades\Validator;
use App\SharedBalance;
use App\Station;
use Exception;

class SaleController extends Controller
{
    private $user, $station, $validationRequest, $response;
    public function __construct(ValidationRequest $validationRequest, ErrorSuccessLogout $response)
    {
        $this->validationRequest = $validationRequest;
        $this->response = $response;
        $this->user = auth()->user();
        if ($this->user != null && ($this->user->role_id == 4 || $this->user->role_id == 5)) {
            if ($this->user->role_id == 4) {
                $this->station = $this->user->dispatcher->station;
            }
        } else {
            $this->response->logout(JWTAuth::getToken());
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $activities = new Activities();
        switch ($this->user->role_id) {
            case 4:
                $validator = Validator::make($request->only('schedule_id'), ['schedule_id' => 'required|integer']);
                if ($validator->fails()) {
                    return $this->response->errorResponse($validator->errors(), 11);
                }
                $sales = $activities->getBalances($request, new Sale, [['dispatcher_id', $this->user->id], ['schedule_id', $request->schedule_id]]);
                if (is_bool($sales)) {
                    return $this->response->errorResponse('Las fechas son incorrectas.', 12);
                }
                if ($sales->count() == 0) {
                    return $this->response->errorResponse('No cuenta con depositos en la cuenta', 13);
                }
                $data = array();
                foreach ($sales as $s) {
                    $sale['payment'] = $s->payment;
                    $sale['date'] = $s->created_at->format('Y/m/d');
                    $sale['hour'] = $s->created_at->format('H:i');
                    $sale['gasoline'] = $s->gasoline;
                    $sale['liters'] = $s->liters;
                    array_push($data, $sale);
                }
                return $this->response->successReponse('sales', $data);
            case 5:
                $shopping = $activities->getBalances($request, new Sale, [['client_id', $this->user->id]]);
                if (is_bool($shopping)) {
                    return $this->response->errorResponse('Las fechas son incorrectas.', 12);
                }
                if ($shopping->count() == 0) {
                    return $this->response->errorResponse('No cuenta con depositos en la cuenta', 13);
                }
                $data = array();
                foreach ($shopping as $s) {
                    $sale['sale'] = $s->sale;
                    $sale['date'] = $s->created_at->format('Y/m/d H:i');
                    $sale['station'] = $s->station->name;
                    $sale['gasoline'] = $s->gasoline;
                    $sale['payment'] = $s->payment;
                    $sale['liters'] = $s->liters;
                    array_push($data, $sale);
                }
                return $this->response->successReponse('shopping', $data);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if ($this->user->role_id == 4) {
            $validation = $this->validationRequest->validateSale($request);
            if (!(is_bool($validation))) {
                return $this->response->errorResponse($validation, 11);
            }
            if (Sale::where([
                ['sale', $request->sale],
                ['company_id', $this->user->dispatcher->station->company_id],
                ['station_id', $this->user->dispatcher->station_id]
            ])->exists()) {
                return $this->response->errorResponse('La venta ya ha sido registrada', 17);
            }
            $client = Client::where('membership', $request->membership)->first();
            if ($client == null) {
                return $this->response->errorResponse('El cliente no existe', 404);
            }
            if ($request->sponsor == null) {
                $deposit = $client->user->deposits()->where([['status', 2], ['balance', '>=', $request->payment]])->first();
            } else {
                $sponsor = Client::where('membership', $request->sponsor)->first();
                if ($sponsor == null) {
                    return $this->response->errorResponse('El beneficiario no existe', 404);
                }
                $deposit = SharedBalance::where([['sponsor_id', $sponsor->user_id], ['beneficiary_id', $client->user_id], ['status', 2], ['balance', '>=', $request->payment]])->first();
            }
            if ($deposit == null) {
                return $this->response->errorResponse('Saldo insuficiente', 15);
            }
            $request->merge(['dispatcher_id' => $this->user->id]);
            $notification = new Activities();
            return $notification->notification('Realizaste una solicitud de pago.', 'Pago con QR', $request->all(), $client->ids);
        }
        return $this->response->logout(JWTAuth::getToken());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($this->user->role_id == 5) {
            $validation = $this->validationRequest->validatePayment($request);
            if (!(is_bool($validation))) {
                return $this->response->errorResponse($validation, 11);
            }
            switch ($request->response) {
                case 'accept':
                    $dispatcher = User::find($request->dispatcher_id);
                    if ($dispatcher == null || $dispatcher->dispatcher == null) {
                        return $this->response->errorResponse('El despachador no existe', 404);
                    }
                    if (Sale::where([
                        ['sale', $request->sale],
                        ['company_id', $dispatcher->dispatcher->station->company_id],
                        ['station_id', $dispatcher->dispatcher->station_id]
                    ])->exists()) {
                        return $this->response->errorResponse('La venta ya ha sido registrada', 17);
                    }
                    if ($request->sponsor == null) {
                        $deposit = $this->user->deposits()->where([['status', 2], ['balance', '>=', $request->payment]])->first();
                    } else {
                        $sponsor = Client::where('membership', $request->sponsor)->first();
                        if ($sponsor == null) {
                            return $this->response->errorResponse('El beneficiario no existe', 404);
                        }
                        $deposit = $this->user->beneficiary()->where([['sponsor_id', $sponsor->user_id], ['status', 2], ['balance', '>=', $request->payment]])->first();
                    }
                    if ($deposit == null) {
                        return $this->response->errorResponse('Saldo insuficiente', 15);
                    }
                    $request->merge(isset($sponsor) ? ['sponsor_id' => $sponsor->user_id] : []);
                    $schedule = Schedule::where('station_id', $dispatcher->dispatcher->station_id)->whereTime('start', '<=', now()->format('H:i'))->whereTime('end', '>=', now()->format('H:i'))->first();
                    Sale::create(
                        $request->merge(
                            [
                                'company_id' => $dispatcher->dispatcher->station->company_id,
                                'station_id' => $dispatcher->dispatcher->station_id,
                                'client_id' => $this->user->id,
                                'dispatcher_id' => $dispatcher->id,
                                'time_id' => $dispatcher->times->last()->id,
                                'schedule_id' => $schedule->id
                            ]
                        )->all()
                    );
                    // Falta la suma de puntos para el cliente y el beneficiario
                    $deposit->balance -= $request->payment;
                    $deposit->save();
                    // $notification = new Activities();
                    // $notification->notification('Cobro realizado con éxito.', 'Pago con QR', $request->all(), $client->ids);
                    
                    return $this->response->successReponse('message', 'Cobro realizado correctamente');
                case 'deny':
                    // envio de notificacion para el despachador
                    return $this->response->successReponse('notification', 'Canceled');
                default:
                    return $this->response->errorResponse('Acción no reconocida', 18);
            }
        }
        return $this->response->logout(JWTAuth::getToken());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if ($this->user->role_id == 4) {
            $time = $this->user->times->last();
            if ($time == null || $time->status == 6) {
                return $this->response->errorResponse('Registre su turno para continuar', 16);
            }
            $data = array();
            if ($request->id == null) {
                foreach ($this->station->_bombs as $bomb) {
                    $b['id'] = $bomb->id;
                    $b['island-bomb'] = 'Isla ' . $bomb->island->number . ' - Bomba ' . $bomb->number;
                    array_push($data, $b);
                }
                return $this->response->successReponse('islands-bombs', $data);
            }
            $bomb = Bomb::find($request->id);
            if ($bomb != null && $bomb->station_id == $this->station->id) {
                /* codigo temporal de una venta 
            aqui es donde se obtiene el data de las ventas para el caso de no tener https,
            si hay https o api lo hace la aplicación */
                $gasolines = ['Magna', 'Premium', 'Diesel'];
                $prices = [19.44, 20.33, 22.13];
                $index = rand(0, 2);
                $liters = rand(16, 2400) / 16;
                $payment = $liters * $prices[$index];
                $sale = rand(1000000, 9999999);

                $data['gasoline'] = $gasolines[$index];
                $data['payment'] = $payment;
                $data['liters'] = $liters;
                $data['sale'] = $sale;
                $data['no_bomb'] = 2;
                $data['no_island'] = 1;
                return $this->response->successReponse('sale', $data);
            }
            return $this->response->errorResponse('El número de bomba no existe o la estación es incorrecta', 404);
        }
        return $this->response->logout(JWTAuth::getToken());
    }
    // Registro, inicio y fin de turno
    public function startEndTime(Request $request)
    {
        if ($this->user->role_id == 4) {
            $time = $this->user->times->last();
            switch ($request->time) {
                case 'start':
                    if ($time != null && $time->status == 4) {
                        return $this->response->errorResponse('Finalice el turno actual para iniciar otro', 16);
                    }
                    $schedule = Schedule::where('station_id', $this->station->id)->whereTime('start', '<=', now()->format('H:i'))->whereTime('end', '>=', now()->format('H:i'))->first();
                    RegisterTime::create($request->merge(['user_id' => $this->user->id, 'station_id' => $this->station->id, 'schedule_id' => $schedule->id, 'status' => 4])->all());
                    return $this->response->successReponse('message', 'Inicio de turno registrado');
                    // Posible codigo para pausar el turno
                case 'end':
                    if ($time != null && $time->status != 6) {
                        $time->update(['status' => 6]);
                        return $this->response->successReponse('message', 'Fin de turno registrado');
                    }
                    return $this->response->errorResponse('Turno no registrado o finalizado anteriormente', 16);
            }
            return $this->response->errorResponse('Registro no válido', 16);
        }
        return $this->response->logout(JWTAuth::getToken());
    }
    // Lista de los turnos de la estación
    public function getSchedules()
    {
        if ($this->user->role_id == 4) {
            $data = array();
            foreach ($this->station->schedules as $s) {
                $schedule['id'] = $s->id;
                $schedule['name'] = $s->name;
                array_push($data, $schedule);
            }
            return $this->response->successReponse('schedules', $data);
        }
        return $this->response->logout(JWTAuth::getToken());
    }
    // lista de las estaciones
    public function getStations()
    {
        if ($this->user->role_id == 5) {
            $stations = array();
            foreach (Station::all() as $station) {
                $data['id'] = $station->place_id;
                $data['name'] = $station->name;
                $data['address'] = $station->address;
                $data['phone'] = $station->phone;
                $data['email'] = $station->email;
                $data['latitude'] = $station->latitude;
                $data['longitude'] = $station->longitude;
                array_push($stations, $data);
            }
            return $this->response->successReponse('stations', $stations);
        }
        return $this->response->logout(JWTAuth::getToken());
    }
    // lista de precios de la estacion
    public function getPricesGasoline(Request $request)
    {
        if ($this->user->role_id == 5) {
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
                        return $this->response->successReponse('prices', $prices);
                    }
                }
                return $prices;
            } catch (Exception $e) {
                return $this->response->errorResponse('Intente más tarde', 19);
            }
        }
        return $this->response->logout(JWTAuth::getToken());
    }
}
