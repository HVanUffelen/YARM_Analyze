<?php

namespace Yarm\Bookshelf;

use Illuminate\Support\ServiceProvider;

class BookshelfServiceProvider extends ServiceProvider{

    public function boot()
    {

        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views','bookshelf');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->mergeConfigFrom(__DIR__ . '/config/bookshelf.php','bookshelf');
        $this->publishes([
            //__DIR__ . '/config/bookshelf.php' => config_path('bookshelf.php'),
            __DIR__.'/views' => resource_path('views/vendor/bookshelf'),
            // Assets
            __DIR__.'/js' => resource_path('js/vendor'),
        ],'bookshelf');


        //after every update
        //run   php artisan vendor:publish [--provider="Yarm\Bookshelf\BookshelfServiceProvider"][--tag="bookshelf"]  --force
    }

    public function register()
    {

    }

}
