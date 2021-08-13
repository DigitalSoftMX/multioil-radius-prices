<?php

namespace App\Repositories;

use App\Cree;
use App\PriceCre;
use Exception;

class Activities
{
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
            $data['regular'] = $station->prices->last()->regular;
            $data['premium'] = $station->prices->last()->premium;
            $data['diesel'] = $station->prices->last()->diesel;
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
