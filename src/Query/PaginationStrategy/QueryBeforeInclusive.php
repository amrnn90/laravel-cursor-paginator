<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

use Amrnn90\CursorPaginator\Query\PaginationStrategy\QueryBefore;

class QueryBeforeInclusive extends QueryBefore
{
    public function isInclusive()
    {
        return true;
    }
}
