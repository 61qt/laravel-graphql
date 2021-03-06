<?php

declare(strict_types=1);

namespace QT\GraphQL;

use Iterator;
use RuntimeException;
use QT\GraphQL\Contracts\Context;
use Illuminate\Support\Facades\DB;
use QT\GraphQL\Options\PageOption;
use QT\GraphQL\Options\ChunkOption;
use QT\GraphQL\Options\CursorOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

/**
 * Graphql Result Resolver
 *
 * @package QT\GraphQL
 */
class Resolver
{
    use Traits\Validate;
    use Traits\SqlBuilder;
    use Traits\ResolverTriggers;

    /**
     * model 对应的query
     *
     * @var null|Builder
     */
    protected $query = null;

    /**
     * 查询的table
     *
     * @var string
     */
    protected $table;

    /**
     * 页面最大行数
     *
     * @var int
     */
    protected $perPageMax = 500;

    /**
     * Resolver constructor.
     *
     * @param Model $model
     * @param Factory $factory
     */
    public function __construct(protected Model $model, Factory $factory)
    {
        $this->table = $model->getTable();

        $this->boot();

        $this->setValidationFactory($factory);
    }

    /**
     * Bootstrap the resolver
     *
     * @return void
     */
    protected function boot()
    {
        static::registerDefaultOperatorHandle();
    }

    /**
     * @param bool $fresh
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getModelQuery(bool $fresh = false): Builder
    {
        if ($fresh) {
            return $this->model->newQuery();
        }

        if (!empty($this->query)) {
            return $this->query;
        }

        return $this->query = $this->model->newQuery();
    }

    /**
     * 释放当前使用的builder并返回
     *
     * @param bool $fresh
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getFreedModelQuery(): Builder
    {
        $query = $this->getModelQuery();

        $this->query = null;

        return $query;
    }

    /**
     * 获取单条记录
     *
     * @param Context $context
     * @param array $input
     * @param array $selection
     * @return \Illuminate\Database\Eloquent\Model
     * @throws Error
     */
    public function show(Context $context, array $input, array $selection = []): Model
    {
        $id = $this->getKey($input);

        $this->beforeShow($context, $id);

        $model = $this->getBuilder($selection)->findOrFail($id);

        $this->afterShow($model, $selection);

        return $model;
    }

    /**
     * 获取指定范围的数据
     *
     * @param Context $context
     * @param ChunkOption $option
     * @param array $selection
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws Error
     */
    public function chunk(Context $context, ChunkOption $option, array $selection = []): Collection
    {
        $this->beforeList($context, $option);

        $query = $this->getBuilder($selection, $option->filters, $option->orderBy);

        if (!$option->all) {
            $query->skip($option->skip)->take($option->take);
        }

        $models = $query->get();

        $this->afterList($models, $selection);

        return $models;
    }

    /**
     * 返回分页信息
     *
     * @param Context $context
     * @param PageOption $option
     * @param array $selection
     * @return \Illuminate\Contracts\Pagination\Paginator
     * @throws Error
     */
    public function pagination(Context $context, PageOption $option, array $selection = []): Paginator
    {
        $this->beforeList($context, $option);

        $paginator = $this->getBuilder($selection, $option->filters, $option->orderBy)
            ->paginate(min($option->take, $this->perPageMax), ['*'], 'page', $option->page);

        $this->afterList($paginator, $selection);

        return $paginator;
    }

    /**
     * 获取导出数据总量
     *
     * @param Context $context
     * @param CursorOption $option
     * @return int
     * @throws Error
     */
    public function getExportCount(Context $context, CursorOption $option): int
    {
        $this->beforeExport($context, $option, []);

        return $this->getBuilder([], $option->filters)->count();
    }

    /**
     * 导出列表数据
     *
     * @param Context $context
     * @param CursorOption $option
     * @param array $selection
     * @return Iterator
     * @throws Error
     */
    public function export(Context $context, CursorOption $option, array $selection): Iterator
    {
        $this->beforeExport($context, $option, $selection);

        return $this->cursor($option, $selection);
    }

    /**
     * 游标式查询数据集
     *
     * @param CursorOption $option
     * @param array $selection
     * @return Iterator
     * @throws Error
     */
    public function cursor(CursorOption $option, array $selection = []): Iterator
    {
        $offset = $option->offset;
        $query  = $this->getBuilder($selection, $option->filters, $option->orderBy);

        do {
            $models = (clone $query)->forPage(++$offset, $option->limit)->get();

            foreach ($models as $model) {
                yield $model;
            }
        } while ($models->count() === $option->limit);
    }

