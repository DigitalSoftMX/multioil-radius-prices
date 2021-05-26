<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Http\Controllers\Controller;
use App\Repositories\Activities;
use App\Repositories\ErrorSuccessLogout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class PartnerController extends Controller
{
    private $activities, $user, $response;
    public function __construct(Activities $activities, ErrorSuccessLogout $response)
    {
        $this->activities = $activities;
        $this->response = $response;
        $this->user = auth()->user();
        if ($this->user == null || $this->user->role_id != 5) {
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
        $partners = array();
        foreach ($this->user->partners as $partner) {
            array_push($partners, $this->activities->getContact($partner));
        }
        if (count($partners) > 0) {
            return $this->response->successReponse('partners', $partners);
        }
        return $this->response->errorResponse('Aún no tienes contactos agregados', 14);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return $this->activities->addOrDropContact($request, $this->user);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if (($client = Client::where([['membership', $request->membership], ['membership', '!=', $this->user->client->membership]])->first()) != null) {
            return $this->response->successReponse('partner', $this->activities->getContact($client));
        }
        return $this->response->errorResponse('La membresia del usuario no existe', 404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        return $this->activities->addOrDropContact($request, $this->user, false);
    }
}
