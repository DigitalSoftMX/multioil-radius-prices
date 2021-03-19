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
            return $user->partners;
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
        //
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
                $data['id'] = $client->id;
                $data['membership'] = $client->membership;
                $data['name'] = $client->user->name . ' ' . $client->user->first_surname . ' ' . $client->user->second_surname;
                return $this->activities->successReponse('partner', $data);
            }
            return $this->activities->errorResponse('La membresia del usuario no existe');
        }
        return $this->activities->logout(JWTAuth::getToken());
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
