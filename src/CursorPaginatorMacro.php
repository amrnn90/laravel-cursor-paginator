<?php

namespace Amrnn90\CursorPaginator;

class CursorPaginatorMacro
{
    protected $requestData;
    protected $currentCursor;
    protected $perPage;
    protected $columns;

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

        return new CursorPaginator($items, $this->perPage, $meta);
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
        $this->currentCursor = Cursor::fromRequest($this->requestData);
    }

    protected function resolveQuery($query)
    {
        return $this->currentCursor->paginationQuery($query, $this->perPage);
    }

    protected function meta($query, $items)
    {
        $targetsManager = new TargetsManager($query);
        return (new Query\QueryMeta($query, $items, $this->currentCursor, $targetsManager))
            ->meta();
    }
}
