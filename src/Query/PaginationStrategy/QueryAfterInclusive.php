<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

class QueryAfterInclusive extends QueryAfter
{
    protected function getAfterComparator($query)
    {
        return $this->comparator($query, true);
    }
}
