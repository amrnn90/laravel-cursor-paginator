<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Amrnn90\CursorPaginator\Query\PaginationStrategy\QueryAround;
use Amrnn90\CursorPaginator\Exceptions\CursorPaginatorException;
use Tests\Models\Reply;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QueryAroundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        factory(Reply::class, 10)->create();
    }

    /** @test */
    public function it_does_not_mutate_original_query()
    {
        $query = Reply::orderBy('id');
        $originalSql = $query->toSql();

        $resultQuery = (new QueryAround($query, 2))->process(2);

        $this->assertEquals($originalSql, $query->toSql());
        $this->assertNotEquals($originalSql, $resultQuery->toSql());
    }

    /** @test */
    public function it_returns_items_according_to_per_page_number()
    {
        $query = Reply::orderBy('id', 'asc');

        $resultQuery = (new QueryAround($query, 3))->process(5);
        $this->assertCount(3, $resultQuery->get());

        $resultQuery = (new QueryAround($query, 3))->process(1);
        $this->assertCount(2, $resultQuery->get());

        $resultQuery = (new QueryAround($query, 3))->process(10);
        $this->assertCount(2, $resultQuery->get());

        $resultQuery = (new QueryAround($query, 2))->process(10);
        $this->assertCount(2, $resultQuery->get());

        $resultQuery = (new QueryAround($query, 2))->process(1);
        $this->assertCount(1, $resultQuery->get());

        $resultQuery = (new QueryAround($query, 1))->process(10);
        $this->assertCount(1, $resultQuery->get());

        $resultQuery = (new QueryAround($query, 1))->process(1);
        $this->assertCount(1, $resultQuery->get());
    }

    /** @test */
    public function it_handles_ascending_order()
    {
        $query = Reply::orderBy('id', 'asc');

        $resultQuery = (new QueryAround($query, 3))->process(5);
        $this->assertEquals([4, 5, 6], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 1))->process(1);
        $this->assertEquals([1], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 2))->process(1);
        $this->assertEquals([1], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 3))->process(1);
        $this->assertEquals([1, 2], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 4))->process(1);
        $this->assertEquals([1, 2], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 1))->process(10);
        $this->assertEquals([10], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 2))->process(10);
        $this->assertEquals([9, 10], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 3))->process(10);
        $this->assertEquals([9, 10], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 4))->process(10);
        $this->assertEquals([8, 9, 10], $resultQuery->get()->pluck('id')->all());
    }

    /** @test */
    public function it_handles_descending_order()
    {
        $query = Reply::orderBy('id', 'desc');

        $resultQuery = (new QueryAround($query, 3))->process(5);
        $this->assertEquals([6, 5, 4], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 1))->process(1);
        $this->assertEquals([1], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 2))->process(1);
        $this->assertEquals([2, 1], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 3))->process(1);
        $this->assertEquals([2, 1], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 4))->process(1);
        $this->assertEquals([3, 2, 1], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 1))->process(10);
        $this->assertEquals([10], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 2))->process(10);
        $this->assertEquals([10], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 3))->process(10);
        $this->assertEquals([10, 9], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 4))->process(10);
        $this->assertEquals([10, 9], $resultQuery->get()->pluck('id')->all());
    }

    /** @test */
    public function it_respects_query_filters()
    {
        $query = Reply::whereIn('id', [2, 4, 6, 8, 10])->orderBy('id', 'asc');

        $resultQuery = (new QueryAround($query, 3))->process(6);
        $this->assertEquals([4, 6, 8], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 2))->process(6);
        $this->assertEquals([4, 6], $resultQuery->get()->pluck('id')->all());

        $query = Reply::whereIn('id', [2, 4, 6, 8, 10])->orderBy('id', 'desc');

        $resultQuery = (new QueryAround($query, 3))->process(6);
        $this->assertEquals([8, 6, 4], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAround($query, 2))->process(6);
        $this->assertEquals([8, 6], $resultQuery->get()->pluck('id')->all());
    }

    /** @test */
    public function it_detects_ordered_by_column()
    {
        Reply::truncate();
        foreach ([2006, 2004, 2008, 2010, 2002, 2009, 2011] as $year) {
            factory(Reply::class)->create(['created_at' => Carbon::createFromDate($year)]);
        }

        $query = Reply::orderBy('created_at', 'asc');
        $resultQuery = (new QueryAround($query, 3))->process(Carbon::createFromDate((2008)));
        $this->assertEquals(
            [2006, 2008, 2009],
            $resultQuery->get()->pluck('created_at')->map->get('year')->all()
        );

        $query = Reply::orderBy('created_at', 'desc');
        $resultQuery = (new QueryAround($query, 3))->process(Carbon::createFromDate((2008)));
        $this->assertEquals(
            [2009, 2008, 2006],
            $resultQuery->get()->pluck('created_at')->map->get('year')->all()
        );
    }

    /** @test */
    public function it_detects_date_casts_on_models()
    {
        Reply::truncate();
        foreach ([2006, 2004, 2008, 2010, 2002, 2009, 2011] as $year) {
            factory(Reply::class)->create(['created_at' => Carbon::createFromDate($year)]);
        }

        $query = Reply::orderBy('created_at', 'asc');
        $resultQuery = (new QueryAround($query, 3))->process(Carbon::createFromDate(2008)->timestamp);
        $this->assertEquals(
            [2006, 2008, 2009],
            $resultQuery->get()->pluck('created_at')->map->get('year')->all()
        );
    }

    /** @test */
    public function it_throws_exception_when_there_is_no_order()
    {
        $this->expectException(CursorPaginatorException::class);

        $query = Reply::query();
        (new QueryAround($query, 2))->process(5);
    }

    /** @test */
    public function it_handles_eager_loading()
    {
        $query = Reply::with('user')->orderBy('id');
        $reply = (new QueryAround($query, 1))->process(5)->first();

        $this->assertTrue($reply->relationLoaded('user'));
    }

    /** @test */
    public function it_works_with_query_builders()
    {
        $query = DB::table('users')->orderBy('id');
        $resultQuery = (new QueryAround($query, 3))->process(5);
        $this->assertEquals([4, 5, 6], $resultQuery->get()->pluck('id')->all());

        $query = DB::table('users')->orderBy('id', 'desc');
        $resultQuery = (new QueryAround($query, 3))->process(5);
        $this->assertEquals([6, 5, 4], $resultQuery->get()->pluck('id')->all());

        $query = DB::table('users')->whereIn('id', [2, 4, 6, 8, 10])->orderBy('id');
        $resultQuery = (new QueryAround($query, 3))->process(6);
        $this->assertEquals([4, 6, 8], $resultQuery->get()->pluck('id')->all());
    }
}
