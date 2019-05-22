<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

use Illuminate\Support\Facades\DB;

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

        // return $this->getOuterQuery($middleQuery);
        $res =  $this->getOuterQuery($middleQuery);
        return $res;
    }

    protected function getBeforeComparator($query, $i = 0)
    {
        return $this->comparator($query, false, $i);
    }

    protected function getMiddleQuery($query, $targets)
    {
        $middleQuery = $this->wrapQuery($query);
        $this->reverseQueryOrders($middleQuery);

        $this->applyWhere($middleQuery, $targets);

        return $middleQuery
            ->limit($this->perPage);
    }

    protected function getComparatorForTarget($query, $targets, $targetIndex)
    {
        if ($targetIndex == count($targets) - 1) {
            $comparator = $this->getBeforeComparator($query, $targetIndex);
        } else {
            $comparator = $this->comparator($query, true, $targetIndex);
        }

        if ($targetIndex > 0) $comparator = $this->reverseComparator($comparator);

        return $comparator;
    }

    protected function applyWhere($query, $targets)
    {
        for ($i = 0; $i < count($targets); $i++) {

            $column = $this->getOrderColumn($query, $i);
            $comparator = $this->getComparatorForTarget($query, $targets, $i);

            if ($i == 0) {
                $target = $targets[$i];
                $query->whereRaw("`$column` $comparator ?", [$target]);
            } else {
                $this->applyWhereForColumnsAfterFirst($query, $targets, $i);
            }
        }
        return $query;
    }

    protected function applyWhereForColumnsAfterFirst($query, $targets, $colIndex)
    {
        $comparator = $this->getComparatorForTarget($query, $targets, $colIndex);
        $column = $this->getOrderColumn($query, $colIndex);
        $prevColumnsConditions = $this->getPrevColumnsConditions($query, $colIndex);

        $query->whereRaw("NOT ($prevColumnsConditions AND `$column` $comparator ?)", array_slice($targets, 0, $colIndex + 1));

        return $query;
    }

    protected function getPrevColumnsConditions($query, $colIndex)
    {
        $prevColumnsConditions = "";
        for ($j = 0; $j < $colIndex; $j++) {
            $prevColumn = $this->getOrderColumn($query, $j);
            $prevColumnsConditions .= "`$prevColumn` = ?";
            if ($j != $colIndex - 1) {
                $prevColumnsConditions .= " and ";
            }
        }
        return $prevColumnsConditions;
    }

    protected function getOuterQuery($middleQuery)
    {
        $outerQuery = $this->wrapQuery($middleQuery);
        $this->reverseQueryOrders($outerQuery);

        return $outerQuery;
    }
}
