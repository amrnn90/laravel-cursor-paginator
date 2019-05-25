<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Models\Reply;
use Amrnn90\CursorPaginator\Query\QueryMeta;
use Amrnn90\CursorPaginator\Cursor;

class QueryMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        factory(Reply::class, 10)->create();
    }

    /** @test */
    public function  it_gives_total_count()
    {
        $query = Reply::orderBy('id');
        $items = Reply::whereIn('id', [2,3,4])->get()->sortBy('id');
        $cursor = new Cursor('before', 5);

        $meta = (new QueryMeta($query, null))->meta($items, $cursor);
        $this->assertEquals(10, $meta['total']);
    }
}
