<?php

namespace QT\Test;

use PHPUnit\Framework\TestCase;
use QT\GraphQL\Options\QueryOption;
use QT\GraphQL\Options\ChunkOption;
use QT\GraphQL\Options\PageOption;
use QT\GraphQL\Options\CursorOption;

class OptionsTest extends TestCase
{
    // ==================== QueryOption 测试 ====================

    public function testQueryOptionDefaultValues()
    {
        $option = new QueryOption();

        $this->assertEquals([], $option->filters);
        $this->assertEquals([], $option->orderBy);
    }

    public function testQueryOptionWithArrayFilters()
    {
        $option = new QueryOption([
            'filters' => ['status' => ['eq' => 1]]
        ]);

        $this->assertEquals(['status' => ['eq' => 1]], $option->filters);
    }

    public function testQueryOptionWithJsonStringFilters()
    {
        $option = new QueryOption([
            'filters' => '{"status":{"eq":1}}'
        ]);

        $this->assertEquals(['status' => ['eq' => 1]], $option->filters);
    }

    public function testQueryOptionWithArrayOrderBy()
    {
        $option = new QueryOption([
            'orderBy' => [['id' => 'desc']]
        ]);

        $this->assertEquals([['id' => 'desc']], $option->orderBy);
    }

    public function testQueryOptionWithJsonStringOrderBy()
    {
        $option = new QueryOption([
            'orderBy' => '[{"id":"desc"}]'
        ]);

        $this->assertEquals([['id' => 'desc']], $option->orderBy);
    }

    public function testQueryOptionIgnoresUnknownKeys()
    {
        $option = new QueryOption([
            'filters' => ['status' => 1],
            'unknownKey' => 'value'
        ]);

        $this->assertEquals(['status' => 1], $option->filters);
        $this->assertFalse(property_exists($option, 'unknownKey'));
    }

    public function testQueryOptionWithEmptyArray()
    {
        $option = new QueryOption([]);

        $this->assertEquals([], $option->filters);
        $this->assertEquals([], $option->orderBy);
    }

    // ==================== ChunkOption 测试 ====================

    public function testChunkOptionDefaultValues()
    {
        $option = new ChunkOption();

        $this->assertEquals([], $option->filters);
        $this->assertEquals([], $option->orderBy);
        $this->assertNull($option->take);
        $this->assertNull($option->skip);
        $this->assertNull($option->all);
    }

    public function testChunkOptionWithCustomValues()
    {
        $option = new ChunkOption([
            'filters' => ['status' => ['eq' => 1]],
            'orderBy' => [['id' => 'desc']],
            'take' => 50,
            'skip' => 100,
            'all' => true
        ]);

        $this->assertEquals(['status' => ['eq' => 1]], $option->filters);
        $this->assertEquals([['id' => 'desc']], $option->orderBy);
        $this->assertEquals(50, $option->take);
        $this->assertEquals(100, $option->skip);
        $this->assertTrue($option->all);
    }

    public function testChunkOptionWithOnlyTake()
    {
        $option = new ChunkOption(['take' => 200]);

        $this->assertEquals(200, $option->take);
        $this->assertNull($option->skip);
    }

    public function testChunkOptionWithOnlySkip()
    {
        $option = new ChunkOption(['skip' => 50]);

        $this->assertNull($option->take);
        $this->assertEquals(50, $option->skip);
    }

    public function testChunkOptionWithAllFalse()
    {
        $option = new ChunkOption(['all' => false]);

        $this->assertFalse($option->all);
    }

    public function testChunkOptionInheritsQueryOption()
    {
        $option = new ChunkOption([
            'filters' => ['status' => 1],
            'take' => 50
        ]);

        $this->assertInstanceOf(QueryOption::class, $option);
        $this->assertEquals(['status' => 1], $option->filters);
    }

    // ==================== PageOption 测试 ====================

    public function testPageOptionDefaultValues()
    {
        $option = new PageOption();

        $this->assertEquals([], $option->filters);
        $this->assertEquals([], $option->orderBy);
        $this->assertNull($option->take);
        $this->assertNull($option->page);
    }

    public function testPageOptionWithCustomValues()
    {
        $option = new PageOption([
            'filters' => ['status' => ['eq' => 1]],
            'orderBy' => [['id' => 'desc']],
            'take' => 50,
            'page' => 3
        ]);

        $this->assertEquals(['status' => ['eq' => 1]], $option->filters);
        $this->assertEquals([['id' => 'desc']], $option->orderBy);
        $this->assertEquals(50, $option->take);
        $this->assertEquals(3, $option->page);
    }

    public function testPageOptionWithFirstPage()
    {
        $option = new PageOption(['page' => 1]);

        $this->assertEquals(1, $option->page);
    }

    public function testPageOptionWithLargePageNumber()
    {
        $option = new PageOption(['page' => 1000]);

        $this->assertEquals(1000, $option->page);
    }

    public function testPageOptionWithOnlyTake()
    {
        $option = new PageOption(['take' => 100]);

        $this->assertEquals(100, $option->take);
        $this->assertNull($option->page);
    }

    public function testPageOptionInheritsQueryOption()
    {
        $option = new PageOption([
            'filters' => ['status' => 1],
            'page' => 2
        ]);

        $this->assertInstanceOf(QueryOption::class, $option);
        $this->assertEquals(['status' => 1], $option->filters);
    }

    // ==================== CursorOption 测试 ====================

    public function testCursorOptionDefaultValues()
    {
        $option = new CursorOption();

        $this->assertEquals([], $option->filters);
        $this->assertEquals([], $option->orderBy);
        $this->assertNull($option->offset);
        $this->assertNull($option->limit);
    }

