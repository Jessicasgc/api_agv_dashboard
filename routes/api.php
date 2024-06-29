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
    // Route::get('/profile', [AuthController::class, 'showProfile']);
    Route::put('/changePs', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    


    //User
    Route::get('/user', [AuthController::class, 'getAllUser']);
    Route::get('/user/{id}', [AuthController::class, 'getUserById']);
    Route::put('/user/{id}', [AuthController::class, 'updateUser']);

    Route::patch('/user/{id}/isActive', [AuthController::class, 'changeIsActive']);


    //Station
    Route::get('/station', [StationController::class, 'index']);
    Route::get('/station/name/{station_name}', [StationController::class, 'show']);
    Route::post('/station', [StationController::class, 'storee']);
    Route::put('/station/{id}', [StationController::class, 'updateById']);
    Route::put('/station/name/{station_name}', [StationController::class, 'updateByName']);
    Route::delete('/station/name/{station_name}', [StationController::class, 'destroyByName']);
    Route::delete('/station/{id}', [StationController::class, 'destroyById']);

    //Item Type
    Route::get('/itemtype', [ItemTypesController::class, 'index']);
    Route::get('/itemtype/name/{type_name}', [ItemTypesController::class, 'show']);
    Route::post('/itemtype', [ItemTypesController::class, 'store']);
    Route::put('/itemtype/name/{type_name}', [ItemTypesController::class, 'updateByName']);
    Route::put('/itemtype/{id}', [ItemTypesController::class, 'updateById']);
    Route::delete('/itemtype/{id}', [ItemTypesController::class, 'destroyById']);
    Route::delete('/itemtype/name/{type_name}', [ItemTypesController::class, 'destroyByName']);

    //Item
    Route::middleware('log.action:item')->group(function () {
        Route::get('/item', [ItemsController::class, 'index']);
        Route::get('/item/{id}', [ItemsController::class, 'showById']);
        Route::get('/item/name/{item_name}', [ItemsController::class, 'showByName']);
        Route::get('/item/code/{item_code}', [ItemsController::class, 'showByCode']);
        Route::post('/item', [ItemsController::class, 'store']);
        Route::put('/item/{id}', [ItemsController::class, 'updateById']);
        Route::put('/item/code/{item_code}', [ItemsController::class, 'updateByCode']);
        Route::delete('/item/{id}', [ItemsController::class, 'destroyById']);
        Route::delete('/item/code/{item_code}', [ItemsController::class, 'destroyByName']);
    });

    //AGV
    Route::get('/agv', [AGVController::class, 'index']);
    Route::get('/agv/{id}', [AGVController::class, 'showById']);
    Route::get('/agv/name/{agv_name}', [AGVController::class, 'showByName']);

    //Task
    Route::get('/task', [TasksController::class, 'index']);
    Route::get('/task/{id}', [TasksController::class, 'showById']);
    Route::get('/task/agv/{id_agv}', [TasksController::class, 'showByIdAGV']);
    Route::get('/task/name/{task_name}', [TasksController::class, 'showByName']);
    Route::get('/task/code/{task_code}', [TasksController::class, 'showByCode']);
    Route::get('/waiting_task', [TasksController::class, 'showWaitingTasks']);
    Route::get('/done_task', [TasksController::class, 'showDoneTasks']);
    Route::get('/task/agv/{id_agv}/processing', [TasksController::class, 'showProcessingByIdAGV']);
    Route::get('/task/agv/{id_agv}/allocated', [TasksController::class, 'showAllocatedByIdAGV']);
    Route::get('/task/agv/{id_agv}/done', [TasksController::class, 'showDoneByIdAGV']);
    Route::post('/task', [TasksController::class, 'store']);
    Route::put('/task/{id}', [TasksController::class, 'updateById']);
    Route::put('/task/code/{task_code}', [TasksController::class, 'updateByCode']);
    Route::delete('/task/{id}', [TasksController::class, 'destroyById']);
    Route::delete('/task/name/{task_name}', [TasksController::class, 'destroyByName']);
    Route::delete('/task/code/{task_code}', [TasksController::class, 'destroyByCode']);
});

// //User
Route::post('/forgetPs', [AuthController::class, 'changePasswordBeforeLogin']);
Route::post('/register', [AuthController::class, 'register']);
// Route::post('/changePr', [AuthController::class, 'changeProfile']);
// Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::delete('/user/{id}', [AuthController::class, 'deleteUser']);
// Route::post('/refresh', [AuthController::class, 'refresh']);
// Route::post('/me', [AuthController::class, 'me']);
