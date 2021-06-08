<?php

namespace QT\GraphQL;

use RuntimeException;
use Illuminate\Support\Arr;
use QT\GraphQL\Contracts\Context;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use QT\GraphQL\Options\ListOption;
use QT\GraphQL\Options\PageOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
     */
    public function __construct(protected Model $model)
    {
        $this->table = $this->getModelQuery()->getModel()->getTable();

        $this->registorDefaultOperatorHandle();
    }

    /**
     * @param bool $fresh
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getModelQuery(bool $fresh = false): Builder
    {
        if ($fresh) {
            return $this->model->query();
        }

        if (!empty($this->query)) {
            return $this->query;
        }

        return $this->query = $this->model->query();
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

        $query = $this->buildSelect(
            $this->getModelQuery(), $selection, true
        );

        $model = $query->findOrFail($id);

        $this->afterShow($model);

        return $model;
    }

    /**
     * 获取指定范围的数据
     *
     * @param Context $context
     * @param ListOption $option
     * @param array $selection
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws Error
     */
    public function take(Context $context, ListOption $option, array $selection = []): Collection
    {
        $this->beforeList($context);

        $query = $this->generateSql($selection, $option->filters, $option->orderBy);

        if (!$option->all) {
            $query->skip($option->skip)->take($option->take);
        }

        $models = $query->get();

        $this->afterList($models);

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
        $this->beforeList($context);

        $results = $this->generateSql($selection, $option->filters, $option->orderBy)
            ->paginate(min($option->take, $this->perPageMax), ['*'], 'page', $option->page);

        $this->afterList($results);

        return $results;
    }

    /**
     * 生成sql
     *
     * @param array $selection
     * @param array $filters
     * @param array $orderBy
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function generateSql(array $selection, array $filters, array $orderBy): Builder
    {
        return $this->buildSql(
            $this->getModelQuery(), $selection, $filters, $orderBy
        );
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
        $id = $this->getKey($input);

        $this->beforeUpdate($context, $id);

        $input = $this->checkAndFormatInput($input);

        if ($input instanceof Collection) {
            // 保证输入类型一定为数组
            $input = $input->toArray();
        }

        $this->validate($input, $this->rules, $this->messages);

        $model = $this->getModelQuery()
            ->findOrFail($id)
            ->fill($input);

        return DB::transaction(function () use ($model) {
            $model->save();

            $this->buildRelation($model);

            $this->afterUpdate($model);

            return $model;
        });
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
        $this->beforeStore($context);

        $input = $this->checkAndFormatInput($input);

        if ($input instanceof Collection) {
            // 保证输入类型一定为数组
            $input = $input->toArray();
        }

        $this->validate($input, $this->rules, $this->messages);

        return DB::transaction(function () use ($input) {
            $model = $this->model->create($input);

            $this->buildRelation($model);

            $this->afterStore($model);

            return $model;
        });
    }

    /**
     * @param array $input
     * @return array|Collection
     */
    protected function checkAndFormatInput(array $input = []): array | Collection
    {
        return $input;
    }

    /**
     * 构造model关系,在afterUpdate跟afterStore触发
     *
     * @param $model
     * @return mixed
     */
    protected function buildRelation(Model $model): Model
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
        $id = $this->getKey($input);

        $this->beforeDestroy($context, $id);

        return DB::transaction(function () use ($id) {
            $model = $this->getModelQuery()->findOrFail($id);

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

        $query = $this->buildFilter($this->getModelQuery(true), $filters);

        return DB::transaction(function () use ($query) {
            $this->beforeBatchDestroy($query->get());

            return $query->delete();
        });
    }

    /**
     * @param array|Collection $input
     * @return Model
     */
    public function find(array | Collection $input): Model
    {
        $model = $this->getModelQuery(true)->find($this->getKey($input));

        if ($model === null) {
            throw new RuntimeException('数据不存在');
        }

        return $model;
    }

    /**
     * 获取Model的主键.
     *
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
     * TODO 多态查询优化
     * 加载动态关系,morphTo关系
     *
     * @param $models
     * @param $selection
     */
    protected function loadRelations($models, $selection)
    {
        if (empty($selection)) {
            return;
        }

        if (!$model = $models->first()) {
            return;
        }

        foreach ($selection as $field => $val) {
            if (!method_exists($model, $field) || !is_array($val)) {
                continue;
            }

            if (!is_array($val)) {
                continue;
            }

            $relation = $model->{$field}();
            // 检查是否为动态关联
            if ($relation instanceof MorphTo) {
                // 获取动态关联的对象
                $types = $models
                    ->unique($relation->getMorphType())
                    ->pluck($relation->getMorphType());

                $relations = [];
                foreach ($types as $type) {
                    $with  = [];
                    $class = new $type;

                    foreach ($val as $k => $v) {
                        if (method_exists($class, $k)) {
                            $with[] = $k;
                        }
                    }

                    $relations[$type] = $with;
                }

                $this->loadMorph($models, $field, $relations);
            }
        }
    }

    // TODO 多态查询优化
    protected function loadMorph($models, $relation, $relations)
    {
        $models->pluck($relation)
            ->groupBy(function ($model) {
                return empty($model) ? null : get_class($model);
            })
            ->filter(function ($models, $className) use ($relations) {
                return Arr::has($relations, $className);
            })
            ->each(function ($models, $className) use ($relations) {
                $className::with($relations[$className])
                    ->eagerLoadRelations($models->all());
            });
    }
}
