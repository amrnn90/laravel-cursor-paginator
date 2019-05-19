<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

use Amrnn90\CursorPaginator\Query\PaginationStrategy\QueryBefore;

class QueryBeforeInclusive extends QueryBefore
{
    protected function getBeforeComparator($query)
    {
        return $this->comparator($query, true);
    }
}
