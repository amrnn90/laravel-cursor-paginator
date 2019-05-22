<?php

namespace Amrnn90\CursorPaginator\Query;

use Amrnn90\CursorPaginator\Exceptions\CursorPaginatorException;
use Illuminate\Support\Facades\DB;

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

    protected function hasOrderColumn($query, $index = 0)
    {
        $orders = $this->extractQueryObject($query)->orders;
        return $orders && count($orders) > $index;
    }

    protected function getOrderColumn($query, $index = 0)
    {
        if ($this->hasOrderColumn($query, $index)) {
            return $this->extractQueryObject($query)->orders[$index]['column'];
        }
    }

    protected function getOrderDirection($query, $index = 0)
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

    protected function canOperateOnQuery()
    {
        if (!$this->hasOrderColumn($this->query)) {
            throw new CursorPaginatorException('Query must be ordered on some column');
        }
    }
}
