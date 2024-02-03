<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\RakController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\KaryawanController;
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
Route::post('/login', [UserController::class, 'login']);
Route::post('/signup', [UserController::class, 'signUp']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/tambahtransaksi', [TransaksiController::class, 'tambahTransaksi']);
    Route::post('/transaksiKeluar', [TransaksiController::class, 'transaksiKeluar']);
    Route::get('/getDetailTransaksi/{receiptID}', [TransaksiController::class, 'getDetailTransaksi']);
    Route::delete('/deleteTransaksi/{receiptID}', [TransaksiController::class, 'deleteTransaksi']);
    // admin page
    Route::get('/getadmin', [AdminController::class, 'getAdmin']);
    Route::get('/getStatistik', [AdminController::class, 'getStatistik']);
    Route::get('/manageUser', [AdminController::class, 'manageUser']);
    Route::post('/changeStatus/{id}', [AdminController::class, 'changeStatus']);
    Route::delete('/deleteUser/{id}', [AdminController::class, 'deleteUser']);
    Route::get('/getrak', [RakController::class, 'getAllRaks']);
    Route::get('/getTransaksi', [TransaksiController::class, 'getAllTransaksi']);
    Route::post('/tambahRakdanSlot', [RakController::class, 'tambahRakDanSlot']);
    Route::post('/changeStatusRak/{idrak}', [RakController::class, 'changeStatusRak']);
    Route::get('/getRakbyID/{idrak}', [RakController::class, 'getRakbyID']);
    Route::get('/getRakSlot', [RakController::class, 'getRakSlot']);
    Route::get('/rak/{idrak}/rakslots', [RakController::class, 'getByRakId']);
    Route::post('/updateProfile', [AdminController::class, 'updateProfile']);
    Route::get('/getProduk', [ProdukController::class, 'getProduk']);
    Route::post('/tambahJenisProduk', [ProdukController::class, 'tambahJenisProduk']);
    Route::post('/tambahproduk', [ProdukController::class, 'tambahProduk']);
    Route::delete('/deleteProduk/{idproduk}', [ProdukController::class, 'deleteProduk']);
    //karyawan page
    Route::get('/getKaryawan', [KaryawanController::class, 'getKaryawan']);
    Route::get('/getTransaksibyKaryawan', [TransaksiController::class, 'getTransaksibyKaryawan']);
    Route::post('/updateProfileKaryawan', [KaryawanController::class, 'updateProfileKaryawan']);
    Route::get('/getAllTransaksiReport', [TransaksiController::class, 'getAllTransaksiReport']);
});

Route::get('/public/storage/photo/{filename}', function ($filename) {
    $path = public_path() . '/storage/photo/' . $filename;

    if (!File::exists($path)) {
        return response()->json(['message' => 'Image not found.'], 404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});