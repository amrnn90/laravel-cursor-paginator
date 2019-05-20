<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

class QueryAfter extends PaginationQueryAbstract
{
    protected function doProcess($target)
    {
        $wrapper = $this->wrapQuery($this->query);
        $comparator = $this->getAfterComparator($wrapper);

        return $wrapper
            ->where($this->getOrderColumn($wrapper), $comparator, $target)
            ->limit($this->perPage);
    }

    protected function getAfterComparator($query)
    {
        return $this->comparator($query, false);
    }
}
