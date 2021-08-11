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
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
// Rutas para el cliente
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('client/show', 'Api\ClientController@show');
    Route::get('client/edit', 'Api\ClientController@edit');
    Route::post('client/update', 'Api\ClientController@update');
    Route::get('prices', 'Api\SaleController@getPricesGasoline');
});
// Rutas para dueños de estación
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('owners', 'Api\StationOwnersController@index');
    Route::get('owners/placeclosetome', 'Api\StationOwnersController@placeCloseToMe');
    Route::post('setradio', 'Api\StationOwnersController@setRadio');
    // Route::get('notification', 'Api\StationOwnersController@notification');
});
