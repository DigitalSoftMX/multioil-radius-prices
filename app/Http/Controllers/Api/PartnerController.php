<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Http\Controllers\Controller;
use App\Repositories\Activities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class PartnerController extends Controller
{
    protected $activities;
    public function __construct(Activities $activities)
    {
        $this->activities = $activities;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (($user = Auth::user())->role_id == 5) {
            $partners = array();
            foreach ($user->partners as $partner) {
                array_push($partners, $this->activities->getContact($partner));
            }
            if (count($partners) > 0) {
                return $this->activities->successReponse('partners', $partners);
            }
            return $this->activities->errorResponse('Aún no tienes contactos agregados', 14);
        }
        return $this->activities->logout(JWTAuth::getToken());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($user = Auth::user())->role_id == 5) {
            return $this->activities->addOrDropContact($request, $user);
        }
        return $this->activities->logout(JWTAuth::getToken());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if (($user = Auth::user())->role_id == 5) {
            if (($client = Client::where([['membership', $request->membership], ['membership', '!=', $user->client->membership]])->first()) != null) {
                return $this->activities->successReponse('partner', $this->activities->getContact($client));
            }
            return $this->activities->errorResponse('La membresia del usuario no existe', 404);
        }
        return $this->activities->logout(JWTAuth::getToken());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        if (($user = Auth::user())->role_id == 5) {
            return $this->activities->addOrDropContact($request, $user, false);
        }
        return $this->activities->logout(JWTAuth::getToken());
    }
}
