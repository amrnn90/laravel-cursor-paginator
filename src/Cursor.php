<?php

namespace Amrnn\CursorPaginator;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Amrnn\CursorPaginator\Exceptions\CursorPaginatorException;
use Amrnn\CursorPaginator\Util\Base64Url;

class Cursor implements JsonSerializable, Jsonable, Arrayable
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
        $cursorName = self::encodedCursorName();
        if(self::encodeCursor() && isset($requestData[$cursorName])) {
            $requestData = json_decode(Base64Url::decode($requestData[$cursorName]), true);
        }
        foreach (array_keys(static::queryMappings()) as $direction) {
            if ($target = \Arr::get($requestData, $direction)) {
                if (self::encodeCursor()) {
                    $decodedTarget = json_decode(Base64Url::decode($target), true);
                    if (is_array($decodedTarget) && $decodedTarget[$direction]) {
                        return new static($direction, $decodedTarget[$direction]);
                    }
                }
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
        $params = [$this->direction => $this->target];
        if(self::encodeCursor()) {
            $params = [ self::encodedCursorName() => Base64Url::encode(json_encode($params, JSON_NUMERIC_CHECK))];
        }
        return $params;
    }

    protected static function encodeCursor(): bool
    {
        return config('cursor_paginator.encode_cursor');
    }

    protected static function encodedCursorName(): string
    {
        return config('cursor_paginator.encoded_cursor_name', 'cursor');
    }

    public function isValid()
    {
        return !(empty($this->direction) || empty($this->target));
    }

    public function toArray()
    {
        if (!$this->isValid()) return null;
        if(self::encodeCursor()) {
            return $this->urlParams();
        }
        return [
            'direction' => $this->direction,
            'target' => $this->target
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize()
    {
        if (!$this->isValid()) return null;
        if(self::encodeCursor()) {
            return Base64Url::encode(json_encode([$this->direction => $this->target],JSON_NUMERIC_CHECK));
        }
        return $this->toArray();
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
