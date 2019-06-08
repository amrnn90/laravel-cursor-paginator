<?php

namespace Amrnn\CursorPaginator\Query\PaginationStrategy;

class QueryBeforeInclusive extends QueryBefore
{
    public function isInclusive()
    {
        return true;
    }
}
