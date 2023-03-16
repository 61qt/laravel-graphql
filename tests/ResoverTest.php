<?php

namespace QT\Test;

use Mockery;
use Carbon\Carbon;
use RuntimeException;
use QT\GraphQL\Resolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use QT\GraphQL\Contracts\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Factory;
use QT\GraphQL\Options\PageOption;
use QT\GraphQL\Options\ChunkOption;
use Illuminate\Database\Connection;
use Illuminate\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());

        DB::shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
        Carbon::setTestNow(null);

        Model::unsetConnectionResolver();
        Carbon::resetToStringFormat();
    }

    protected function createResolver($model): Resolver
    {
        return new Resolver($model, new Factory(new Translator(new ArrayLoader, 'cn')));
    }

    protected function addMockConnection($model)
    {
        $model->setConnectionResolver($resolver = Mockery::mock(ConnectionResolverInterface::class));
        $resolver->shouldReceive('connection')->andReturn($connection = Mockery::mock(Connection::class));

        $grammar   = new Grammar;
        $processor = Mockery::mock(Processor::class);
        $processor->shouldReceive('processSelect')->andReturnUsing(function ($query, $result) {
            return $result;
        });

        $connection->shouldReceive('getName')->andReturn('name');
        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $connection->shouldReceive('query')->andReturnUsing(function () use ($connection, $grammar, $processor) {
            return new QueryBuilder($connection, $grammar, $processor);
        });
    }

    public function testGetModelQuery()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);

        $resolver = $this->createResolver($model);

        $query = $resolver->getModelQuery();
        $this->assertSame($query, $resolver->getModelQuery());
        $this->assertNotSame($query, $resolver->getModelQuery(true));
    }

    public function testShow()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $model->getConnection()->shouldReceive('select')->once()->andReturnUsing(function ($sql) {
            $this->assertSame('select "table"."id", "table"."name" from "table" where "table"."id" = ? order by "table"."id" desc limit 1', $sql);

            return [['id' => 999, 'name' => 'foo']];
        });

        $result = $this->createResolver($model)->show(new GraphQLContext, [$model->getKeyName() => 999], ['id' => true, 'name' => true]);

        $this->assertSame(['id' => 999, 'name' => 'foo'], $result->toArray());
    }

    public function testShowShouldThrowExceptionIfModelNotFound()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $model->getConnection()->shouldReceive('select');

        $this->expectException(ModelNotFoundException::class);

        $this->createResolver($model)->show(new GraphQLContext, [$model->getKeyName() => 1], ['id' => true]);
    }

    public function testChunk()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $model->getConnection()->shouldReceive('select')->once()->andReturnUsing(function ($sql) {
            $this->assertSame('select "table"."id", "table"."name" from "table" where ("table"."id" in (?, ?)) order by "table"."id" desc limit 100 offset 100', $sql);

            return [['id' => 999, 'name' => 'foo']];
        });

        $option = new ChunkOption(['filters' => ['id' => ['in' => [1, 2]]], 'orderBy' => [['id' => 'desc']], 'take' => 100, 'skip' => 100]);
        $result = $this->createResolver($model)->chunk(new GraphQLContext, $option, ['id' => true, 'name' => true]);

        $this->assertSame([['id' => 999, 'name' => 'foo']], $result->toArray());
    }

    public function testGetAllChunk()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $model->getConnection()->shouldReceive('select')->once()->andReturnUsing(function ($sql, $values) {
            $this->assertSame('select "table"."id", "table"."name" from "table" where ("table"."id" in (?, ?)) order by "table"."id" desc', $sql);

            return [['id' => 999, 'name' => 'foo']];
        });

        $option = new ChunkOption(['filters' => ['id' => ['in' => [1, 2]]], 'orderBy' => [['id' => 'desc']], 'all' => true]);
        $result = $this->createResolver($model)->chunk(new GraphQLContext, $option, ['id' => true, 'name' => true]);

        $this->assertSame([['id' => 999, 'name' => 'foo']], $result->toArray());
    }

    public function testPagination()
    {
        $times = 1;
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $model->getConnection()->shouldReceive('select')->times(2)->andReturnUsing(function ($sql) use (&$times) {
            if ($times++ === 1) {
                $this->assertSame('select count(*) as aggregate from "table" where ("table"."id" in (?, ?))', $sql);

                return [['aggregate' => 1]];
            } else {
                $this->assertSame('select "table"."id", "table"."name" from "table" where ("table"."id" in (?, ?)) order by "table"."id" desc limit 100 offset 100', $sql);

                return [['id' => 999, 'name' => 'foo']];
            }
        });

        $option    = new PageOption(['filters' => ['id' => ['in' => [1, 2]]], 'orderBy' => [['id' => 'desc']], 'take' => 100, 'page' => 2]);
        $paginator = $this->createResolver($model)->pagination(new GraphQLContext, $option, ['id' => true, 'name' => true]);
        $items     = array_map(function ($item) {return $item instanceof Arrayable ? $item->toArray() : $item;}, $paginator->items());

        $this->assertSame([['id' => 999, 'name' => 'foo']], $items);
    }

    public function testStore()
    {
        $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newInstance'])->getMock();
        $model->expects($this->once())->method('newInstance')->willReturnCallback(function () {
            $query = Mockery::mock(Builder::class);
            $query->shouldReceive('getConnection')->once();
            $query->shouldReceive('insertGetId')->once()->with(['name' => 'foo'], 'id')->andReturn(1);

            $model = $this->getMockBuilder(EloquentModelStub::class)->onlyMethods(['newModelQuery'])->getMock();
            $model->expects($this->once())->method('newModelQuery')->willReturn($query);

            return $model;
        });

        $model = $this->createResolver($model)->store(new GraphQLContext, ['name' => 'foo']);

        $this->assertTrue($model->exists);
        $this->assertSame(1, $model->id);
        $this->assertSame('foo', $model->name);
    }

    public function testUpdate()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $connection = $model->getConnection();
        $connection->shouldReceive('select')->andReturn(['id' => 1, 'name' => 'foo']);
        $connection->shouldReceive('update')->andReturn(1);

        $model = $this->createResolver($model)->update(new GraphQLContext, ['id' => 1, 'name' => 'bar']);

        $this->assertSame('bar', $model->name);
    }

    public function testUpdateShouldThrowExceptionIfKeyIsEmpty()
    {
        $model = new EloquentModelStub;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("{$model->getKeyName()}不能为空");

        $this->createResolver($model)->update(new GraphQLContext, ['_' . $model->getKeyName() => 1]);
    }

    public function testUpdateShouldThrowExceptionIfNotFound()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $model->getConnection()->shouldReceive('select');

        $this->expectException(ModelNotFoundException::class);

        $this->createResolver($model)->update(new GraphQLContext, ['id' => 1, 'name' => 'bar']);
    }

    public function testDestroy()
    {
        $model = new EloquentModelStub;
        $this->addMockConnection($model);
        $connection = $model->getConnection();
        $connection->shouldReceive('delete')->andReturn(1);
        $connection->shouldReceive('select')->andReturn(['id' => 1, 'name' => 'foo']);

        $model = $this->createResolver($model)->destroy(new GraphQLContext, ['id' => 1]);

        $this->assertSame(null, $model->id);
        $this->assertFalse($model->exists);
    }

    public function testGetKeyReturnsKeyValue()
    {
        $model    = new EloquentModelStub;
        $keyName  = $model->getKeyName();
        $resolver = $this->createResolver($model);

        $value = 1;
        $this->assertSame($value, $resolver->getKey([$keyName => $value]));
    }

    public function testGetKeyShouldThrowExceptionIfKeyIsEmpty()
    {
        $model    = new EloquentModelStub;
        $keyName  = $model->getKeyName();
        $resolver = $this->createResolver($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("{$keyName}不能为空");

        $resolver->getKey(['_' . $keyName => 1]);
    }
}

class EloquentModelStub extends Model
{
    protected $table      = 'table';
    protected $primaryKey = 'id';
    protected $guarded    = [];
    public $timestamps    = false;
}

class GraphQLContext implements Context
{
    protected $data = [];

    public function getRequest(): Request
    {
        return new Request;
    }

    public function getResponse(): Response
    {
        return new Response;
    }

    public function setValue(string $key, mixed $value)
    {
        $this->data[$key] = $value;
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return null;
    }
}
