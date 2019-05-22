<?php

namespace Amrnn90\CursorPaginator\Query;
use Illuminate\Database\Query\Builder;

class QueryMeta extends QueryAbstract
{
    public function meta($items)
    {
        $query = $this->query;
        $orderColumn = $this->getOrderColumn($query, 0);

        $countQuery = with(clone $query)->selectRaw('COUNT(*) as `count`');
        $firstQuery = with(clone $query)->select($orderColumn)->limit(1);
        $lastQuery = with($this->reverseQueryOrders(clone $query))->select($orderColumn)->limit(1);

        $meta = resolve(Builder::class)
            ->selectSub($countQuery, 'total')
            ->selectSub($firstQuery, 'first')
            ->selectSub($lastQuery, 'last')
            ->first();

        $itemsFirst = $items->first()[$orderColumn];
        $itemsLast = $items->last()[$orderColumn];

        return [
            'total' => $meta->total,
            'first' => $meta->first,
            'last' => $meta->last,
            'previous' => $meta->first != $itemsFirst ? $itemsFirst : null,
            'next' => $meta->last != $itemsLast ? $itemsLast : null,
        ];
    }
}
