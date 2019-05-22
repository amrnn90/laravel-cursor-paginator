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
        $this->assertEquals([5, 6], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfterInclusive($query, 2))->process(1);
        $this->assertEquals([1, 2], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfterInclusive($query, 2))->process(0);
        $this->assertEquals([1, 2], $resultQuery->get()->pluck('id')->all());
    }

    /** @test */
    public function it_accepts_multi_column_pagination_targets()
    {
        Reply::truncate();
        foreach ([1, 2, 3, 4,  1, 2, 3, 4,  1, 2, 3, 4,] as $likes) {
            factory(Reply::class)->create(['likes_count' => $likes]);
        }

        $query = Reply::orderBy('likes_count')->orderBy('id');
        $resultQuery = (new QueryAfterInclusive($query, 3))->process([3, 7]);

        $this->assertEquals(
            [7, 11, 4],
            $resultQuery->pluck('id')->all()
        );

        $query = Reply::orderBy('likes_count', 'desc')->orderBy('id', 'desc');
        $resultQuery = (new QueryAfterInclusive($query, 3))->process([3, 7]);

        $this->assertEquals(
            [7, 3, 10],
            $resultQuery->pluck('id')->all()
        );
    }
}
