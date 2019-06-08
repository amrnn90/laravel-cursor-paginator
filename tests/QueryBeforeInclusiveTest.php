<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Amrnn\CursorPaginator\Query\PaginationStrategy\QueryBeforeInclusive;
use Tests\Models\Reply;

class QueryBeforeInclusiveTest extends TestCase
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

        $resultQuery = (new QueryBeforeInclusive($query, 2))->process(5);
        $this->assertEquals([4, 5], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryBeforeInclusive($query, 2))->process(10);
        $this->assertEquals([9, 10], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryBeforeInclusive($query, 2))->process(11);
        $this->assertEquals([9, 10], $resultQuery->get()->pluck('id')->all());
    }

    /** @test */
    public function it_accepts_multi_column_pagination_targets()
    {
        Reply::truncate();
        foreach ([1, 2, 3, 4,  1, 2, 3, 4,  1, 2, 3, 4,] as $likes) {
            factory(Reply::class)->create(['likes_count' => $likes]);
        }

        $query = Reply::orderBy('likes_count')->orderBy('id');
        $resultQuery = (new QueryBeforeInclusive($query, 3))->process([4, 8]);

        $this->assertEquals(
            [11, 4, 8],
            $resultQuery->pluck('id')->all()
        );
    }
}
