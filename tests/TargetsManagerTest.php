<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Models\Reply;
use Amrnn90\CursorPaginator\TargetsManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TargetsManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        factory(Reply::class, 10)->create();
        $this->query = Reply::orderBy('id');
        $this->targetsManager = new TargetsManager($this->query);
    }

    /** @test */
    public function it_can_serialize_single_targets()
    {
        $this->assertEquals('1', $this->targetsManager->serialize([1]));
        $this->assertEquals('1', $this->targetsManager->serialize(1));
    }

    /** @test */
    public function it_can_parse_single_targets()
    {
        $this->assertEquals([1], $this->targetsManager->parse('1'));
    }

    /** @test */
    public function it_parses_empty_values_to_empty_array()
    {
        $this->assertEquals([], $this->targetsManager->parse(''));
        $this->assertEquals([], $this->targetsManager->parse(null));
    }

    /** @test */
    public function it_serializes_dates_into_timestamps()
    {
        $date = now();
        $this->assertEquals($date->timestamp, $this->targetsManager->serialize([$date]));
    }

    /** @test */
    public function it_parses_timestamps_into_dates()
    {
        $query = Reply::orderBy('created_at');
        $targetsManager = new TargetsManager($query);
        $timestamp = now()->timestamp;
        $result = $targetsManager->parse($timestamp);
        $this->assertEquals([Carbon::createFromTimestamp($timestamp)], $result);
    }

    /** @test */
    public function it_can_serialize_multiple_targets()
    {
        $this->assertEquals('1,second', $this->targetsManager->serialize([1, 'second']));
    
        $date = now();
        $this->assertEquals("1,$date->timestamp", $this->targetsManager->serialize([1, $date]));
    }

    /** @test */
    public function it_can_parse_multiple_targets()
    {
        $this->assertEquals(['1', 'second'], $this->targetsManager->parse('1,second'));
    
        $query = Reply::orderBy('id')->orderBy('created_at');
        $targetsManager = new TargetsManager($query);
        $timestamp = now()->timestamp;
        $result = $targetsManager->parse("1,$timestamp");
        $this->assertEquals(['1', Carbon::createFromTimestamp($timestamp)], $result);
    }


    /** @test */
    public function it_can_detect_date_targets_from_queries_and_serializes_them_into_timestamps()
    {
        $query = Reply::orderBy('created_at');
        $targetsManager = new TargetsManager($query);
        $date = now();

        $this->assertEquals($date->timestamp, $targetsManager->serialize([$date->toDateTimeString()]));
    }

    /** @test */
    public function it_can_detect_date_targets_from_options_and_serializes_them_into_timestamps()
    {
        $query = DB::table('replies')->orderBy('created_at');
        $targetsManager = new TargetsManager($query, ['dates' => ['created_at']]);
        $date = now();

        $this->assertEquals($date->timestamp, $targetsManager->serialize([$date->toDateTimeString()]));
    }

    /** @test */
    public function it_produces_target_from_item()
    {
        $item = Reply::first();

        $this->assertEquals($item->id, $this->targetsManager->targetFromItem($item));
    }

    /** @test */
    public function it_produces_multi_column_target_from_item()
    {
        $query = Reply::orderBy('id')->orderBy('created_at');
        $targetsManager = new TargetsManager($query);
        $item = Reply::first();
        $expected = $item->id . ',' . $item->created_at->timestamp;
        $this->assertEquals($expected, $targetsManager->targetFromItem($item));
    }
}
