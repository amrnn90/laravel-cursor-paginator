<?php

namespace Amrnn\CursorPaginator\Query\PaginationStrategy;

class QueryAfterInclusive extends QueryAfter
{
    public function isInclusive()
    {
        return true;
    }
}
