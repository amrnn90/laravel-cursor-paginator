<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Amrnn90\CursorPaginator\{CursorPaginatorMacro, CursorPaginator};
use Illuminate\Http\Request;
use Mockery;
use Illuminate\Database\Eloquent\Builder;
use Tests\Models\Reply;

class PaginatorMacroTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();

        
    }

    /** @test */
    public function produces_a_cursor_paginator()
    {
        factory(Reply::class, 5)->create();
        $requestData = [
            'before' => 3 
        ];

        $paginatorMacro = new CursorPaginatorMacro($requestData);

        $result = $paginatorMacro->process(Reply::orderBy('id'));

        $this->assertInstanceOf(CursorPaginator::class, $result);
    }


}