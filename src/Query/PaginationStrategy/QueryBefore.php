<?php

namespace Amrnn\CursorPaginator\Query\PaginationStrategy;


/* 
    We use a middle wrapper query to support ordering and paginating on 
    computed columns (because sql doesn't support using select aliases in a where statement).
    We use an outer wrapper query to return the results in the correct order (because we have to reverse them initially).
*/

class QueryBefore extends PaginationQueryAbstract
{
    protected function doProcess($targets)
    {
        $middleQuery = $this->getMiddleQuery($this->query, $targets);

        return $this->getOuterQuery($middleQuery);
    }

    protected function getMiddleQuery($query, $targets)
    {
        $middleQuery = $this->wrapQuery($query);
        $this->reverseQueryOrders($middleQuery);

        (new WhereApplier($middleQuery, $targets, $this))->applyWhere();

        return $middleQuery
            ->limit($this->perPage);
    }

    protected function getOuterQuery($middleQuery)
    {
        $outerQuery = $this->wrapQuery($middleQuery);
        $this->reverseQueryOrders($outerQuery);

        return $outerQuery;
    }
}
