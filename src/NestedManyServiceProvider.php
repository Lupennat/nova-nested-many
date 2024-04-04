<?php

namespace Lupennat\NestedMany;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use Lupennat\NestedMany\Console\NestedActionCommand;
use Lupennat\NestedMany\Http\Requests\NestedResourceRequest;

class NestedManyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                NestedActionCommand::class
            ]);
        }

        $this->app->booted(function () {
            $this->routes();
        });

        Nova::serving(function (ServingNova $event) {
            Nova::script('nested-many-v2.2.3', __DIR__ . '/../dist/js/nested-many.js');
            Nova::style('nested-many-v2.2.3', __DIR__ . '/../dist/css/nested-many.css');
        });

        NovaRequest::macro('isNested', function () {
            return $this instanceof NestedResourceRequest;
        });

        NovaRequest::macro('getNestedPropagated', function ($key) {
            return \Illuminate\Support\Arr::get($this->nestedPropagated ?? [], $key);
        });

        NovaRequest::macro('getNestedValidationKeyPrefix', function () {
            return $this->nestedValidationKeyPrefix ?? '';
        });
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova'])
            ->prefix('nova-vendor/nested-many')
            ->group(__DIR__ . '/../routes/api.php');
    }
}
