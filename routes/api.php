<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', 'Api\AuthController@login');
Route::get('logout', 'Api\AuthController@logout');
Route::post('client/store', 'Api\ClientController@store');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
// Rutas para el cliente
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('client/show', 'Api\ClientController@show');
    Route::get('client/edit', 'Api\ClientController@edit');
    Route::post('client/update', 'Api\ClientController@update');
});
// Rutas para realizar depositos e historial
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('deposit', 'Api\DepositController@index');
    Route::post('deposit/store', 'Api\DepositController@store');
});
// Rutas para CRUD de compañeros
Route::group(['middleare' => 'jwtAuth'], function () {
    Route::get('partner', 'Api\PartnerController@index');
    Route::get('partner/show', 'Api\PartnerController@show');
});
// Rutas para compartir despositos e historial
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('shared', 'Api\SharedController@index');
    Route::post('shared/store', 'Api\SharedController@store');
});
