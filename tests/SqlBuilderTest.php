<?php

namespace QT\Test;

use Mockery;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use QT\GraphQL\Traits\SqlBuilder;

// 测试用的具体类 - 通过构造函数设置属性值
class TestResolverWithInvalidJoin
{
    use SqlBuilder;

    public function __construct()
    {
        // 通过构造函数设置无效配置
        $this->joinTable = [
            'invalid_table' => 'invalid_string_value', // 应该是数组或 Closure
        ];
    }
}

class TestResolverWithValidJoin
{
    use SqlBuilder;

    public function __construct()
    {
        $this->joinTable = [
            'valid_table' => ['foreign_id', '=', 'id'],
        ];
    }
}

class TestResolverWithClosureJoin
{
    use SqlBuilder;

    public function __construct()
    {
        $this->joinTable = [
            'closure_table' => function ($join) {
                $join->on('test_table.id', '=', 'closure_table.test_id');
            },
        ];
    }
}

class TestResolverForOperators
{
    use SqlBuilder;

    public function initOperators(): void
    {
        static::registerDefaultOperatorHandle();
    }

    public function getRegisteredOperators(): array
    {
        return array_keys(static::$globalOperators);
    }
}

class SqlBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    protected function createMockBuilder(): Builder
    {
        $model = new class extends Model {
            protected $table = 'test_table';
            protected $guarded = [];
            public $timestamps = false;
        };

        $model->setConnectionResolver($resolver = Mockery::mock(ConnectionResolverInterface::class));
        $resolver->shouldReceive('connection')->andReturn($connection = Mockery::mock(Connection::class));

        $processor = Mockery::mock(Processor::class);
        $processor->shouldReceive('processSelect')->andReturnUsing(function ($query, $result) {
            return $result;
        });

        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $grammar = new Grammar($connection);

        $connection->shouldReceive('getName')->andReturn('test');
        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $connection->shouldReceive('query')->andReturnUsing(function () use ($connection, $grammar, $processor) {
            return new QueryBuilder($connection, $grammar, $processor);
        });

        return $model->newQuery();
    }

    public function testBuildJoinThrowsExceptionForInvalidConfiguration()
    {
        $resolver = new TestResolverWithInvalidJoin();

        $query = $this->createMockBuilder();
        $joined = [];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid joinTable configuration for table 'invalid_table'");

        $resolver->buildJoin($query, 'invalid_table', $joined);
    }

    public function testBuildJoinAcceptsArrayConfiguration()
    {
        $resolver = new TestResolverWithValidJoin();

        $query = $this->createMockBuilder();
        $joined = [];

        $result = $resolver->buildJoin($query, 'valid_table', $joined);

        $this->assertSame($query, $result);
        $this->assertArrayHasKey('valid_table', $joined);
    }

    public function testBuildJoinAcceptsClosureConfiguration()
    {
        $resolver = new TestResolverWithClosureJoin();

        $query = $this->createMockBuilder();
        $joined = [];

        $result = $resolver->buildJoin($query, 'closure_table', $joined);

        $this->assertSame($query, $result);
        $this->assertArrayHasKey('closure_table', $joined);
    }

    public function testDefaultOperatorsAreRegistered()
    {
        $resolver = new TestResolverForOperators();
        $resolver->initOperators();
        $operators = $resolver->getRegisteredOperators();

        // 验证基本操作符已注册
        $this->assertContains('eq', $operators);
        $this->assertContains('ne', $operators);
        $this->assertContains('gt', $operators);
        $this->assertContains('lt', $operators);
        $this->assertContains('in', $operators);
        $this->assertContains('like', $operators);
        $this->assertContains('between', $operators);
    }
}
