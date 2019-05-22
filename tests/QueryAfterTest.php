<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Amrnn90\CursorPaginator\Query\PaginationStrategy\QueryAfter;
use Amrnn90\CursorPaginator\Exceptions\CursorPaginatorException;
use Tests\Models\Reply;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QueryAfterTest extends TestCase
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

        $resultQuery = (new QueryAfter($query, 2))->process(2);

        $this->assertEquals($originalSql, $query->toSql());
        $this->assertNotEquals($originalSql, $resultQuery->toSql());
    }

    /** @test */
    public function it_returns_items_according_to_per_page_number()
    {
        $query = Reply::orderBy('id', 'asc');

        $resultQuery = (new QueryAfter($query, 3))->process(6);
        $this->assertCount(3, $resultQuery->get());

        $resultQuery = (new QueryAfter($query, 4))->process(6);
        $this->assertCount(4, $resultQuery->get());

        $resultQuery = (new QueryAfter($query, 5))->process(6);
        $this->assertCount(4, $resultQuery->get());
    }

    /** @test */
    public function it_handles_ascending_order()
    {
        $query = Reply::orderBy('id', 'asc');

        $resultQuery = (new QueryAfter($query, 2))->process(5);
        $this->assertEquals([6, 7], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfter($query, 2))->process(10);
        $this->assertEquals([], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfter($query, 2))->process(9);
        $this->assertEquals([10], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfter($query, 2))->process(1);
        $this->assertEquals([2, 3], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfter($query, 2))->process(-99);
        $this->assertEquals([1, 2], $resultQuery->get()->pluck('id')->all());
    }

    /** @test */
    public function it_handles_descending_order()
    {
        $query = Reply::orderBy('id', 'desc');

        $resultQuery = (new QueryAfter($query, 2))->process(5);
        $this->assertEquals([4, 3], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfter($query, 2))->process(1);
        $this->assertEquals([], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfter($query, 2))->process(2);
        $this->assertEquals([1], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfter($query, 2))->process(10);
        $this->assertEquals([9, 8], $resultQuery->get()->pluck('id')->all());

        $resultQuery = (new QueryAfter($query, 2))->process(99);
        $this->assertEquals([10, 9], $resultQuery->get()->pluck('id')->all());
    }

    /** @test */
    public function it_respects_query_filters()
    {
        $query = Reply::whereIn('id', [2, 4, 6, 8, 10])->orderBy('id', 'asc');

        $resultQuery = (new QueryAfter($query, 2))->process(6);
        $this->assertEquals([8, 10], $resultQuery->get()->pluck('id')->all());

        $query = Reply::whereIn('id', [2, 4, 6, 8, 10])->orderBy('id', 'desc');

        $resultQuery = (new QueryAfter($query, 2))->process(6);
        $this->assertEquals([4, 2], $resultQuery->get()->pluck('id')->all());
    }

    /** @test */
    public function it_detects_ordered_by_column()
    {
        Reply::truncate();
        foreach ([2006, 2004, 2008, 2010, 2002, 2009, 2011] as $year) {
            factory(Reply::class)->create(['created_at' => Carbon::createFromDate($year)]);
        }

        $query = Reply::orderBy('created_at', 'asc');
        $resultQuery = (new QueryAfter($query, 2))->process(Carbon::createFromDate((2008)));
        $this->assertEquals(
            [2009, 2010],
            $resultQuery->get()->pluck('created_at')->map->get('year')->all()
        );

        $query = Reply::orderBy('created_at', 'desc');
        $resultQuery = (new QueryAfter($query, 2))->process(Carbon::createFromDate((2008)));
        $this->assertEquals(
            [2006, 2004],
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
        $resultQuery = (new QueryAfter($query, 2))->process(Carbon::createFromDate(2008)->timestamp);
        $this->assertEquals(
            [2009, 2010],
            $resultQuery->get()->pluck('created_at')->map->get('year')->all()
        );
    }

    /** @test */
    public function it_throws_exception_when_there_is_no_order()
    {
        $this->expectException(CursorPaginatorException::class);

        $query = Reply::query();
        (new QueryAfter($query, 2))->process(5);
    }

    /** @test */
    public function it_handles_eager_loading()
    {
        $query = Reply::with('user')->orderBy('id');
        $reply = (new QueryAfter($query, 1))->process(5)->first();

        $this->assertTrue($reply->relationLoaded('user'));
    }

    /** @test */
    public function it_works_with_query_builders()
    {
        $query = DB::table('users')->orderBy('id');
        $resultQuery = (new QueryAfter($query, 2))->process(5);
        $this->assertEquals([6, 7], $resultQuery->get()->pluck('id')->all());

        $query = DB::table('users')->orderBy('id', 'desc');
        $resultQuery = (new QueryAfter($query, 2))->process(5);
        $this->assertEquals([4, 3], $resultQuery->get()->pluck('id')->all());

        $query = DB::table('users')->whereIn('id', [2, 4, 6, 8, 10])->orderBy('id');
        $resultQuery = (new QueryAfter($query, 2))->process(6);
        $this->assertEquals([8, 10], $resultQuery->get()->pluck('id')->all());
    }

    /** @test */
    public function it_works_with_computed_columns()
    {
        Reply::truncate();
        foreach ([2006, 2004, 2008, 2010, 2002, 2009, 2011] as $year) {
            factory(Reply::class)->create(['created_at' => Carbon::createFromDate($year)]);
        }

        $query = DB::table('replies')->selectRaw('strftime("%Y", `created_at`) as year')->orderBy('year');
        $resultQuery = (new QueryAfter($query, 2))->process('2008');

        $this->assertEquals(
            ['2009', '2010'],
            $resultQuery->pluck('year')->all()
        );
    }

    /** @test */
    public function it_can_handle_multiple_column_ordering()
    {
        Reply::truncate();
        foreach ([2004, 2003, 2003, 2001, 2003, 2004] as $year) {
            factory(Reply::class)->create(['created_at' => Carbon::createFromDate($year)]);
        }

        $query = Reply::orderBy('created_at')->orderBy('id', 'desc');
        $resultQuery = (new QueryAfter($query, 3))->process(Carbon::createFromDate(2002));
        $this->assertEquals(
            [5, 3, 2],
            $resultQuery->pluck('id')->all()
        );

        $query = Reply::orderBy('created_at', 'desc')->orderBy('id', 'desc');
        $resultQuery = (new QueryAfter($query, 3))->process(Carbon::createFromDate(2005));
        $this->assertEquals(
            [6, 1, 5],
            $resultQuery->pluck('id')->all()
        );
    }
}
