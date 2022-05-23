<?php

namespace Yarm\Analyze;

use Illuminate\Support\ServiceProvider;

class AnalyzeServiceProvider extends ServiceProvider{

    public function boot()
    {

        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views','analyze');
        $this->mergeConfigFrom(__DIR__ . '/config/analyze.php','analyze');
        $this->publishes([
            //__DIR__ . '/config/analyze.php' => config_path('analyze.php'),
            __DIR__.'/views' => resource_path('views/vendor/analyze'),
            // Assets
            __DIR__.'/js' => resource_path('js/vendor'),
        ],'analyze');


        //after every update
        //run   php artisan vendor:publish [--provider="Yarm\Analyze\AnalyzeServiceProvider"][--tag="analyze"]  --force
    }

    public function register()
    {

    }
}
