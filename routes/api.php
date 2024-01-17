<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CreateController;
use App\Http\Controllers\RakController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

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

Route::post('/signup', [UserController::class, 'signUp']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/tambahproduk', [CreateController::class, 'tambahProduk']);
Route::post('/tambahrak', [CreateController::class, 'tambahRak']);
Route::post('/tambahrakslot', [CreateController::class, 'tambahRakslot']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/tambahtransaksi', [CreateController::class, 'tambahTransaksi']);
    Route::get('/getadmin', [AdminController::class, 'getAdmin']);
    Route::get('/getrak', [RakController::class, 'getAllRaks']);
    Route::get('/getTransaksi', [TransaksiController::class, 'getAllTransaksi']);
    Route::get('/getRakbyID/{idrak}', [RakController::class, 'getRakbyID']);
    Route::get('/getRakSlot', [RakController::class, 'getRakSlot']);
    Route::get('/rak/{idrak}/rakslots', [RakController::class, 'getByRakId']);
});