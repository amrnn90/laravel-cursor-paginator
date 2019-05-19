<?php

namespace Amrnn90\CursorPaginator;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Amrnn90\CursorPaginator\CursorPaginatorMacro;
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
        $request = $this->app->make(Request::class);

        $macro = function ($perPage = 10, $columns = ['*']) use ($request) {
            return (new CursorPaginatorMacro($request->all(), [
                'perPage' => $perPage,
                'columns' => $columns
            ]))->process($this);
        };

        QueryBuilder::macro('cursorPaginate', $macro);
        EloquentBuilder::macro('cursorPaginate', $macro);
    }
}
