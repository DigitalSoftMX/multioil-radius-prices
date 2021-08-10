<?php

namespace App\Repositories;

use App\Client;
use App\Cree;
use App\PriceCre;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Activities
{
    // Metodo para obtener registros de compras,ventas o depositos
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
        $validator = Validator::make($request->all(), [
            'stripe_id' => 'required|string',
            'balance' => 'required|integer|min:50|exclude_if:balance,0',
            'currency' => 'required|string',
            'metadata' => ['required', 'string', 'min:2'],
            'amount_captured' => 'required|integer|min:50|exclude_if:balance,0',
            'created' => 'required|integer',
            'livemode' => 'required|integer',
            'payment_method' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $validator->errors();
            // return $this->errorResponse($validator->errors(), 11);
        }
        return true;
    }
    // Metodo para devolver la informacion de un contacto
    public function getContact($model)
    {
        $data['client_id'] = $model->id;
        $data['user_id'] = $model->user->id;
        $data['membership'] = $model->membership;
        $data['name'] = $model->user->name . ' ' . $model->user->first_surname . ' ' . $model->user->second_surname;
        return $data;
    }
    // Metodo para agregar o eliminar a un contacto
    public function addOrDropContact(Request $request, $user, $add = true)
    {
        $response = new ErrorSuccessLogout();
        if (Client::find($request->id) != null && $user->client->id != $request->id) {
            if ($add && $user->partners->contains($request->id)) {
                return $response->errorResponse('El contacto ya ha sido agregado anteriormente', 404);
            }
            $add ? $user->partners()->attach($request->id) : $user->partners()->detach($request->id);
            return $response->successReponse('message', $add ? 'Contacto agregado correctamente' : 'Contacto eliminado correctamente');
        }
        return $response->errorResponse('El contacto no existe', 404);
    }
    // Notificacion de cobro
    // Funcion para enviar una notificacion
    public function notification($message, $notification, $data = array(), $idsClient = null, $idsDispatcher = null)
    {
        $resp = new ErrorSuccessLogout();
        $ids = array();
        $idsClient != null ? array_push($ids, "$idsClient") : $ids;
        $idsDispatcher != null ? array_push($ids, "$idsDispatcher") : $ids;
        if (count($ids) < 1)
            return $resp->errorResponse('Falta permiso de notificacion', 404);
        $fields = array(
            'app_id' => "dddb8413-d9ef-4f54-b747-fe0269bc21b8",
            'contents' => array(
                "en" => "English message from postman",
                "es" => $message
            ),
            'data' => $data,
            'headings' => array(
                "en" => "English title from postman",
                "es" => $notification
            ),
            'include_player_ids' => $ids,
        );
        $fields = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec($ch);
        curl_close($ch);
        return $resp->successReponse('notification', \json_decode($response));
    }
    // Metodo para obtener lugares
    public function getStationsCloseToMe($place_id, $latitude, $longitude, $radio)
    {
        $stations = [];
        try {
            ini_set("allow_url_fopen", 1);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://publicacionexterna.azurewebsites.net/publicaciones/places');
            $contents = curl_exec($curl);
            $apiPlaces = simplexml_load_string($contents);
            foreach ($apiPlaces->place as $place) {
                if ($place['place_id'] != $place_id) {
                    if ($this->getDistanceBetweenPoints($latitude, $longitude, $place->location->y, $place->location->x, $radio)) {
                        $station['place_id'] = intval($place['place_id']);
                        $station['cre_id'] = strval($place->cre_id);
                        $station['name'] = strval($place->name);
                        $station['latitude'] = number_format(floatval($place->location->y), 5);
                        $station['longitude'] = number_format(floatval($place->location->x), 5);
                        array_push($stations, $station);
                    }
                }
            }
        } catch (Exception $e) {
        }
        return $stations;
    }

    // Actualizar precios y enviar notificacion
    public function notificationPricesAndOwners($stations)
    {
        // borrando historial de un mes atras
        $today = now()->format('Y-m-d');
        $today = date("Y-m-d", strtotime($today . "- 1 month"));
        foreach (PriceCre::whereDate('created_at', '<=', $today)->get() as $pricecree) {
            $pricecree->delete();
        }
        $idstations = [];
        try {
            ini_set("allow_url_fopen", 1);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://publicacionexterna.azurewebsites.net/publicaciones/prices');
            $contents = curl_exec($curl);
            $apiPrices = simplexml_load_string($contents);
            foreach ($stations as $s) {
                foreach ($apiPrices->place as $place) {
                    if ($place['place_id'] == $s->place_id) {
                        $change = false;
                        $lastprice = PriceCre::where('cree_id', $s->id)->whereDate('created_at', now()->format('Y-m-d'))->first();
                        if ($lastprice != null) {
                            $regular = $lastprice->regular;
                            $premium = $lastprice->premium;
                            $diesel = $lastprice->diesel;
                        }
                        $dataprice['cree_id'] = $s->id;
                        foreach ($place->gas_price as $price) {
                            $gastype = $price['type'];
                            $newprice = number_format((float) $price, 2);
                            $dataprice["$gastype"] = $newprice;
                            if ($lastprice != null) {
                                $lastprice->update(["$gastype" => $newprice]);
                                if ("$gastype" == 'regular') {
                                    if ($regular != $newprice)
                                        $change = true;
                                }
                                if ("$gastype" == 'premium') {
                                    if ($premium != $newprice)
                                        $change = true;
                                }
                                if ("$gastype" == 'diesel') {
                                    if ($diesel != $newprice)
                                        $change = true;
                                }
                            }
                        }
                        if ($lastprice == null) {
                            PriceCre::create($dataprice);
                            $change = true;
                        }
                        if ($change)
                            array_push($idstations, $s->id);
                        $change = false;
                        break;
                    }
                }
            }
        } catch (Exception $e) {
        }
        $places = [];
        $ids = [];
        foreach ($idstations as $id) {
            $station = Cree::find($id);
            $data['name'] = $station->name;
            $data['regular'] = $station->regular;
            $data['premium'] = $station->premium;
            $data['diesel'] = $station->diesel;
            array_push($places, $data);
            foreach ($station->admins as $user) {
                if (!in_array($user->stations->ids, $ids))
                    array_push($ids, $user->stations->ids);
            }
        }
        // Notificacion a los usuarios y lugares de cambio de precio
        foreach ($ids as $i) {
            $this->sendNotification($i, $places);
        }
    }
    // Envia de notifacion a los usuarios con sus estaciones correspondientes
    private function sendNotification($ids, $stations)
    {
        try {
            $fields = array(
                'to' => $ids,
                'notification' =>
                array(
                    'title' => 'Cambio de precio',
                    'body' => 'Estas estaciones cambiaron de precio'
                ),
                "priority" => "high",
                "data" => array(
                    "stations" => $stations
                ),
            );
            $headers = array('Authorization: key=AAAAQDS4lDQ:APA91bF0guhj8Ic6jMAhA2rC01UbAk29K-GlsonaXTHOc9B25muA7Er8HHC-eoBgBNtoLxmFvlPPGRM0AvvmILQuiFnbJKlBUIVBDYQHedF4BfDF1WeapDXcpAJkGfQb7l3GDOfBnifU', 'Content-Type: application/json');
            $url = 'https://fcm.googleapis.com/fcm/send';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            curl_close($ch);
            // return json_decode($result, true);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    // función para medir la distancia entre dos coordenadas
    private function getDistanceBetweenPoints($lat1, $lng1, $lat2, $lng2, $radius)
    {
        // El radio del planeta tierra en metros.
        $R = 6378137;
        $dLat = $this->degreesToRadians($lat2 - $lat1);
        $dLong = $this->degreesToRadians($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)  + cos($this->degreesToRadians($lat1))  *  cos($this->degreesToRadians($lat1))  * sin($dLong / 2)  *  sin($dLong / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $R * $c;

        if ($distance < $radius) {
            return true;
        }

        return false;
    }

    private function degreesToRadians($degrees)
    {
        return $degrees * pi() / 180;
    }
}
