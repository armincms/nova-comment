<?php

namespace Armincms\NovaComment;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider; 
use Laravel\Nova\Nova as LaravelNova; 
use Laravel\Nova\Events\ServingNova;
use Armincms\Snail\Events\ServingSnail;
use Armincms\Snail\Snail as ArmincmsSnail;

class ToolServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        LaravelNova::resources([
            Nova\Comment::class
        ]);

        ArmincmsSnail::resources([
            Snail\Comment::class
        ]);
    } 

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when()
    {
        return [
            ServingNova::class,
            ServingSnail::class,
        ];
    }
}
