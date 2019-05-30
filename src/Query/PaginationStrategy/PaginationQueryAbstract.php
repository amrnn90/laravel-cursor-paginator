<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

use Amrnn90\CursorPaginator\Query\QueryHelpers;
use Carbon\Carbon;

abstract class PaginationQueryAbstract
{
    use QueryHelpers;

    protected $query;
    protected $perPage;
    protected $options;

    public function __construct($query = null, $perPage = 10)
    {
        $this->setQuery($query);
        $this->setPerPage($perPage);
    }

    public function setQuery($query) 
    {
        if ($query) {
            $this->query = clone $query;
        }
        return $this;
    }

    public function setPerPage($perPage) 
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function process($targets)
    {
        $this->ensureQueryIsOrdered($this->query);

        $targets = is_array($targets) ? $targets : [$targets];

        return $this->doProcess($targets);
    }

    public function isInclusive()
    {
        return false;
    }

    abstract protected function doProcess($targets);
}
