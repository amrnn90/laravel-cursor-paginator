<?php

namespace Amrnn90\CursorPaginator\Query;
use Illuminate\Database\Query\Builder;
use Amrnn90\CursorPaginator\Cursor;

class QueryMeta extends QueryAbstract
{
    public function meta($items, $currentCursor)
    {
        $query = $this->query;
        $orderColumns = $this->getOrderColumnList($query);

        $countQuery = with(clone $query)->selectRaw('COUNT(*) as `count`');
        $firstQuery = with(clone $query)->select($orderColumns)->limit(1);
        $lastQuery = with($this->reverseQueryOrders(clone $query))->select($orderColumns)->limit(1);

        $meta = resolve(Builder::class)
            ->selectSub($countQuery, 'total')
            ->selectSub($firstQuery, 'first')
            ->selectSub($lastQuery, 'last')
            ->first();

        $itemsFirst = $items->first();
        $itemsFirstTarget = $this->getTargetsFromItem($itemsFirst, $orderColumns);
        $itemsLast = $items->last();
        $itemsLastTarget = $this->getTargetsFromItem($itemsLast, $orderColumns);

        return [
            'total' => $meta->total,
            'first' => $meta->first,
            'last' => $meta->last,
            'previous' => $meta->first != $itemsFirst ? new Cursor('before', $itemsFirstTarget) : null,
            'next' => $meta->last != $itemsLast ? new Cursor('after', $itemsLastTarget) : null,
            'current' => $currentCursor
        ];
    }

    protected function getTargetsFromItem($item, $columns) 
    {
        $res = [];
        foreach ($columns as $column) 
        {
            $res[] = $item[$column];
        }
        return $res;
    }
}
