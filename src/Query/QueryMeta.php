<?php

namespace Amrnn\CursorPaginator\Query;

use Amrnn\CursorPaginator\Cursor;
use Amrnn\CursorPaginator\TargetsManager;

class QueryMeta
{
    use QueryHelpers;

    protected $query;
    protected $items;
    protected $currentCursor;
    protected $targetsManager;
    protected $nextItem;

    public function __construct($query, $items, $currentCursor, TargetsManager $targetsManager, $nextItem = null)
    {
        $this->ensureQueryIsOrdered($query);
        $this->query = clone $query;
        $this->items = $items;
        $this->currentCursor = $currentCursor;
        $this->targetsManager = $targetsManager;
        $this->nextItem = $nextItem;
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
            'current' => $this->currentCursor() ?? $firstItemCursor,
            'next_item' => $this->nextItem
        ];
    }

    protected function firstItemCursor($meta)
    {
        $itemsFirst = $meta->first;
        return Cursor::afterInclusive($this->targetsManager->targetFromItem($itemsFirst));
    }

    protected function lastItemCursor($meta)
    {
        $itemsLast = $meta->last;
        return Cursor::beforeInclusive($this->targetsManager->targetFromItem($itemsLast));
    }

    protected function previousCursor($meta)
    {
        $itemsFirst = $this->items->first();
        $itemsFirstTarget = $this->targetsManager->targetFromItem($itemsFirst);

        if (!$itemsFirstTarget) return null;

        return !$this->modelsEqual($meta->first, $itemsFirst) ? Cursor::before($itemsFirstTarget) : null;
    }

    protected function nextCursor($meta)
    {
        $itemsLast = $this->items->last();
        $itemsLastTarget = $this->targetsManager->targetFromItem($itemsLast);

        if (!$itemsLastTarget) return null;

        return !$this->modelsEqual($meta->last, $itemsLast) ? Cursor::after($itemsLastTarget) : null;
    }

    protected function currentCursor()
    {
        if (is_null($this->currentCursor->target)) {
            return null;
        }
        return $this->currentCursor;
    }

    protected function modelsEqual($first, $second) {
        if (method_exists($first, 'is')) {
            return $first->is($second);
        }
        return $first == $second;
    }

    protected function runQueryMeta()
    {
        $query = $this->query;

        $count = with(clone $query)->count();
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
