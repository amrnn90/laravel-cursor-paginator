<?php

namespace Amrnn90\CursorPaginator;

class Macro
{
    protected $requestData;
    protected $currentCursor;
    protected $perPage;
    protected $options;

    public function __construct(array $requestData, $perPage, $options = [])
    {
        $this->setRequestData($requestData);
        $this->setPerPage($perPage);
        $this->options = $options;
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
        $this->perPage = $perPage ?? config('cursor_paginator.per_page');
        return $this;
    }

    protected function setCurrentCursor()
    {
        $this->currentCursor = Cursor::fromRequest($this->requestData);
    }

    protected function resolveQuery($query)
    {
        return $this->currentCursor->paginationQuery($query, $this->perPage, $this->options);
    }

    protected function meta($query, $items)
    {
        $targetsManager = new TargetsManager($query, $this->options);
        return (new Query\QueryMeta($query, $items, $this->currentCursor, $targetsManager))
            ->meta();
    }
}
