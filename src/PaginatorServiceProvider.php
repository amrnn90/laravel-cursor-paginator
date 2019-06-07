<?php

namespace Amrnn90\CursorPaginator;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Amrnn90\CursorPaginator\Macro as PaginatorMacro;
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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $macro = function ($perPage = 10, $options = []) {
            $request = resolve(Request::class);

            return (new PaginatorMacro($request->all(), $perPage, $options))
                ->process($this);
        };

        QueryBuilder::macro('cursorPaginate', $macro);
        EloquentBuilder::macro('cursorPaginate', $macro);
    }
}
