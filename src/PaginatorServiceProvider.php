<?php

namespace Amrnn\CursorPaginator;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Amrnn\CursorPaginator\Macro as PaginatorMacro;
use Illuminate\Http\Request;

class PaginatorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/cursor_paginator.php', 'cursor_paginator');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publish();

        $macro = function ($perPage = null, $options = []) {
            $request = resolve(Request::class);

            return (new PaginatorMacro($request->all(), $perPage, $options))
                ->process($this);
        };

        QueryBuilder::macro('myCursorPaginate', $macro);
        EloquentBuilder::macro('myCursorPaginate', $macro);
    }


    protected function publish()
    {
        $this->publishes([
            __DIR__ . '/config/cursor_paginator.php' => config_path('cursor_paginator.php'),
        ]);
    }
}
