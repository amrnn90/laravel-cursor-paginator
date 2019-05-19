<?php

namespace Amrnn90\CursorPaginator;

use Countable;
use ArrayAccess;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Pagination\AbstractPaginator;

class CursorPaginator extends AbstractPaginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable, PaginatorContract
{

    public function __construct($items, $perPage, $meta, array $options = [])
    {
        $this->options = $options;
        
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->perPage = $perPage;
        $this->meta = $meta;
        $this->path = $this->path !== '/' ? rtrim($this->path, '/') : $this->path;

        $this->setItems($items);
    }

    public function setItems($items)
    {
        $this->items = $items;
    }

    public function render($view = null, $data = [])
    {
        // No render method
        return '';
    }

    public function hasMorePages()
    {
        return true;
    }

    public function nextPageUrl()
    {
        return '';
    }

    public function toArray()
    {
        return [
            'data' => $this->items(),
            'path' => $this->path,
            'per_page' => $this->perPage(),
            'prev_page_url' => $this->meta['previous']
        ] + $this->meta;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
