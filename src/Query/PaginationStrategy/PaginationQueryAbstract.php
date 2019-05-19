<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

use Amrnn90\CursorPaginator\Query\QueryAbstract;

abstract class PaginationQueryAbstract extends QueryAbstract
{
    public function process($target)
    {
       return $this->doProcess($this->formatTarget($this->query, $target));
    }

    abstract protected function doProcess($target);
}
