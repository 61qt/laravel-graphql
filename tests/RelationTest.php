<?php

namespace QT\Test;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use QT\GraphQL\Relations\HasMany;
use QT\GraphQL\Relations\HasOne;

// 测试用的具体 Model 类
class TestParentModel extends Model
{
    protected $table = 'parent_table';
    protected $guarded = [];
    public $timestamps = false;
}

class TestRelatedModel extends Model
{
    protected $table = 'related_table';
    protected $guarded = [];
    public $timestamps = false;
}

class RelationTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    protected function setupMockConnection(Model $model): void
    {
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
    }

    public function testHasOneOrManyConstructorInitializesExtraKeysAfterParent()
    {
        // 测试构造函数在父类初始化之后设置 extraKeys
        $parent = new TestParentModel();
        $parent->id = 1;
        $parent->custom_key = 'test_value';

        $related = new TestRelatedModel();
        $this->setupMockConnection($related);

        $extraKeys = ['custom_key' => 'parent_custom_key'];
        $query = $related->newQuery();

        // 创建 HasMany 实例（继承自 HasOneOrMany）
        $relation = new HasMany($query, $parent, 'parent_id', 'id', $extraKeys);

        // 验证 extraKeys 已正确设置
        $this->assertEquals($extraKeys, $relation->getExtraKeyNames());
    }

    public function testHasManyGetResultsQualifiesForeignKeyColumn()
    {
        // 测试 HasMany::getResults 使用 qualifyColumn 限定外键列名
        $parent = new TestParentModel();
        $parent->id = 1;
        $parent->custom_key = 'test_value';

        $related = new TestRelatedModel();
        $this->setupMockConnection($related);

        $extraKeys = ['custom_key' => 'parent_custom_key'];
        $query = $related->newQuery();

        // 模拟查询执行
        $query->getConnection()->shouldReceive('select')
            ->once()
            ->andReturnUsing(function ($sql, $bindings) {
                // 验证 SQL 中包含限定列名 "related_table"."parent_custom_key"
                $this->assertStringContainsString('"related_table"."parent_custom_key"', $sql);
                return [];
            });

        $relation = new HasMany($query, $parent, 'parent_id', 'id', $extraKeys);
        $results = $relation->getResults();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
    }

    public function testHasOneGetResultsQualifiesForeignKeyColumn()
    {
        // 测试 HasOne::getResults 使用 qualifyColumn 限定外键列名
        $parent = new TestParentModel();
        $parent->id = 1;
        $parent->custom_key = 'test_value';

        $related = new TestRelatedModel();
        $this->setupMockConnection($related);

        $extraKeys = ['custom_key' => 'parent_custom_key'];
        $query = $related->newQuery();

        // 模拟查询执行
        $query->getConnection()->shouldReceive('select')
            ->once()
            ->andReturnUsing(function ($sql, $bindings) {
                // 验证 SQL 中包含限定列名 "related_table"."parent_custom_key"
                $this->assertStringContainsString('"related_table"."parent_custom_key"', $sql);
                return [];
            });

        $relation = new HasOne($query, $parent, 'parent_id', 'id', $extraKeys);
        $results = $relation->getResults();

        // HasOne 在没有结果时返回 null 或默认值
        $this->assertNull($results);
    }

    public function testHasManyWithMultipleExtraKeys()
    {
        // 测试多个 extraKeys 的情况
        $parent = new TestParentModel();
        $parent->id = 1;
        $parent->key1 = 'value1';
        $parent->key2 = 'value2';

        $related = new TestRelatedModel();
        $this->setupMockConnection($related);

        $extraKeys = [
            'key1' => 'foreign_key1',
            'key2' => 'foreign_key2',
        ];

        $query = $related->newQuery();

        // 模拟查询执行
        $query->getConnection()->shouldReceive('select')
            ->once()
            ->andReturnUsing(function ($sql, $bindings) {
                // 验证 SQL 中包含所有的外键列
                $this->assertStringContainsString('"related_table"."foreign_key1"', $sql);
                $this->assertStringContainsString('"related_table"."foreign_key2"', $sql);
                return [];
            });

        $relation = new HasMany($query, $parent, 'parent_id', 'id', $extraKeys);
        $results = $relation->getResults();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
    }

    public function testHasManyGetResultsReturnsEmptyCollectionWhenParentKeyIsNull()
    {
        // 测试当父键为 null 时返回空集合
        $parent = new TestParentModel();
        $parent->id = null; // 父键为 null

        $related = new TestRelatedModel();
        $this->setupMockConnection($related);

        $query = $related->newQuery();

        $relation = new HasMany($query, $parent, 'parent_id', 'id', []);
        $results = $relation->getResults();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $results);
        $this->assertTrue($results->isEmpty());
    }
}
