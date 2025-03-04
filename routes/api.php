<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\Api\ItemTypesController;
use App\Http\Controllers\Api\ItemsController;
use App\Http\Controllers\Api\AGVController;
use App\Http\Controllers\Api\TasksController;
use App\Http\Controllers\Api\AuthController;





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


// Route::middleware('auth:sanctum')->get('/auth-check', [AuthController::class, 'checkAuth']);
Route::group(['middleware' => 'auth:api'], function(){
    Route::get('/auth-check', [AuthController::class, 'authCheck']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('log.action')->group(function () {
        
        //User
        Route::get('/user', [AuthController::class, 'getAllUser']);
        // Route::get('/user/{id}', [AuthController::class, 'getUserById']);
        Route::put('/user/{id}', [AuthController::class, 'updateUser']);
        Route::put('/user/{id}/isActive', [AuthController::class, 'changeIsActive']);
        Route::delete('/user/{id}', [AuthController::class, 'destroyUser']);
        Route::get('/user/logauth5', [AuthController::class, 'getLast5LogAuth']);
        Route::get('/user/authedLogAction', [AuthController::class, 'getLogActionByAuthUser']);
        Route::get('/user/logaction', [AuthController::class, 'getLogAction']);
        Route::get('/user/logauth', [AuthController::class, 'getLogAuth']);
        Route::post('/register', [AuthController::class, 'register']);

        //Station
        Route::get('/station', [StationController::class, 'index']);
        Route::get('/station/name/{station_name}', [StationController::class, 'show']);
        Route::post('/station', [StationController::class, 'store']);
        Route::put('/station/{id}', [StationController::class, 'updateById']);
        Route::delete('/station/{id}', [StationController::class, 'destroyById']);

         //Item Type
        Route::get('/itemtype', [ItemTypesController::class, 'index']);
        Route::get('/itemtype/name/{type_name}', [ItemTypesController::class, 'show']);
        Route::post('/itemtype', [ItemTypesController::class, 'store']);
        Route::put('/itemtype/{id}', [ItemTypesController::class, 'updateById']);
        Route::delete('/itemtype/{id}', [ItemTypesController::class, 'destroyById']);

        //Item
        Route::get('/item', [ItemsController::class, 'index']);
        Route::get('/item/{id}', [ItemsController::class, 'showById']);
        Route::post('/item', [ItemsController::class, 'store']);
        Route::put('/item/{id}', [ItemsController::class, 'updateById']);
        Route::delete('/item/{id}', [ItemsController::class, 'destroyById']);
        //Task
        Route::get('/task', [TasksController::class, 'index']);
        Route::get('/task/{id}', [TasksController::class, 'showById']);
        Route::get('/task/agv/{id_agv}', [TasksController::class, 'showByIdAGV']);
        Route::get('/processing_task', [TasksController::class, 'showProcessingTasks']);
        Route::get('/allocated_task', [TasksController::class, 'showAllocatedTasks']);
        Route::get('/waiting_task', [TasksController::class, 'showWaitingTasks']);
        Route::get('/done_task', [TasksController::class, 'showDoneTasks']);
        Route::get('/task/agv/{id_agv}/processing', [TasksController::class, 'showProcessingByIdAGV']);
        Route::get('/task/agv/{id_agv}/allocated', [TasksController::class, 'showAllocatedByIdAGV']);
        Route::get('/task/agv/{id_agv}/done', [TasksController::class, 'showDoneByIdAGV']);
        Route::post('/task', [TasksController::class, 'store']);
        Route::put('/task/{id}', [TasksController::class, 'updateById']);
        Route::delete('/task/{id}', [TasksController::class, 'destroyById']);
    });

    //AGV
    Route::get('/agv', [AGVController::class, 'index']);
    Route::get('/agv/{id}', [AGVController::class, 'showById']);
    Route::get('/agv/name/{agv_name}', [AGVController::class, 'showByName']);

    
});

Route::post('/login', [AuthController::class, 'login']);


