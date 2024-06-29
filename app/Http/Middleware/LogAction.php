<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ActionLog;
use Carbon\Carbon;
use Illuminate\Support\Str;


class LogAction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        Log::info('LogAction Middleware executed.');
        // $modelClass = 'App\\Models\\' . ucfirst($request->route('model'));
        // if (Auth::check() && ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete'))) {
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete')) {
            $action = '';
            if ($request->isMethod('post')) {
                $action = 'create';
            } elseif ($request->isMethod('put')) {
                $action = 'update';
            } elseif ($request->isMethod('delete')) {
                $action = 'delete';
            }

            // $modelInstance = $request->route()->parameter($request->route()->parameterNames()[0] ?? '');
            
            // Ensure we have a valid model instance and retrieve table name
            // if ($modelInstance && method_exists($modelInstance, 'getTable')) {
            //     $table = $modelInstance->getTable();
            // } 
            $routeUri = $request->route()->uri();

            // Determine table name from route parameters
            $table= $this->getTableNameFromRoute($routeUri);

    
            // Extract parameters
            // $parameters = $route->parameters();
    
            $id = $request->route()->parameter('id');
            Log::info("Action: $action, Table: $table, ID: $id");
            ActionLog::create([
                'id_user' => Auth::id(),
                'action' => $action,
                'table_name' => $table,
                'row_id' => $id ?? 0,
                'data' => json_encode($request->all()),
                'action_time' => Carbon::now(),  
            ]);
            Log::info('Action logged successfully.');
        }

        return $response;
    }
    protected function getTableNameFromRoute($routeUri)
    {
        // Example logic to determine table name based on route URI
        if (strpos($routeUri, '/item')) {
            return 'items';
        }

        // Add more conditions for other endpoints if needed

        // If no match found, return empty string or handle accordingly
        return '';
    }
}
