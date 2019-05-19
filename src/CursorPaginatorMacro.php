<?php

namespace Amrnn90\CursorPaginator;

use Illuminate\Http\Request;

class CursorPaginatorMacro
{
    protected $request;
    protected $perPage;
    protected $columns;
    protected $queryMappings = [
        'before' => Query\PaginationStrategy\QueryBefore::class,
        'after'  => Query\PaginationStrategy\QueryAfter::class,
        'around' => Query\PaginationStrategy\QueryAround::class
    ];
    
    function __construct(array $request, $perPage = 10, $columns = ['*'])
    {
        $this->request = $request;
        $this->perPage = $perPage;
        $this->columns = $columns;
    }

    function process($query)
    {
        $items = $this->resolveQuery($query)->get();
        $meta = $this->meta($query, $items);

        return new CursorPaginator($items, 2, $meta);
    }

    function resolveQuery($query) 
    {

        foreach ($this->queryMappings as $key => $class) {
            if ($value = array_get($this->request, $key)) {
                return app()->makeWith($class, [
                    'query' => $query,
                    'perPage' => $this->perPage
                ])->process($value);
            }
        }
    }

    function meta($query, $items)
    {
        return app()->makeWith(Query\QueryMeta::class, [
            'query' => $query,
            'perPage' => $this->perPage,
        ])->meta($items);
    }
}
