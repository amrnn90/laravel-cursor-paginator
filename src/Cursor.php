<?php

namespace Amrnn\CursorPaginator;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Amrnn\CursorPaginator\Exceptions\CursorPaginatorException;

class Cursor implements Jsonable, Arrayable
{
    public $direction;
    public $target;

    public function __construct($direction, $target)
    {
        $this->direction = $direction;
        $this->target = $target;
    }

    protected static function queryMappings()
    {
        return [
            static::beforeDirection() => Query\PaginationStrategy\QueryBefore::class,
            static::beforeInclusiveDirection() => Query\PaginationStrategy\QueryBeforeInclusive::class,
            static::afterDirection()  => Query\PaginationStrategy\QueryAfter::class,
            static::afterInclusiveDirection()  => Query\PaginationStrategy\QueryAfterInclusive::class,
        ];
    }

    public static function fromRequest($requestData)
    {
        foreach (array_keys(static::queryMappings()) as $direction) {
            if ($target = \Arr::get($requestData, $direction)) {
                return new static($direction, $target);
            }
        }
        return static::afterInclusive(null);
    }

    public static function before($target)
    {
        return new static(static::beforeDirection(), $target);
    }

    public static function beforeInclusive($target)
    {
        return new static(static::beforeInclusiveDirection(), $target);
    }

    public static function after($target)
    {
        return new static(static::afterDirection(), $target);
    }

    public static function afterInclusive($target)
    {
        return new static(static::afterInclusiveDirection(), $target);
    }

    protected static function mapDirection($direction)
    {
        return config("cursor_paginator.directions.$direction");
    }

    protected static function beforeDirection()
    {
        return static::mapDirection('before');
    }

    protected static function beforeInclusiveDirection()
    {
        return static::mapDirection('before_i');
    }

    protected static function afterDirection()
    {
        return static::mapDirection('after');
    }

    protected static function afterInclusiveDirection()
    {
        return static::mapDirection('after_i');
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function urlParams()
    {
        if (!$this->isValid()) return null;
        return [$this->direction => $this->target];
    }

    public function isValid()
    {
        return !(empty($this->direction) || empty($this->target));
    }

    public function toArray()
    {
        if (!$this->isValid()) return null;
        return [
            'direction' => $this->direction,
            'target' => $this->target
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function paginate($query, $perPage, $targetsManagerOptions = [])
    {
        $targetsManager = new TargetsManager($query, $targetsManagerOptions);
        $paginationQuery = resolve(static::queryMappings()[$this->direction]);

        // we fetch perPage + 1 items in order to save next item in meta
        $paginationQuery
            ->setPerPage($perPage + 1)
            ->setQuery($query);

        $items = $paginationQuery->process($targetsManager->parse($this->target))->get();

        return $this->removeNextItemFromItems($items, $perPage);
    }

    protected function removeNextItemFromItems($items, $perPage)
    {
        if ($items->count() > $perPage) {
            switch ($this->direction) {
                case $this->beforeDirection():
                case $this->beforeInclusiveDirection():
                    $nextItem = $items->first();
                    $currentItems = $items->slice(1);
                    break;
                case $this->afterDirection():
                case $this->afterInclusiveDirection():
                    $nextItem = $items->last();
                    $currentItems = $items->slice(0, $items->count() - 1);
                    break;
                default:
                    throw new CursorPaginatorException();
            }
            return [$currentItems, $nextItem];
        }
        return [$items, null];
    }
}
