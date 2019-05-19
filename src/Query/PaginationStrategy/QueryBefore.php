<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;


class QueryBefore extends PaginationQueryAbstract
{
    protected function doProcess($target)
    {
        $query = $this->query;

        $this->reverseQueryOrders($query);
        $comparator = $this->getBeforecomparator($query);

        $query
            ->where($this->getOrderColumn($query), $comparator, $target)
            ->limit($this->perPage);

        $wrappingQuery =  $this->getCleanQueryFrom($query)->fromSub($query, '');
        
        $this->copyEagerLoad($query, $wrappingQuery);
        $this->copyOrders($query, $wrappingQuery);
        $this->reverseQueryOrders($wrappingQuery);

        return $wrappingQuery;
    }

    protected function getBeforeComparator($query)
    {
        return $this->comparator($query, false);
    }
}
