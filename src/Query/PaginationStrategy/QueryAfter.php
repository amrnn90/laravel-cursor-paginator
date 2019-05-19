<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

class QueryAfter extends PaginationQueryAbstract
{
    protected function doProcess($target)
    {
        $query = $this->query;
        $comparator = $this->getAfterComparator($query);

        return $query
            ->where($this->getOrderColumn($query), $comparator, $target)
            ->limit($this->perPage);
    }

    protected function getAfterComparator($query)
    {
        return $this->comparator($query, false);
    }
}
