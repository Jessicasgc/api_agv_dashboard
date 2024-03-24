<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\Api\ItemTypesController;
use App\Http\Controllers\Api\ItemsController;
use App\Http\Controllers\Api\AGVController;
use App\Http\Controllers\Api\TasksController;



// use App\Http\Controllers;
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

//Station
Route::get('/station', [StationController::class, 'index']);
Route::get('/station/{station_name}', [StationController::class, 'show']);
Route::post('/station', [StationController::class, 'store']);
Route::put('/station/{id}', [StationController::class, 'updateById']);
Route::put('/station/{station_name}', [StationController::class, 'updateByName']);
Route::delete('/station/{id}', [StationController::class, 'destroy']);
//Route::get('/station', 'App\Http\Controllers\Api\StationController@index');

//Route::post('/station', 'App\Http\Controllers\Api\StationController@store');
//Route::put('/station/{id}', 'App\Http\Controllers\Api\StationController@update');

//Item Type
Route::get('/itemtype', [ItemTypesController::class, 'index']);
Route::get('/itemtype/{id}', [ItemTypesController::class, 'show']);
Route::post('/itemtype', [ItemTypesController::class, 'store']);
Route::put('/itemtype/{id}', [ItemTypesController::class, 'update']);
Route::delete('/itemtype/{id}', [ItemTypesController::class, 'destroy']);

//Item
Route::get('/item', [ItemsController::class, 'index']);
Route::get('/item/{id}', [ItemsController::class, 'show']);
Route::post('/item{id}', [ItemsController::class, 'store']);
Route::put('/item/{id}', [ItemsController::class, 'update']);
Route::delete('/item/{id}', [ItemsController::class, 'destroy']);

//AGV
Route::get('/agv', [AGVController::class, 'index']);
Route::get('/agv/{id}', [AGVController::class, 'show']);
Route::post('/agv/{id}', [AGVController::class, 'store']);
Route::put('/agv/{id}', [AGVController::class, 'update']);
Route::delete('/agv/{id}', [AGVController::class, 'destroy']);

//Task
Route::get('/task', [TasksController::class, 'index']);
Route::get('/task/{id}', [TasksController::class, 'show']);
Route::post('/task/{id}', [TasksController::class, 'store']);
Route::put('/task{id}', [TasksController::class, 'update']);
Route::delete('/task/{id}', [TasksController::class, 'destroy']);