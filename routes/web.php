<?php

use Illuminate\Support\Facades\Route;
use App\Models\AGV;
use App\Services\WebsocketClient;
use App\Events\WebsocketEvent;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use App\Handlers\WebSocketHandler;
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

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/agv-event', function (){
//     event(new \App\Events\WebsocketEvent());
//     return null;
// });
Route::get('/send-agv-data', [\App\Http\Controllers\Api\AGVController::class, 'store']);


// Route::get('/dashboard', function () {
//     $server = IoServer::factory(
//         new HttpServer(
//             new WsServer(
//                 new WebSocketHandler()
//             )
//         ),
//         80
//     );

//     $server->run();
// });

// Route::get('/send-event', function (WebsocketClient $webSocketClient) {
//     // $agvData = AGV::all(); // You may need to adjust this based on your specific requirements

//     // // Check if AGV data is available
//     // if ($agvData->isEmpty()) {
//     //     // If AGV data is empty, return an error response
//     //     return response()->json([
//     //         'status' => 'error',
//     //         'message' => 'No AGV data available',
//     //     ], 404);
//     // }

//     // // Broadcast the AGV data using WebsocketEvent
//     // broadcast(new \App\Events\WebsocketEvent($agvData));

//     // // Return a success response
//     // return response()->json([
//     //     'status' => 'success',
//     //     'message' => 'AGV data broadcasted successfully',
//     //     'data' => $agvData
//     // ]);
//     $webSocketClient->fetchAGVData(function ($agvData) {
//         if (empty($agvData)) {
//             // If AGV data is empty, return an error response
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'No AGV data available',
//             ], 404);
//         }

//         // Broadcast the AGV data using WebsocketEvent
//         broadcast(new WebsocketEvent($agvData));

//         // Return a success response
//         return response()->json([
//             'status' => 'success',
//             'message' => 'AGV data broadcasted successfully',
//             'data' => $agvData
//         ]);
//     });
// });