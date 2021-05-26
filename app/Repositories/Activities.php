<?php

namespace App\Repositories;

use App\Client;
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
            'balance_transaction' => 'required|string',
            'currency' => 'required|string',
            'metadata' => ['required', 'string', 'min:2'],
            'payment_intent' => 'required|string',
            'refunded' => 'required|integer',
            'stripe_status' => 'required|string',
            'amount_captured' => 'required|integer|min:50|exclude_if:balance,0',
            'amount_refunded' => 'required|integer',
            'calculated_statement_descriptor' => 'required|string',
            'created' => 'required|integer',
            'livemode' => 'required|integer',
            'paid' => 'required|integer',
            'payment_method' => 'required|string',
            'receipt_number' => 'required|string',
            'receipt_url' => 'required|string'
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
}
