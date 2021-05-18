<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Models\Reply;
use Illuminate\Http\Request;
use Mockery as m;
use Carbon\Carbon;
use Amrnn\CursorPaginator\Cursor;
use Amrnn\CursorPaginator\CursorPaginator;
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

        $paginator = Reply::orderBy('id')->myCursorPaginate();

        $this->assertInstanceOf(CursorPaginator::class, $paginator);
    }

    /** @test */
    public function resulting_paginator_has_correct_data()
    {
        $this->request(['before' => 5]);

        $paginatorData = Reply::orderBy('id')->myCursorPaginate(3)->toArray();

        $this->assertEquals([2, 3, 4], collect($paginatorData['data'])->pluck('id')->all());
    }

    /** @test */
    public function paginator_returns_first_page_if_request_has_no_cursor()
    {

        $paginatorData = Reply::orderBy('id')->myCursorPaginate(3)->toArray();

        $this->assertEquals([1, 2, 3], collect($paginatorData['data'])->pluck('id')->all());
        $this->assertEquals(new Cursor('after_i', 1), $paginatorData['current_page']);
    }

    /** @test */
    public function date_casts_can_be_detected_automatically_on_models()
    {
        Reply::truncate();
        foreach ([2006, 2004, 2008, 2010, 2002, 2009, 2011] as $year) {
            factory(Reply::class)->create(['created_at' => Carbon::create($year)]);
        }

        $this->request(['before_i' => Carbon::create(2008)->timestamp]);

        $paginatorData = Reply::orderBy('created_at')->myCursorPaginate(3)->toArray();

        $this->assertEquals(
            [2004, 2006, 2008],
            collect($paginatorData['data'])->pluck('created_at')->map->get('year')->all()
        );
        $this->assertEquals(2002, $paginatorData['next_item']->created_at->get('year'));
    }

    /** @test */
    public function date_casts_can_be_passed_as_config()
    {
        Reply::truncate();
        foreach ([2006, 2004, 2008, 2010, 2002, 2009, 2011] as $year) {
            factory(Reply::class)->create(['created_at' => Carbon::create($year)]);
        }

        $this->request(['before' => Carbon::create(2010)->timestamp]);

        $paginatorData = DB::table('replies')->orderBy('created_at')->myCursorPaginate(3, ['dates' => ['created_at']])->toArray();

        $this->assertEquals(
            [2006, 2008, 2009],
            collect($paginatorData['data'])->pluck('created_at')->map(function ($i) {
                return Carbon::parse($i)->get('year');
            })->all()
        );

        $this->assertEquals(Cursor::before(Carbon::create(2006)->timestamp), $paginatorData['previous_page']);
    }

    /** @test */
    public function maps_cursor_directions_from_config()
    {
        config(['cursor_paginator.directions' => [
            'before' => 'b',
            'before_i' => 'bi',
            'after' => 'a',
            'after_i' => 'ai'
        ], 'cursor_paginator.encode_cursor' => false]);

        $this->request(['b' => 5]);
        $paginatorData = Reply::orderBy('id')->myCursorPaginate(3)->toArray();
        $this->assertEquals(['direction' => 'b', 'target' => 5], $paginatorData['current_page']->toArray());

        $this->request(['a' => 5]);
        $paginatorData = Reply::orderBy('id')->myCursorPaginate(3)->toArray();
        $this->assertEquals(['direction' => 'a', 'target' => 5], $paginatorData['current_page']->toArray());

        $this->assertEquals(['direction' => 'ai', 'target' => 1], $paginatorData['first_page']->toArray());
        $this->assertEquals(['direction' => 'bi', 'target' => 10], $paginatorData['last_page']->toArray());
    }


    /** @test */
    public function maps_encoded_cursor_from_config()
    {
        config(['cursor_paginator' => [
            'encode_cursor' => true,
            'encoded_cursor_name' => 'page-id',
            'directions' => [
                'before' => 'b',
                'before_i' => 'bi',
                'after' => 'a',
                'after_i' => 'ai'
            ]
        ]]);

        $this->request(['page-id' => 'eyJiIjo1fQ']);
        $paginatorData = Reply::orderBy('id')->myCursorPaginate(3)->toArray();
        $this->assertEquals(['page-id' => 'eyJiIjo1fQ'], $paginatorData['current_page']->toArray());

        $this->request(['page-id' => 'eyJhIjo1fQ']);
        $paginatorData = Reply::orderBy('id')->myCursorPaginate(3)->toArray();
        $this->assertEquals(['page-id' => 'eyJhIjo1fQ'], $paginatorData['current_page']->toArray());

        $this->assertEquals(['page-id' => 'eyJhaSI6MX0'], $paginatorData['first_page']->toArray());
        $this->assertEquals(['page-id' => 'eyJiaSI6MTB9'], $paginatorData['last_page']->toArray());
    }

    /** @test */
    public function use_defaut_per_page_from_config()
    {
        $this->request(['before' => 5]);
        $paginatorData = Reply::orderBy('id')->myCursorPaginate()->toArray();
        $this->assertEquals(10, $paginatorData['per_page']);

        config(['cursor_paginator.per_page' => 20]);
        $this->request(['before' => 5]);
        $paginatorData = Reply::orderBy('id')->myCursorPaginate()->toArray();
        $this->assertEquals(20, $paginatorData['per_page']);
    }

    /** @test */
    public function allows_selecting_columns()
    {
        $this->request(['before' => 5]);
        $paginatorData = Reply::select('id')->orderBy('id')->myCursorPaginate()->toArray();

        $this->assertEquals(['id' => 1], $paginatorData['data'][0]->toArray());
    }

    /** @test */
    public function paginator_result_contains_next_item()
    {
        $this->request(['after_i' => 1]);
        $paginatorData = Reply::orderBy('id')->myCursorPaginate(3)->toArray();
        $items = Reply::whereIn('id', [1, 2, 3])->get();
        $nextItem = Reply::find(4);

        $this->assertEquals($items->all(), $paginatorData['data']);
        $this->assertEquals($nextItem, $paginatorData['next_item']);
    }
}
