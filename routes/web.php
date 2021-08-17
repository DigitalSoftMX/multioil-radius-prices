<?php

use App\Repositories\Activities;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Obteniendo la estructura de notificacion
Route::get('notification', function () {
    $activities = new Activities();
    foreach (User::where('role_id', 3)->get() as $admin) {
        $activities->notificationPricesAndOwners($admin->stationscree);
    }
});
Route::get('/', function () {
    return view('auth.login');
});
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
