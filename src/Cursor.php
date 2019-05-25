<?php

namespace Amrnn90\CursorPaginator;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Cursor implements Jsonable, Arrayable {
    public $direction;
    public $target;

    public function __construct($direction, $target)
    {
        $this->direction = $direction;
        $this->setTarget($target);
    }

    public function setTarget($target)
    {
        if (is_array($target)) {
            $this->target = join(',', $target);
        } else {
            $this->target = $target;
        }
    }

    public function getParsedTarget()
    {
        return explode(',', $this->target);
    }

    public function urlParams()
    {
        if (!$this->isValid()) return null;
        return [$this->direction => $this->target];
    }

    public function isValid() {
        return !(empty($this->direction) || empty($this->target));
    }

    public function toArray()
    {
        if (!$this->isValide()) return null;
        return [
            'direction' => $this->direction,
            'target' => $this->target
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}