    public function testCursorOptionWithCustomValues()
    {
        $option = new CursorOption([
            'filters' => ['status' => ['eq' => 1]],
            'orderBy' => [['id' => 'desc']],
            'offset' => 100,
            'limit' => 50
        ]);

        $this->assertEquals(['status' => ['eq' => 1]], $option->filters);
        $this->assertEquals([['id' => 'desc']], $option->orderBy);
        $this->assertEquals(100, $option->offset);
        $this->assertEquals(50, $option->limit);
    }

    public function testCursorOptionWithZeroOffset()
    {
        $option = new CursorOption(['offset' => 0]);

        $this->assertEquals(0, $option->offset);
    }

    public function testCursorOptionWithZeroLimit()
    {
        $option = new CursorOption(['limit' => 0]);

        $this->assertEquals(0, $option->limit);
    }

    public function testCursorOptionInheritsQueryOption()
    {
        $option = new CursorOption([
            'filters' => ['status' => 1],
            'offset' => 10
        ]);

        $this->assertInstanceOf(QueryOption::class, $option);
        $this->assertEquals(['status' => 1], $option->filters);
    }

    // ==================== 边界情况测试 ====================

    public function testComplexFilterStructure()
    {
        $option = new QueryOption([
            'filters' => [
                'status' => ['eq' => 1],
                'age' => ['between' => [18, 65]],
                'name' => ['like' => 'John'],
                'id' => ['in' => [1, 2, 3, 4, 5]]
            ]
        ]);

        $this->assertCount(4, $option->filters);
        $this->assertEquals(['eq' => 1], $option->filters['status']);
        $this->assertEquals(['between' => [18, 65]], $option->filters['age']);
        $this->assertEquals(['like' => 'John'], $option->filters['name']);
        $this->assertEquals(['in' => [1, 2, 3, 4, 5]], $option->filters['id']);
    }

    public function testComplexOrderByStructure()
    {
        $option = new QueryOption([
            'orderBy' => [
                ['id' => 'desc'],
                ['name' => 'asc'],
                ['created_at' => 'desc']
            ]
        ]);

        $this->assertCount(3, $option->orderBy);
        $this->assertEquals('desc', $option->orderBy[0]['id']);
        $this->assertEquals('asc', $option->orderBy[1]['name']);
        $this->assertEquals('desc', $option->orderBy[2]['created_at']);
    }

    public function testJoinTableOrderByStructure()
    {
        $option = new QueryOption([
            'orderBy' => [
                ['users' => ['name' => 'asc']]
            ]
        ]);

        $this->assertIsArray($option->orderBy[0]['users']);
        $this->assertEquals('asc', $option->orderBy[0]['users']['name']);
    }

    // ==================== JSON 解析测试 ====================

    public function testJsonFiltersParsing()
    {
        $jsonFilters = json_encode([
            'status' => ['eq' => 1],
            'type' => ['in' => ['a', 'b', 'c']]
        ]);

        $option = new QueryOption(['filters' => $jsonFilters]);

        $this->assertEquals(['eq' => 1], $option->filters['status']);
        $this->assertEquals(['in' => ['a', 'b', 'c']], $option->filters['type']);
    }

    public function testJsonOrderByParsing()
    {
        $jsonOrderBy = json_encode([
            ['id' => 'desc'],
            ['name' => 'asc']
        ]);

        $option = new QueryOption(['orderBy' => $jsonOrderBy]);

        $this->assertCount(2, $option->orderBy);
        $this->assertEquals('desc', $option->orderBy[0]['id']);
        $this->assertEquals('asc', $option->orderBy[1]['name']);
    }

    public function testInvalidJsonStringReturnsNull()
    {
        $option = new QueryOption(['filters' => 'invalid json']);

        $this->assertNull($option->filters);
    }

    // ==================== 属性访问测试 ====================

    public function testPropertyAccess()
    {
        $option = new ChunkOption([
            'take' => 50,
            'skip' => 100
        ]);

        $this->assertEquals(50, $option->take);
        $this->assertEquals(100, $option->skip);

        $option->take = 200;
        $this->assertEquals(200, $option->take);
    }

    public function testInheritanceChain()
    {
        $chunk = new ChunkOption(['take' => 50, 'skip' => 100]);
        $page = new PageOption(['take' => 50, 'page' => 2]);
        $cursor = new CursorOption(['offset' => 100, 'limit' => 50]);

        $this->assertInstanceOf(QueryOption::class, $chunk);
        $this->assertInstanceOf(QueryOption::class, $page);
        $this->assertInstanceOf(QueryOption::class, $cursor);

        $this->assertIsArray($chunk->filters);
        $this->assertIsArray($page->filters);
        $this->assertIsArray($cursor->filters);
    }

    // ==================== 组合测试 ====================

    public function testMixedArrayAndJsonInput()
    {
        $option = new QueryOption([
            'filters' => ['status' => 1],
            'orderBy' => '[{"id":"desc"}]'
        ]);

        $this->assertEquals(['status' => 1], $option->filters);
        $this->assertEquals([['id' => 'desc']], $option->orderBy);
    }

    public function testAllOptionTypesCanBeCreated()
    {
        $query = new QueryOption(['filters' => ['id' => 1]]);
        $chunk = new ChunkOption(['take' => 10, 'skip' => 0]);
        $page = new PageOption(['page' => 1, 'take' => 20]);
        $cursor = new CursorOption(['offset' => 0, 'limit' => 30]);

        $this->assertInstanceOf(QueryOption::class, $query);
        $this->assertInstanceOf(QueryOption::class, $chunk);
        $this->assertInstanceOf(QueryOption::class, $page);
        $this->assertInstanceOf(QueryOption::class, $cursor);
    }
}
