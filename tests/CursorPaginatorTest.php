<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Amrnn\CursorPaginator\CursorPaginator;
use Amrnn\CursorPaginator\Cursor;

class CursorPaginatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function meta($override = [])
    {
        return array_merge([
            'total' => 10,
            'previous' => new Cursor('before', 2),
            'next' => new Cursor('after', 4),
            'first' => new Cursor('after_i', 1),
            'last' => new Cursor('before_i', 10),
            'current' => new Cursor('after_i', 1),
        ], $override);
    }

    /** @test */
    public function it_returns_current_page()
    {
        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta());

        $this->assertEquals(new Cursor('after_i', 1), $paginator->currentPage());
    }

    /** @test */
    public function it_returns_per_page()
    {
        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta());

        $this->assertEquals(3, $paginator->perPage());
    }

    /** @test */
    public function it_knows_if_there_are_more_pages()
    {
        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['next' => 4]));
        $this->assertEquals(true, $paginator->hasMorePages());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['next' => null]));
        $this->assertEquals(false, $paginator->hasMorePages());
    }

    /** @test */
    public function it_knows_if_it_should_split_pages()
    {

        $paginator = new CursorPaginator([1, 2, 3], 3, $this->meta(['total' => 5]));
        $this->assertEquals(true, $paginator->hasPages());

        $paginator = new CursorPaginator([1, 2, 3, 4, 5], 5, $this->meta(['total' => 5]));
        $this->assertEquals(false, $paginator->hasPages());

        $paginator = new CursorPaginator([3, 4, 5], 3, $this->meta(['total' => 5]));
        $this->assertEquals(true, $paginator->hasPages());
    }

    /** @test */
    public function it_knows_when_on_first_page()
    {
        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => null]));
        $this->assertEquals(true, $paginator->onFirstPage());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => new Cursor('before', 3)]));
        $this->assertEquals(false, $paginator->onFirstPage());
    }

    /** @test */
    public function it_knows_the_current_page_name()
    {
        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['current' => ['direction' => 'before']]));
        $this->assertEquals('before', $paginator->getPageName());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['current' => ['direction' => 'after']]));
        $this->assertEquals('after', $paginator->getPageName());
    }

    /** @test */
    public function it_can_resolve_current_path()
    {
        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta());

        $this->assertEquals('http://localhost', $paginator::resolveCurrentPath());
    }

    /** @test */
    public function it_produces_previous_page_url()
    {
        config(['cursor_paginator.encode_cursor' => false]);

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => new Cursor('before', 2)]));

        $this->assertEquals('http://localhost?before=2', $paginator->previousPageUrl());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => new Cursor(null, null)]));
        $this->assertNull($paginator->previousPageUrl());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => null]));
        $this->assertNull($paginator->previousPageUrl());

        config(['cursor_paginator.encode_cursor' => true]);

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => new Cursor('before', 2)]));

        $this->assertEquals('http://localhost?cursor=eyJiZWZvcmUiOjJ9', $paginator->previousPageUrl());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => new Cursor(null, null)]));
        $this->assertNull($paginator->previousPageUrl());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => null]));
        $this->assertNull($paginator->previousPageUrl());
    }

    /** @test */
    public function it_produces_next_page_url()
    {
        config(['cursor_paginator.encode_cursor' => false]);

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['next' => new Cursor('after', 4)]));

        $this->assertEquals('http://localhost?after=4', $paginator->nextPageUrl());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['next' => new Cursor('after', null)]));
        $this->assertNull($paginator->nextPageUrl());

        config(['cursor_paginator.encode_cursor' => true]);

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['next' => new Cursor('after', 4)]));

        $this->assertEquals('http://localhost?cursor=eyJhZnRlciI6NH0', $paginator->nextPageUrl());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['next' => new Cursor('after', null)]));
        $this->assertNull($paginator->nextPageUrl());
    }

    /** @test */
    public function it_appends_extra_query_params_to_url()
    {
        config(['cursor_paginator.encode_cursor' => false]);

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['next' => new Cursor('after', 4)]));
        $paginator->appends('extra', 'true');

        $this->assertEquals('http://localhost?extra=true&after=4', $paginator->nextPageUrl());

        config(['cursor_paginator.encode_cursor' => true]);

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['next' => new Cursor('after', 4)]));
        $paginator->appends('extra', 'true');

        $this->assertEquals('http://localhost?extra=true&cursor=eyJhZnRlciI6NH0', $paginator->nextPageUrl());
    }

    /** @test */
    public function it_returns_correct_data()
    {
        config(['cursor_paginator.encode_cursor' => false]);

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta([
            'total' => 10,
            'current' => new Cursor('after_i', 2),
            'next' => new Cursor('after', 4),
            'has_next' => false,
            'previous' => new Cursor('before', 2),
            'has_previous' => true,
            'first' => new Cursor('after_i', 1),
            'last' => new Cursor('before_i', 10),
            'next_item' => 1
        ]));

        $this->assertEquals([
            'data' => [2, 3, 4],
            'per_page' => 3,
            'current_page' => new Cursor('after_i', 2),
            'first_page' => new Cursor('after_i', 1),
            'last_page' => new Cursor('before_i', 10),
            'next_page' => new Cursor('after', 4),
            'has_next' => false,
            'previous_page' => new Cursor('before', 2),
            'has_previous' => true,
            'first_page_url' => 'http://localhost?after_i=1',
            'last_page_url' => 'http://localhost?before_i=10',
            'next_page_url' => 'http://localhost?after=4',
            'prev_page_url' => 'http://localhost?before=2',
            'path' => 'http://localhost',
            'total' => 10,
            'next_item' => 1
        ], $paginator->toArray());

        config(['cursor_paginator.encode_cursor' => true]);

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta([
            'total' => 10,
            'current' => new Cursor('after_i', 2),
            'next' => new Cursor('after', 4),
            'has_next' => true,
            'previous' => new Cursor('before', 2),
            'has_previous' => false,
            'first' => new Cursor('after_i', 1),
            'last' => new Cursor('before_i', 10),
            'next_item' => 1
        ]));

        $this->assertEquals([
            'data' => [2, 3, 4],
            'per_page' => 3,
            'current_page' => new Cursor('after_i', 2),
            'first_page' => new Cursor('after_i', 1),
            'last_page' => new Cursor('before_i', 10),
            'next_page' => new Cursor('after', 4),
            'has_next' => true,
            'previous_page' => new Cursor('before', 2),
            'has_previous' => false,
            'first_page_url' => 'http://localhost?cursor=eyJhZnRlcl9pIjoxfQ',
            'last_page_url' => 'http://localhost?cursor=eyJiZWZvcmVfaSI6MTB9',
            'next_page_url' => 'http://localhost?cursor=eyJhZnRlciI6NH0',
            'prev_page_url' => 'http://localhost?cursor=eyJiZWZvcmUiOjJ9',
            'path' => 'http://localhost',
            'total' => 10,
            'next_item' => 1
        ], $paginator->toArray());
    }

    /** @test */
    public function it_renders_pagination_links()
    {
        config(['cursor_paginator.encode_cursor' => false]);

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => null, 'next' => new Cursor('after', 3)]));
        $this->assertStringContainsString('<li class="page-item disabled" aria-disabled="true">', $paginator->links());
        $this->assertStringContainsString('<a class="page-link" href="http://localhost?after=3" rel="next">', $paginator->links());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => new Cursor('before_i', 5), 'next' => null]));
        $this->assertStringContainsString('<a class="page-link" href="http://localhost?before_i=5" rel="prev">', $paginator->links());
        $this->assertStringContainsString('<li class="page-item disabled" aria-disabled="true">', $paginator->links());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['total' => 3]));
        $this->assertEquals('', $paginator->links());

        config(['cursor_paginator.encode_cursor' => true]);

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => null, 'next' => new Cursor('after', 3)]));
        $this->assertStringContainsString('<li class="page-item disabled" aria-disabled="true">', $paginator->links());
        $this->assertStringContainsString('<a class="page-link" href="http://localhost?cursor=eyJhZnRlciI6M30" rel="next">', $paginator->links());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['previous' => new Cursor('before_i', 5), 'next' => null]));
        $this->assertStringContainsString('<a class="page-link" href="http://localhost?cursor=eyJiZWZvcmVfaSI6NX0" rel="prev">', $paginator->links());
        $this->assertStringContainsString('<li class="page-item disabled" aria-disabled="true">', $paginator->links());

        $paginator = new CursorPaginator([2, 3, 4], 3, $this->meta(['total' => 3]));
        $this->assertEquals('', $paginator->links());
    }
}
