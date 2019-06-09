<?php

namespace Amrnn\CursorPaginator;

use Countable;
use ArrayAccess;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

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

        if (!isset($options['path'])) {
            $this->path = static::resolveCurrentPath();
        }

        $this->path = $this->path !== '/' ? rtrim($this->path, '/') : $this->path;

        $this->setItems($items);
    }

    // overriding this just in case
    public function isValidPageNumber($page)
    {
        return true;
    }

    public function currentPage()
    {
        return $this->meta['current'];
    }

    public function setItems($items)
    {
        $this->items = $items;
    }

    public function hasMorePages()
    {
        return $this->meta['next'] != null;
    }

    public function hasPages()
    {
        return $this->meta['total'] > count($this->getCollection());
    }

    public function onFirstPage()
    {
        return empty($this->meta['previous']);
    }

    public function getPageName()
    {
        return $this->meta['current']['direction'];
    }

    public function url($cursor)
    {
        if (!($cursor instanceof Cursor) || empty($cursor->urlParams())) {
            return null;
        }

        $parameters = $cursor->urlParams();

        if (count($this->query) > 0) {
            $parameters = array_merge($this->query, $parameters);
        }

        return $this->path
            . (Str::contains($this->path, '?') ? '&' : '?')
            . Arr::query($parameters)
            . $this->buildFragment();
    }

    public function firstPageUrl()
    {
        return $this->url($this->meta['first']);
    }

    public function lastPageUrl()
    {
        return $this->url($this->meta['last']);
    }

    public function previousPageUrl()
    {
        return $this->url($this->meta['previous']);
    }

    public function nextPageUrl()
    {
        return $this->url($this->meta['next']);
    }

    public function links($view = null, $data = [])
    {
        return $this->render($view, $data);
    }

    public function render($view = null, $data = [])
    {
        return new HtmlString(
            static::viewFactory()->make($view ?: static::$defaultSimpleView, array_merge($data, [
                'paginator' => $this,
            ]))->render()
        );
    }

    public function toArray()
    {
        return [
            'data' => $this->getCollection(),
            'per_page' => $this->perPage(),
            'total' => $this->meta['total'],
            'next_item' => $this->meta['next_item'],

            'current_page' => $this->meta['current'],
            'first_page' => $this->meta['first'],
            'last_page' => $this->meta['last'],
            'next_page' => $this->meta['next'],
            'previous_page' => $this->meta['previous'],

            'first_page_url' => $this->firstPageUrl(),
            'last_page_url' => $this->lastPageUrl(),
            'next_page_url' => $this->nextPageUrl(),
            'prev_page_url' => $this->previousPageUrl(),
            'path' => $this->path,
        ];
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
