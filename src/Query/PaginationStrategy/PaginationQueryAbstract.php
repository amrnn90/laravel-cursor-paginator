<?php

namespace Amrnn90\CursorPaginator\Query\PaginationStrategy;

use Amrnn90\CursorPaginator\Query\QueryHelpers;
use Carbon\Carbon;
use Amrnn90\CursorPaginator\Exceptions\CursorPaginatorException;

abstract class PaginationQueryAbstract
{
    use QueryHelpers;

    protected $query;
    protected $perPage;
    protected $options;

    public function __construct($query = null, $perPage = 10, $options = [])
    {
        $this->setQuery($query);
        $this->setPerPage($perPage);
        $this->options = $options;
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
        $this->canOperateOnQuery();

        $targets = is_array($targets) ? $targets : [$targets];

        return $this->doProcess($this->formatTargets($this->query, $targets));
    }

    public function isInclusive()
    {
        return false;
    }

    protected function formatTargets($query, $targets)
    {
        for ($i = 0; $i < count($targets); $i++) {
            $column = $this->getOrderColumn($query, $i);
            if (
                (isset($this->options['dates']) && in_array($column, $this->options['dates'])) || (method_exists($query, 'getModel') && in_array($column, $query->getModel()->getDates()))
            ) {
                $targets[$i] =  Carbon::parse($targets[$i])->toDateTimeString();
            }
        }
        return $targets;
    }

    protected function canOperateOnQuery()
    {
        if (!$this->query) {
            throw new CursorPaginatorException('No query provided');
        }

        if (!$this->hasOrderColumn($this->query)) {
            throw new CursorPaginatorException('Query must be ordered on some column');
        }
    }

    abstract protected function doProcess($targets);
}
