<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

/* 
    We use a middle wrapper query to support ordering and paginating on 
    computed columns (because sql doesn't support using select aliases in a where statement).
    We use an outer wrapper query to return the results in the correct order (because we have to reverse them initially).
*/
class QueryBefore extends PaginationQueryAbstract
{
    protected function doProcess($target)
    {
        $middleQuery = $this->getMiddleQuery($this->query, $target);

        return $this->getOuterQuery($middleQuery);
    }

    protected function getBeforeComparator($query)
    {
        return $this->comparator($query, false);
    }

    protected function getMiddleQuery($query, $target)
    {
        $middleQuery = $this->wrapQuery($query);
        $this->reverseQueryOrders($middleQuery);

        $comparator = $this->getBeforecomparator($middleQuery);

        return $middleQuery
            ->where($this->getOrderColumn($middleQuery), $comparator, $target)
            ->limit($this->perPage);
    }

    protected function getOuterQuery($middleQuery)
    {
        $outerQuery = $this->wrapQuery($middleQuery);
        $this->reverseQueryOrders($outerQuery);

        return $outerQuery;
    }
}
