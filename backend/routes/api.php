<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ACI\StatusController as ACIStatus;
use App\Http\Controllers\ACI\FabricController as ACIFabric;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\RackController;
use App\Http\Controllers\TerminalServerController;
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
    Route::group(['prefix' => 'fabric/node'], function () {
        Route::get('/{id}/interfaces', [ACIFabric::class, 'getInterfaces']);
    });
    Route::group(['prefix' => 'fabric'], function () {
        Route::get('/', [ACIFabric::class, 'getNodes']);
    });
    
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

Route::group(['prefix' => 'ts'], function () {
    Route::post('/', [TerminalServerController::class, 'create']);
    Route::get('/{id}', [TerminalServerController::class, 'getById']);
    Route::patch('/{id}', [TerminalServerController::class, 'updateById']);
    Route::delete('/{id}', [TerminalServerController::class, 'deleteById']);
    Route::get('/', [TerminalServerController::class, 'getAll']);
});

Route::group(['prefix' => 'rack'], function () {
    Route::get('/', [RackController::class, 'getAll']);
});