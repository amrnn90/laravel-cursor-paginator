<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

class QueryAfterInclusive extends QueryAfter
{
    public function isInclusive()
    {
        return true;
    }
}
