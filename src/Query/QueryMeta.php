<?php

namespace Amrnn90\CursorPaginator\Query;

use Amrnn90\CursorPaginator\Cursor;
use Amrnn90\CursorPaginator\TargetsManager;

class QueryMeta
{
    use QueryHelpers;

    protected $query;
    protected $items;
    protected $currentCursor;
    protected $targetsManager;

    public function __construct($query, $items, $currentCursor, TargetsManager $targetsManager)
    {
        $this->ensureQueryIsOrdered($query);
        $this->query = clone $query;
        $this->items = $items;
        $this->currentCursor = $currentCursor;
        $this->targetsManager = $targetsManager;
    }

    public function meta()
    {
        $meta = $this->runQueryMeta();
        $firstItemCursor = $this->firstItemCursor($meta);

        return [
            'total' => $meta->total,
            'first' => $firstItemCursor,
            'last' => $this->lastItemCursor($meta),
            'previous' => $this->previousCursor($meta),
            'next' => $this->nextCursor($meta),
            'current' => $this->currentCursor() ?? $firstItemCursor
        ];
    }

    protected function firstItemCursor($meta)
    {
        $itemsFirst = $meta->first;
        return new Cursor('after_i', $this->targetsManager->targetFromItem($itemsFirst));
    }

    protected function lastItemCursor($meta)
    {
        $itemsLast = $meta->last;
        return new Cursor('before_i', $this->targetsManager->targetFromItem($itemsLast));
    }

    protected function previousCursor($meta)
    {
        $itemsFirst = $this->items->first();
        $itemsFirstTarget = $this->targetsManager->targetFromItem($itemsFirst);

        return $meta->first != $itemsFirst ? new Cursor('before', $itemsFirstTarget) : null;
    }

    protected function nextCursor($meta)
    {
        $itemsLast = $this->items->last();
        $itemsLastTarget = $this->targetsManager->targetFromItem($itemsLast);

        return $meta->last != $itemsLast ? new Cursor('after', $itemsLastTarget) : null;
    }

    protected function currentCursor()
    {
        if (is_null($this->currentCursor->target)) {
            return null;
        }
        return $this->currentCursor;
    }

    protected function runQueryMeta()
    {
        $query = $this->query;

        $count = with(clone $query)->selectRaw('COUNT(*) as `count`')->first()->count;
        $firstLastQuery = $this->wrapQuery(
            with(clone $query)->limit(1)->union(
                with($this->reverseQueryOrders(clone $query))->limit(1)
            )
        );
        $this->removeEagerLoad($firstLastQuery);

        $firstAndLast = $firstLastQuery->get();

        return (object) [
            'total' => (int)$count,
            'first' => $firstAndLast->first(),
            'last' => $firstAndLast->count() > 1 ? $firstAndLast->last() : null
        ];
    }
}
