<?php

namespace Amrnn90\CursorPaginator\Query;

use Amrnn90\CursorPaginator\Exceptions\CursorPaginatorException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

abstract class QueryAbstract
{
    protected $query;
    protected $perPage;
    protected $options;

    public function __construct($query, $perPage, $options = [])
    {
        $this->query = clone $query;
        $this->perPage = $perPage;
        $this->options = $options;
        $this->canOperateOnQuery();
    }

    protected function getOrderColumn($query)
    {
        if ($this->hasOrderColumn($query)) {
            return $this->extractQueryObject($query)->orders[0]['column'];
        }
    }

    protected function extractQueryObject($query)
    {
        if (method_exists($query, 'getQuery')) {
            return $query->getQuery();
        }

        return $query;
    }

    protected function getQuery()
    {
        return $this->extractQueryObject($this->query);
    }

    protected function hasOrderColumn($query)
    {
        $orders = $this->extractQueryObject($query)->orders;
        return $orders && count($orders) > 0;
    }

    protected function getOrderDirection($query)
    {
        return $this->extractQueryObject($query)->orders[0]['direction'];
    }

    protected function comparator($query, $inclusive)
    {
        $comparator = $this->getOrderDirection($query) == 'desc' ? '<' : '>';
        return $inclusive ? $comparator . '=' : $comparator;
    }

    protected function reverseQueryOrders($query)
    {
        foreach ($this->extractQueryObject($query)->orders as &$order) {
            $order['direction'] = $order['direction'] == 'desc' ? 'asc' : 'desc';
        }

        return $query;
    }

    protected function getCleanQueryFrom($query)
    {
        if (method_exists($query, 'getModel')) {
            return $query->getModel()->query();
        }
        return DB::table($query->from);
    }

    protected function copyOrders($from, $to)
    {
        $fromQuery = $this->extractQueryObject($from);
        $toQuery = $this->extractQueryObject($to);
        $toQuery->orders = $fromQuery->orders;
    }

    protected function copyEagerLoad($from, $to)
    {
        if (method_exists($to, 'setEagerLoads') && method_exists($from, 'getEagerLoads')) {
            $to->setEagerLoads($from->getEagerLoads());
        }
    }

    protected function canOperateOnQuery()
    {
        if (!$this->hasOrderColumn($this->query)) {
            throw new CursorPaginatorException('Query must be ordered on some column');
        }
    }


    protected function formatTarget($query, $target)
    {
        $column = $this->getOrderColumn($query);
        if (
            (method_exists($query, 'getModel') && in_array($column, $query->getModel()->getDates()))
            || (isset($this->options['dates']) && in_array($column, $this->options['dates']))
        ) {
            return Carbon::parse($target);
        }
        return $target;
    }
}
