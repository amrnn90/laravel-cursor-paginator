<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

use Amrnn90\CursorPaginator\Query\QueryHelpers;

class WhereApplier
{
    use QueryHelpers;

    protected $query;
    protected $targets;
    protected $paginationQuery;

    public function __construct($query, $targets, PaginationQueryAbstract $paginationQuery)
    {
        $this->query = $query;
        $this->paginationQuery = $paginationQuery;
        $this->targets = $targets;
    }

    public function applyWhere()
    {
        for ($i = 0; $i < count($this->targets); $i++) {
            $column = $this->getOrderColumn($this->query, $i);
            $comparator = $this->getComparatorForTarget($i);

            if ($i == 0) {
                $this->query->whereRaw("`$column` $comparator ?", [$this->targets[$i]]);
            } else {
                $this->applyWhereForColumnsAfterFirst($i);
            }
        }
        return $this->query;
    }

    protected function applyWhereForColumnsAfterFirst($colIndex)
    {
        $comparator = $this->getComparatorForTarget($colIndex);
        $column = $this->getOrderColumn($this->query, $colIndex);
        $prevColumnsConditions = $this->getPrevColumnsConditions($colIndex);

        $this->query->whereRaw("NOT ($prevColumnsConditions AND `$column` $comparator ?)", array_slice($this->targets, 0, $colIndex + 1));
    }

    protected function getPrevColumnsConditions($colIndex)
    {
        $prevColumnsConditions = "";
        for ($j = 0; $j < $colIndex; $j++) {
            $prevColumn = $this->getOrderColumn($this->query, $j);
            $prevColumnsConditions .= "`$prevColumn` = ?";
            if ($j != $colIndex - 1) {
                $prevColumnsConditions .= " and ";
            }
        }
        return $prevColumnsConditions;
    }

    protected function getComparatorForTarget($targetIndex)
    {
        if ($targetIndex == count($this->targets) - 1) {
            $comparator = $this->comparator($this->paginationQuery->isInclusive(), $targetIndex);
        } else {
            $comparator = $this->comparator(true, $targetIndex);
        }

        if ($targetIndex > 0) $comparator = $this->reverseComparator($comparator);

        return $comparator;
    }

    public function comparator($inclusive, $index)
    {
        $comparator = $this->getOrderDirection($this->query, $index) == 'desc' ? '<' : '>';
        return $inclusive ? $comparator . '=' : $comparator;
    }

    public function reverseComparator($comparator)
    {
        switch ($comparator) {
            case '>':
                return '<=';
            case '>=':
                return '<';
            case '<':
                return '>=';
            case '<=':
                return '>';
        }
    }
}
