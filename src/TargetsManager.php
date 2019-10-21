<?php

namespace Amrnn\CursorPaginator;

use Amrnn\CursorPaginator\Query\QueryHelpers;
use DateTimeInterface;
use Carbon\Carbon;

class TargetsManager
{
    use QueryHelpers;

    public function __construct($query, $options = [])
    {
        $this->options = $options;
        $this->dates = $options['dates'] ?? [];
        $this->query = $query;
    }

    public function serialize($targets)
    {
        if (is_array($targets)) {
            return $this->serializeArray($targets);
        }
        return $this->serializeSingle($targets, 0);
    }

    public function parse($target)
    {
        if ($target !== '0' && empty($target)) return [];

        $result = [];
        foreach (explode(',', (string)$target) as $index => $target) {
            $result[] = $this->parseSingle($target, $index);
        }
        return $result;
    }

    public function targetFromItem($item)
    {
        if (!$item) {
            return null;
        }
        
        $targets = [];
        foreach ($this->getOrderColumnList($this->query) as $column) {
            $targets[] = $this->extractColumnFromItem($item, $column);
        }
        return $this->serialize($targets);
    }

    protected function serializeArray($targets)
    {
        $results = [];
        foreach ($targets as $index => $target) {
            $results[] = $this->serializeSingle($target, $index);
        }
        return join(',', $results);
    }

    protected function serializeSingle($target, $index)
    {
        if ($this->isDateTarget($target, $index)) {
            return Carbon::parse($target)->getTimestamp();
        }
        return (string)$target;
    }

    protected function parseSingle($target, $index)
    {
        if ($this->isDateTarget($target, $index)) {
            if (filter_var($target, FILTER_VALIDATE_INT)) {
                return Carbon::createFromTimestamp((int)$target);
            }
            return Carbon::parse($target);
        }
        return $target;
    }

    protected function isDateTarget($target, $index)
    {
        return is_a($target, DateTimeInterface::class) ||
            in_array($this->getOrderColumn($this->query, $index), $this->dates) ||
            $this->orderColumnIsDate($this->query, $index);
    }

    protected function extractColumnFromItem($item, $column) 
    {
        if (is_a($item, \stdClass::class)) {
            return $item->$column;
        }
        return $item[$column];
    }
}
