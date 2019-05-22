<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

class QueryBeforeInclusive extends QueryBefore
{
    public function isInclusive()
    {
        return true;
    }
}
