<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Models\Reply;
use Illuminate\Http\Request;
use Mockery as m;
use Carbon\Carbon;
use Amrnn90\CursorPaginator\Cursor;
use Amrnn90\CursorPaginator\CursorPaginator;
use Illuminate\Support\Facades\DB;

class MacroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        factory(Reply::class, 10)->create();
    }

    public function request($requestData)
    {
        app()->instance(Request::class, m::mock(Request::class, ['all' => $requestData]));
    }

    /** @test */
    public function produces_a_cursor_paginator()
    {
        $this->request(['before' => 1]);

        $paginator = Reply::orderBy('id')->cursorPaginate();

        $this->assertInstanceOf(CursorPaginator::class, $paginator);
    }

    /** @test */
    public function resulting_paginator_has_correct_data()
    {
        $this->request(['before' => 5]);

        $paginatorData = Reply::orderBy('id')->cursorPaginate(3)->toArray();

        $this->assertEquals([2, 3, 4], $paginatorData['data']->pluck('id')->all());
    }

    /** @test */
    public function paginator_returns_first_page_if_request_has_no_cursor()
    {

        $paginatorData = Reply::orderBy('id')->cursorPaginate(3)->toArray();

        $this->assertEquals([1, 2, 3], $paginatorData['data']->pluck('id')->all());
        $this->assertEquals(new Cursor('after_i', 1), $paginatorData['current_page']);
    }

    /** @test */
    public function date_casts_can_be_detected_automatically_on_models()
    {
        Reply::truncate();
        foreach ([2006, 2004, 2008, 2010, 2002, 2009, 2011] as $year) {
            factory(Reply::class)->create(['created_at' => Carbon::create($year)]);
        }

        $this->request(['around' => Carbon::create(2008)->timestamp]);

        $paginatorData = Reply::orderBy('created_at')->cursorPaginate(3)->toArray();

        $this->assertEquals(
            [2006, 2008, 2009],
            $paginatorData['data']->pluck('created_at')->map->get('year')->all()
        );
    }

    /** @test */
    public function date_casts_can_be_passed_as_config()
    {
        Reply::truncate();
        foreach ([2006, 2004, 2008, 2010, 2002, 2009, 2011] as $year) {
            factory(Reply::class)->create(['created_at' => Carbon::create($year)]);
        }

        $this->request(['before' => Carbon::create(2010)->timestamp]);

        $paginatorData = DB::table('replies')->orderBy('created_at')->cursorPaginate(3, ['dates' => ['created_at']])->toArray();

        $this->assertEquals(
            [2006, 2008, 2009],
            $paginatorData['data']->pluck('created_at')->map(function ($i) {
                return Carbon::parse($i)->get('year');
            })->all()
        );

        $this->assertEquals(Cursor::before(Carbon::create(2006)->timestamp), $paginatorData['previous_page']);
    }
}
