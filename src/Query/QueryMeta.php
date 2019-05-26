<?php

namespace Amrnn90\CursorPaginator\Query;

use Illuminate\Database\Query\Builder;
use Amrnn90\CursorPaginator\Cursor;

class QueryMeta
{
    use QueryHelpers;
    
    protected $query;
    protected $items;
    protected $currentCursor;

    public function __construct($query, $items, $currentCursor)
    {
        $this->ensureQueryIsOrdered($query);
        $this->query = clone $query;
        $this->items = $items;
        $this->currentCursor = $currentCursor;
    }

    public function meta()
    {
        $meta = $this->runQueryMeta();

        return [
            'total' => $meta->total,
            'first' => $meta->first,
            'last' => $meta->last,
            'previous' => $this->previousCursor($meta),
            'next' => $this->nextCursor($meta),
            'current' => $this->currentCursor
        ];
    }

    protected function previousCursor($meta)
    {
        $itemsFirst = $this->items->first();
        $itemsFirstTarget = $this->getTargetsFromItem($itemsFirst);

        return $meta->first != $itemsFirst ? new Cursor('before', $itemsFirstTarget) : null;
    }

    protected function nextCursor($meta)
    {
        $itemsLast = $this->items->last();
        $itemsLastTarget = $this->getTargetsFromItem($itemsLast);

        return $meta->last != $itemsLast ? new Cursor('after', $itemsLastTarget) : null;
    }

    protected function runQueryMeta()
    {
        $query = $this->query;

        $countQuery = with(clone $query)->selectRaw('COUNT(*) as `count`');
        $firstQuery = with(clone $query)->select($this->columns())->limit(1);
        $lastQuery = with($this->reverseQueryOrders(clone $query))->select($this->columns())->limit(1);

        return resolve(Builder::class)
            ->selectSub($countQuery, 'total')
            ->selectSub($firstQuery, 'first')
            ->selectSub($lastQuery, 'last')
            ->first();
    }

    protected function columns()
    {
        return $this->getOrderColumnList($this->query);
    }

    protected function getTargetsFromItem($item)
    {
        $res = [];
        foreach ($this->columns() as $column) {
            $res[] = $item[$column];
        }
        return $res;
    }
}
