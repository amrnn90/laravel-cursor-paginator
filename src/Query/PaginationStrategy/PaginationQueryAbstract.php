<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

use Amrnn90\CursorPaginator\Query\QueryAbstract;
use Carbon\Carbon;

abstract class PaginationQueryAbstract extends QueryAbstract
{
    public function process($targets)
    {
        $targets = is_array($targets) ? $targets : [$targets];

        return $this->doProcess($this->formatTargets($this->query, $targets));
    }

    public function isInclusive() {
        return false;
    }

    protected function formatTargets($query, $targets)
    {
        for ($i = 0; $i < count($targets); $i++) {
            $column = $this->getOrderColumn($query, $i);
            if (
                (isset($this->options['dates']) && in_array($column, $this->options['dates'])) || 
                (method_exists($query, 'getModel') && in_array($column, $query->getModel()->getDates()))
            ) {
                $targets[$i] =  Carbon::parse($targets[$i])->toDateTimeString();
            }
        }
        return $targets;
    }

    abstract protected function doProcess($targets);
}
