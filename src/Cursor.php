<?php

namespace Amrnn90\CursorPaginator;

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

    static protected function queryMappings()
    {
        $before = config('cursor_paginator.directions.before');
        $before_i = config('cursor_paginator.directions.before_i');
        $after = config('cursor_paginator.directions.after');
        $after_i = config('cursor_paginator.directions.after_i');
        $around = config('cursor_paginator.directions.around');

        return [
            $before => Query\PaginationStrategy\QueryBefore::class,
            $before_i => Query\PaginationStrategy\QueryBeforeInclusive::class,
            $after  => Query\PaginationStrategy\QueryAfter::class,
            $after_i  => Query\PaginationStrategy\QueryAfterInclusive::class,
            $around => Query\PaginationStrategy\QueryAround::class
        ];
    }

    static public function fromRequest($requestData)
    {
        foreach (array_keys(static::queryMappings()) as $direction) {
            if ($target = array_get($requestData, $direction)) {
                return new static($direction, $target);
            }
        }
        return static::afterInclusive(null);
    }

    static public function before($target)
    {
        return new static('before', $target);
    }

    static public function beforeInclusive($target)
    {
        return new static('before_i', $target);
    }

    static public function after($target)
    {
        return new static('after', $target);
    }

    static public function afterInclusive($target)
    {
        return new static('after_i', $target);
    }

    static public function around($target)
    {
        return new static('around', $target);
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
