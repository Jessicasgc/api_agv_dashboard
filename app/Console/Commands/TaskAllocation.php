<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Client\WebSocket;
use Ratchet\Client\Connector;
use React\EventLoop\Factory;
use React\EventLoop\Timer\TimerInterface;
// use React\Socket\Connector as ReactConnector;
use App\Services\WebsocketClient;
use App\Models\AGV;

class TaskAllocation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:allocation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allocating task to a AGV in multi AGV System with algorhythm';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
         // Create event loop
         $loop = Factory::create();

         // Define the task allocation function
         $taskAllocation = function (TimerInterface $timer) {
             // Fetch tasks with status 'waiting'
             $waitingTasks = Task::where('task_status', 'waiting')->get();
 
             foreach ($waitingTasks as $task) {
                 // Check if task has an assigned AGV
                 if (is_null($task->id_agv)) {
                     // Fetch AGVs with less than 1 processing task and less than 5 allocated tasks
                     $availableAgvs = AGV::whereDoesntHave('tasks', function ($query) {
                         $query->where('task_status', 'processing');
                     })->orWhereDoesntHave('tasks', function ($query) {
                         $query->where('task_status', 'allocated')->havingRaw('COUNT(*) >= 5');
                     })->get();
 
                     // Determine the best AGV using the provided algorithm
                     $bestAgv = null;
                     $bestScore = PHP_INT_MAX;
 
                     foreach ($availableAgvs as $agv) {
                         $startStation = Station::find($task->id_start_station);
                         $destinationStation = Station::find($task->id_destination_station);
                         $distance = sqrt(pow($destinationStation->x - $startStation->x, 2) + pow($destinationStation->y - $startStation->y, 2));
                         $batteryLevel = $agv->battery_level; // Assuming AGV model has battery_level attribute
                         $taskCount = $agv->tasks->count();
                         $stockLevel = $destinationStation->stock;
 
                         // Assuming k1, k2, k3, k4 are predefined constants
                         $k1 = 1;
                         $k2 = 1;
                         $k3 = 1;
                         $k4 = 1;
 
                         $score = $k1 * 2 * $distance + $k2 * 20 * $batteryLevel + $k3 * $taskCount + $k4 * $stockLevel;
 
                         if ($score < $bestScore) {
                             $bestScore = $score;
                             $bestAgv = $agv;
                         }
                     }
 
                     if ($bestAgv) {
                         $task->id_agv = $bestAgv->id;
                         $task->task_status = 'allocated';
                         $task->save();
                     }
                 }
             }
 
             // Check tasks with status 'done' and update their station IDs
             $doneTasks = Task::where('task_status', 'done')->get();
 
             foreach ($doneTasks as $task) {
                 $task->id_start_station = $task->id_destination_station;
                 $task->id_destination_station = null;
                 $task->task_status = 'waiting'; // Reset status to waiting or whatever is appropriate
                 $task->save();
             }
         };
 
         // Add the periodic task to the event loop (every 3 seconds)
         $loop->addPeriodicTimer(3, $taskAllocation);
 
         // Run the event loop
         $loop->run();
 
         return 0;
     }
    
}
