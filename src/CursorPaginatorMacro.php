<?php

namespace Amrnn90\CursorPaginator;

use Illuminate\Http\Request;

class CursorPaginatorMacro
{
    protected $requestData;
    protected $currentCursor;
    protected $perPage;
    protected $columns;
    protected $queryMappings = [
        'before' => Query\PaginationStrategy\QueryBefore::class,
        'after'  => Query\PaginationStrategy\QueryAfter::class,
        'around' => Query\PaginationStrategy\QueryAround::class
    ];

    public function __construct(array $requestData, $perPage = 10, $columns = ['*'])
    {
        $this->setRequestData($requestData);
        $this->setPerPage($perPage);
        $this->columns = $columns;
    }

    public function process($query)
    {
        $items = $this->resolveQuery($query)->get();
        $meta = $this->meta($query, $items);

        return new CursorPaginator($items, 2, $meta);
    }

    public function setRequestData($requestData)
    {
        $this->requestData = $requestData;
        $this->setCurrentCursor();
        return $this;
    }

    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
        return $this;
    }

    protected function setCurrentCursor()
    {
        foreach (array_keys($this->queryMappings) as $direction) {
            if ($target = array_get($this->requestData, $direction)) {
                $this->currentCursor = new Cursor($direction, $target);
                return;
            }
        }
    }

    protected function resolveQuery($query)
    {
        $class = $this->queryMappings[$this->currentCursor->direction];

        return app()->makeWith($class, [
            'query' => $query,
            'perPage' => $this->perPage
        ])->process($this->currentCursor->getParsedTarget());
    }

    protected function meta($query, $items)
    {
        return app()->makeWith(Query\QueryMeta::class, [
            'query' => $query,
            'perPage' => $this->perPage,
        ])->meta($items, $this->currentCursor);
    }
}