    /**
     * 生成sql
     *
     * @param array $selection
     * @param array $filters
     * @param array $orderBy
     * @return Builder
     */
    public function getBuilder(array $selection, array $filters = [], array $orderBy = []): Builder
    {
        // 使用释放之后的builder,保证CURD直接不会相互影响
        return $this->buildSql($this->getFreedModelQuery(), $selection, $filters, $orderBy);
    }

    /**
     * 新建记录
     *
     * @param Context $context
     * @param array $input
     * @return Model
     * @throws Error
     */
    public function store(Context $context, array $input = []): Model
    {
        $this->validate($input, $this->rules, $this->messages);

        $input = $this->checkAndFormatInput($input);
        $model = $this->model->newInstance()->fill($input);
        $this->beforeStore($context, $model, $input);

        return DB::transaction(function () use ($model, $input) {
            $model->save();

            $this->buildRelation($model, $input);

            $this->afterStore($model);

            return $model;
        });
    }

    /**
     * 更新一条记录
     *
     * @param Context $context
     * @param array $input
     * @return Model
     * @throws RuntimeException
     */
    public function update(Context $context, array $input = []): Model
    {
        $this->validate($input, $this->rules, $this->messages);

        $input = $this->checkAndFormatInput($input);
        $id    = $this->getKey($input);
        $model = $this->getFreedModelQuery()->findOrFail($id);
        $this->beforeUpdate($context, $model, $input);

        return DB::transaction(function () use ($model, $input) {
            $model->fill($input)->save();

            $this->buildRelation($model, $input);

            $this->afterUpdate($model);

            return $model;
        });
    }

    /**
     * @param array $input
     * @return array
     */
    protected function checkAndFormatInput(array $input = []): array
    {
        return $input;
    }

    /**
     * 构造model关系,在afterUpdate跟afterStore触发
     *
     * @param $model
     * @return mixed
     */
    protected function buildRelation(Model $model, array $input = []): Model
    {
        return $model;
    }

    /**
     * @param Context $context
     * @param array $input
     * @return Model
     * @throws \Exception
     */
    public function destroy(Context $context, array $input = []): Model
    {
        $id    = $this->getKey($input);
        $model = $this->getFreedModelQuery()->findOrFail($id);

        $this->beforeDestroy($context, $model);

        return DB::transaction(function () use ($model) {
            if (!$model->delete()) {
                throw new RuntimeException('删除失败');
            }

            return $this->afterDestroy($model) ?: $model;
        });
    }

    /**
     * @param array $filter
     * @return int
     * @throws \Exception
     */
    public function batchDestroy(Context $context, array $input = []): int
    {
        $filters = [];
        if (!empty($input['filters'])) {
            $filters = $input['filters'];
        }

        $query = $this->buildFilter($this->getFreedModelQuery(), $filters);

        return DB::transaction(function () use ($query) {
            $this->beforeBatchDestroy($query->get());

            return $query->delete();
        });
    }

    /**
     * @param array $input
     * @return Model
     */
    public function find(array $input): Model
    {
        $id    = $this->getKey($input);
        $model = $this->getModelQuery(true)->find($id);

        if ($model === null) {
            $exception = new ModelNotFoundException('数据不存在');

            throw $exception->setModel($this->model, [$id]);
        }

        return $model;
    }

    /**
     * 获取Model的主键.
     *
     * @param array $input
     * @return mixed
     */
    public function getKey(array $input = [])
    {
        $keyName = $this->model->getKeyName();

        if (empty($input[$keyName])) {
            throw new RuntimeException("{$keyName}不能为空");
        }

        return $input[$keyName];
    }

    /**
     * TODO 与 selectFieldAndWithTable 协同,减少selection循环次数
     * 加载动态关系
     *
     * @param Collection $models
     * @param array $selection
     */
    protected function loadRelations($models, array $selection)
    {
        if (empty($selection) || $models->isEmpty()) {
            return;
        }

        $model = $models->first();
        foreach ($selection as $field => $val) {
            if (!method_exists($model, $field)) {
                continue;
            }

            if (!is_array($val)) {
                $val = [];
            }

            $relation = $model->{$field}();
            // 检查是否为动态关联
            if (!$relation instanceof MorphTo && !$relation instanceof MorphOneOrMany) {
                continue;
            }

            // 获取多态关联的对象
            $types = $models
                ->unique($relation->getMorphType())
                ->pluck($relation->getMorphType());

            $relations = [];
            foreach ($types as $type) {
                if (!class_exists($type)) {
                    continue;
                }

                $with  = [];
                $class = new $type();

                foreach ($val as $k => $_) {
                    if (method_exists($class, $k)) {
                        $with[] = $k;
                    }
                }

                $relations[$type] = $with;
            }
            // 加载多态关联数据
            $models->load($field);
            $models->loadMorph($field, $relations);
        }
    }
}
