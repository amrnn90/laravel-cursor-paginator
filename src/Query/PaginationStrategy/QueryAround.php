<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

class QueryAround extends PaginationQueryAbstract
{
    protected function doProcess($target)
    {
        $query = $this->query;
        $before = (new QueryBefore($query, floor($this->perPage / 2)))->process($target);
        $after = (new QueryAfterInclusive($query, ceil($this->perPage / 2)))->process($target);

        $unionQuery =  $before->union($after);
        $this->extractQueryObject($unionQuery)->unionOrders = $this->extractQueryObject($query)->orders;

        return $unionQuery;
    }
}
