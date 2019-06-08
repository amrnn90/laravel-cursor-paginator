<?php

namespace Amrnn\CursorPaginator;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

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
            static::aroundDirection() => Query\PaginationStrategy\QueryAround::class
        ];
    }

    public static function fromRequest($requestData)
    {
        foreach (array_keys(static::queryMappings()) as $direction) {
            if ($target = array_get($requestData, $direction)) {
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

    public static function around($target)
    {
        return new static(static::aroundDirection(), $target);
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

    protected static function aroundDirection()
    {
        return static::mapDirection('around');
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

    public function paginationQuery($query, $perPage, $targetsManagerOptions = [])
    {
        $targetsManager = new TargetsManager($query, $targetsManagerOptions);
        $paginationQuery = resolve(static::queryMappings()[$this->direction]);
        $paginationQuery
            ->setPerPage($perPage)
            ->setQuery($query);

        return $paginationQuery->process($targetsManager->parse($this->target));
    }
}
