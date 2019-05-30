<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Amrnn90\CursorPaginator\CursorPaginatorMacro;
use Amrnn90\CursorPaginator\CursorPaginator;
use Amrnn90\CursorPaginator\Cursor;
use Tests\Models\Reply;

class PaginatorMacroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        factory(Reply::class, 10)->create();
        $this->paginatorMacro =  new CursorPaginatorMacro([]);
    }

    /** @test */
    public function produces_a_cursor_paginator()
    {
        $request = ['before' => 1];

        $this->paginatorMacro->setRequestData($request);

        $paginator = $this->paginatorMacro->process(Reply::orderBy('id'));

        $this->assertInstanceOf(CursorPaginator::class, $paginator);
    }

    /** @test */
    public function resulting_paginator_has_correct_data()
    {
        $request = ['before' => 5];

        $this->paginatorMacro
            ->setRequestData($request)
            ->setPerPage(3);

        $paginatorData = $this->paginatorMacro
            ->process(Reply::orderBy('id'))
            ->toArray();

        $this->assertEquals([2, 3, 4], $paginatorData['data']->pluck('id')->all());
    }

    /** @test */
    public function paginator_returns_first_page_if_request_has_no_cursor()
    {
        $request = [];

        $this->paginatorMacro
            ->setRequestData($request)
            ->setPerPage(3);

        $paginatorData = $this->paginatorMacro
            ->process(Reply::orderBy('id'))
            ->toArray();
        $this->assertEquals([1,2,3], $paginatorData['data']->pluck('id')->all());
        $this->assertEquals(new Cursor('after_i', 1), $paginatorData['current_page']);
    }
}
