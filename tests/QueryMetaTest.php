<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Models\Reply;
use Amrnn90\CursorPaginator\Query\QueryMeta;
use Amrnn90\CursorPaginator\Cursor;
use Amrnn90\CursorPaginator\TargetsManager;

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
        $targetsManager = new TargetsManager($query);

        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager))->meta();
        $this->assertEquals(10, $meta['total']);
    }

    /** @test */
    public function  it_gives_correct_meta()
    {
        $query = Reply::orderBy('id')->orderBy('created_at');
        $items = Reply::whereIn('id', [2,3,4])->get()->sortBy('id');
        $cursor = new Cursor('before', 5);
        $targetsManager = new TargetsManager($query);

        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager))->meta();

        $first = Reply::first();
        $last = Reply::latest()->first();
        $previous = Reply::find(2);
        $next = Reply::find(4);

        $this->assertEquals([
            'total' => 10,
            'first' => new Cursor('after_i', "1,{$first->created_at->timestamp}"),
            'last' => new Cursor('before_i', "10,{$last->created_at->timestamp}"),
            'previous' => new Cursor('before', "2,{$previous->created_at->timestamp}"),
            'next' => new Cursor('after', "4,{$next->created_at->timestamp}"),
            'current' => $cursor
        ], $meta);
    }

    /** @test */
    public function it_returns_null_for_previous_if_there_are_no_previous_results()
    {
        $query = Reply::orderBy('id');
        $items = Reply::whereIn('id', [1,2,3])->get()->sortBy('id');
        $cursor = new Cursor('after_i', 1);
        $targetsManager = new TargetsManager($query);
        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager))->meta();

        $this->assertNull($meta['previous']);
    }

    /** @test */
    public function it_returns_null_for_next_if_there_are_no_more_results()
    {
        $query = Reply::orderBy('id');
        $items = Reply::whereIn('id', [8, 9, 10])->get()->sortBy('id');
        $cursor = new Cursor('before_i', 10);
        $targetsManager = new TargetsManager($query);
        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager))->meta();

        $this->assertNull($meta['next']);
    }
}
