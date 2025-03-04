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

        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete')) {
            $action = '';
            if ($request->isMethod('post')) {
                $action = 'create';
            } elseif ($request->isMethod('put')) {
                $action = 'update';
            } elseif ($request->isMethod('delete')) {
                $action = 'delete';
            }

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
        switch (true) {
            case strpos($routeUri, '/item'):
                return 'items';
            case strpos($routeUri, '/user'):
                return 'users';
            case strpos($routeUri, '/itemtype'):
                return 'itemtypes';
            case strpos($routeUri, '/station'):
                return 'stations';
            case strpos($routeUri, '/task'):
                return 'tasks';
            default:
                return '';
        }
    }
}
