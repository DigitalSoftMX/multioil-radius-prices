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
    Route::get('stations', 'Api\SaleController@getStations');
    Route::get('prices', 'Api\SaleController@getPricesGasoline');
});
// Rutas para realizar depositos e historial
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('deposit', 'Api\DepositController@index');
    Route::post('deposit/store', 'Api\DepositController@store');
});
// Rutas para CRUD de compañeros
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('partner', 'Api\PartnerController@index');
    Route::post('partner/store', 'Api\PartnerController@store');
    Route::get('partner/show', 'Api\PartnerController@show');
    Route::post('partner/destroy', 'Api\PartnerController@destroy');
});
// Rutas para compartir despositos e historial
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('shared', 'Api\SharedController@index');
    Route::post('shared/store', 'Api\SharedController@store');
});
// Ruta para realizar un pago
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::post('sales/store', 'Api\SaleController@store');
});
// Rutas para el despachador
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('dispatcher/edit', 'Api\DispatcherController@edit');
    Route::post('dispatcher/update', 'Api\DispatcherController@update');
    Route::get('dispatcher/show', 'Api\DispatcherController@show');
    Route::get('schedules', 'Api\SaleController@getSchedules');
});
// Rutas para registrar ventas
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::post('time', 'Api\SaleController@startEndTime');
    Route::get('sales/index', 'Api\SaleController@index');
    Route::get('sales/create', 'Api\SaleController@create');
    Route::get('sales/show', 'Api\SaleController@show');
});
// Rutas para dueños de estación
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('owners', 'Api\StationOwnersController@index');
    Route::get('owners/placeclosetome', 'Api\StationOwnersController@placeCloseToMe');
    Route::post('setradio', 'Api\StationOwnersController@setRadio');
    // Route::get('notification', 'Api\StationOwnersController@notification');
});
