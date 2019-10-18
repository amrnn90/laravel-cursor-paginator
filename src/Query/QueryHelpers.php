<?php

namespace Amrnn\CursorPaginator\Query;

use Illuminate\Support\Facades\DB;
use Amrnn\CursorPaginator\Exceptions\CursorPaginatorException;

trait QueryHelpers
{

    protected function extractQueryObject($query)
    {
        if (method_exists($query, 'getQuery')) {
            return $query->getQuery();
        }

        return $query;
    }

    protected function hasOrderColumn($query, $index = 0)
    {
        $orders = $this->extractQueryObject($query)->orders;
        return $orders && count($orders) > $index;
    }

    protected function getOrderColumn($query, $index)
    {
        if ($this->hasOrderColumn($query, $index)) {
            return $this->extractQueryObject($query)->orders[$index]['column'];
        }
    }

    protected function getOrderColumnList($query)
    {
        $result = [];
        foreach ($this->extractQueryObject($query)->orders as $column) {
            $result[] = $column['column'];
        }
        return $result;
    }

    protected function getOrderDirection($query, $index)
    {
        return $this->extractQueryObject($query)->orders[$index]['direction'];
    }

    protected function reverseQueryOrders($query)
    {
        foreach ($this->extractQueryObject($query)->orders as &$order) {
            $order['direction'] = $order['direction'] == 'desc' ? 'asc' : 'desc';
        }

        return $query;
    }

    protected function removeOrders($query)
    {
        $this->extractQueryObject($query)->orders = null;
        return $query;
    }

    protected function orderColumnIsDate($query, $index) 
    {
        if (method_exists($query, 'getModel')) {
            $column = $this->getOrderColumn($query, $index);
            if (in_array($column, $query->getModel()->getDates())) {
                return true;
            }
        }
        return false;
    }

    protected function getCleanQueryFrom($query)
    {
        if (method_exists($query, 'getModel')) {
            return $query->getModel()->query()->withoutGlobalScopes();
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

    protected function removeEagerLoad($query) 
    {
        if (method_exists($query, 'setEagerLoads')) {
            $query->setEagerLoads([]);
        }
    }

    protected function wrapQuery($query)
    {
        $inner = clone $query;
        $wrapper = $this->getCleanQueryFrom($inner);

        $this->copyOrders($inner, $wrapper);
        $this->copyEagerLoad($inner, $wrapper);

        if (!$this->extractQueryObject($inner)->limit) {
            $this->removeOrders($inner);
        }

        return $wrapper->fromSub($inner, null);
    }

    protected function ensureQueryIsOrdered($query)
    {
        if (!$query) {
            throw new CursorPaginatorException('No query provided');
        }

        if (!$this->hasOrderColumn($query)) {
            throw new CursorPaginatorException('Query must be ordered on some column');
        }
    }
}
