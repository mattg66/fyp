<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ACI\StatusController as ACIStatus;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\VSphere\StatusController as VSphereStatus;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['prefix' => 'aci'], function () {
    Route::get('/version', [ACIStatus::class, 'getVersion']);
    Route::get('/health', [ACIStatus::class, 'getStatus']);
});

Route::group(['prefix' => 'vsphere'], function () {
    Route::get('/health', [VSphereStatus::class, 'getStatus']);
});

Route::group(['prefix' => 'node'], function () {
    Route::post('/', [NodeController::class, 'create']);
    Route::get('/{id}', [NodeController::class, 'getById']);
    Route::patch('/{id}', [NodeController::class, 'updateById']);
    Route::delete('/{id}', [NodeController::class, 'deleteById']);
    Route::get('/', [NodeController::class, 'getAll']);
});