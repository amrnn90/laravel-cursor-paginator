<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Amrnn90\CursorPaginator\Query\PaginationStrategy\QueryAfterInclusive;
use Tests\Models\Reply;

class QueryAfterInclusiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        factory(Reply::class, 10)->create();
    }

    /** @test */
    public function it_includes_pagination_target()
    {
        $query = Reply::orderBy('id', 'asc');

        $resultQuery = (new QueryAfterInclusive($query, 2))->process(5);
        $this->assertEquals([5,6], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfterInclusive($query, 2))->process(1);
        $this->assertEquals([1, 2], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfterInclusive($query, 2))->process(0);
        $this->assertEquals([1, 2], $resultQuery->get()->pluck('id')->all());
    }

}
