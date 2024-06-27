<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WebsocketClient;
use Illuminate\Database\Events\QueryExecuted;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WebsocketClient::class, function ($app) {
            return new WebsocketClient('ws://localhost:80/dashboard');
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        config(['app.locale' => 'id']);
        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');
        DB::listen(function(QueryExecuted $query){Log::info($query->sql);});
    }
}
