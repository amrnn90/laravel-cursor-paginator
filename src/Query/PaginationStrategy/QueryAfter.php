<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

class QueryAfter extends PaginationQueryAbstract
{
    protected function doProcess($targets)
    {
        $wrapper = $this->wrapQuery($this->query);

        (new WhereApplier($wrapper, $targets, $this))->applyWhere();

        return $wrapper
            ->limit($this->perPage);
    }
}
