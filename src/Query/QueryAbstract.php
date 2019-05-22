<?php

namespace Amrnn90\CursorPaginator\Query;

use Amrnn90\CursorPaginator\Exceptions\CursorPaginatorException;

abstract class QueryAbstract
{
    use QueryHelpers;
    
    protected $query;
    protected $perPage;
    protected $options;

    public function __construct($query, $perPage, $options = [])
    {
        $this->query = clone $query;
        $this->perPage = $perPage;
        $this->options = $options;
        $this->canOperateOnQuery();
    }

    protected function getQuery()
    {
        return $this->extractQueryObject($this->query);
    }

    protected function canOperateOnQuery()
    {
        if (!$this->hasOrderColumn($this->query)) {
            throw new CursorPaginatorException('Query must be ordered on some column');
        }
    }
}
