<?php

declare(strict_types=1);

namespace QT\GraphQL\Traits;

use QT\GraphQL\Utils;
use RuntimeException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;

trait SqlBuilder
{
    /**
     * 筛选条件对应的操作符
     *
     * @var array
     */
    protected static $defaultOperators = [
        // equal
        'eq' => '=',
        // not equal
        'ne' => '!=',
        // greater than
        'gt' => '>',
        // greater than or equal
        'ge' => '>=',
        // less than
        'lt' => '<',
        // less than or equal
        'le' => '<=',
    ];

    /**
     * 筛选表达式对应的回调(全局通用)
     *
     * @var array<string, callable>
     */
    protected static $globalOperators = [];

    /**
     * 筛选表达式对应的回调(局部使用)
     *
     * @var array<string, callable>
     */
    protected $operators = [];

    /**
     * 最大查询深度
     *
     * @var int
     */
    protected $maxDepth = 5;

    /**
     * 关联字段
     *
     * @var array
     */
    protected $joinTable = [
        // 'table' => ['foreignKey', '=', 'ownerKey'],
    ];

    /**
     * 筛选时需要进行时间戳转换的字段
     *
     * @var array
     */
    protected $timestampFields = ['created_at', 'updated_at'];

    /**
     * 需要特殊构造的条件
     *
     * @var array
     */
    protected $conditionResolvers = [];

    /**
     * 使用索引进行排序的字段,设置时必须带有表名
     * 在有where条件时,排序会强制不使用索引
     * 指定字段后就不会关闭排序索引
     *
     * @var array
     */
    protected $useIndexSortFields = [
        // 'table.column',
    ];

    /**
     * 生成sql
     *
     * @param Builder $query
     * @param array $selection
     * @param array $filters
     * @param array $orderBy
     * @return Builder
     * @throws RuntimeException
     */
    public function buildSql(
        Builder $query,
        array $selection = [],
        array $filters = [],
        array $orderBy = []
    ): Builder {
        $this->buildSelect($query, $selection);
        $this->buildFilter($query, $this->prepareJoin($query, $filters));

        if (empty($orderBy) && $this->model->incrementing) {
            $orderBy[] = [$this->model->getKeyName() => 'desc'];
        }

        foreach ($orderBy as $columns) {
            foreach ($columns as $column => $direction) {
                if (!is_array($direction)) {
                    $this->buildSort($query, $column, $direction);
                    continue;
                }

                $table = $column;
                $item  = $direction;
                // 联表查询结构为, table => [column => direction]
                foreach ($item as $column => $direction) {
                    $this->buildSort($query, $column, $direction, $table);
                }
            }
        }

        return $query;
    }

    /**
     * 预处理联表查询的条件
     *
     * @param Builder $query
     * @param array $input
     * @return array
     */
    protected function prepareJoin(Builder $query, array $input): array
    {
        $tables = array_intersect_key($input, $this->joinTable);

        foreach ($tables as $table => $columns) {
            // users: {id: desc}  => users.id: desc
            // users: {id: {eq: 1}}  => users.id: {eq: q}
            unset($input[$table]);

            foreach ($columns as $column => $val) {
                $input["{$table}.{$column}"] = $val;
            }

            $this->buildJoin($query, $table);
        }

        return $input;
    }

    /**
     * 生成联表查询
     *
     * @param Builder $query
     * @param string $table
     * @return Builder
     */
    public function buildJoin(Builder $query, string $table): Builder
    {
        if (empty($this->joinTable[$table])) {
            return $query;
        }

        // 检查是否手动联表,避免重复关联报错
        if (!empty($query->toBase()->joins)) {
            foreach ($query->toBase()->joins as $join) {
                if ($join->table === $table) {
                    return $query;
                }
            }
        }

        $method   = 'join';
        $relation = array_values($this->joinTable[$table]);

        if (count($relation) > 3) {
            [$first, $operator, $second, $method] = $relation;
        } else {
            [$first, $operator, $second] = $relation;
        }

        $query->{$method}($table, "{$query->getQuery()->from}.{$first}", $operator, "{$table}.{$second}");

        return $query;
    }

    /**
     * 构造字段选择
     *
     * @param Builder $query
     * @param array   $selection
     * @return Builder
     * @throws RuntimeException
     */
    public function buildSelect(Builder $query, array $selection = []): Builder
    {
        if (empty($selection)) {
            // 如果没有选择查询字段,只选中主键(用于计算pageInfo)
            $selection = [$query->getModel()->getKeyName() => true];
        }

        // 关联深度 = 最大深度 - 第一层查询
        $this->selectFieldAndWithTable($query, $selection, $this->maxDepth - 1);

        return $query;
    }

    /**
     * TODO: 多态关联with方案
     * 提前推断type与model的关联,然后把请求的type字段关联到model query上
     * 目前在deferred层完成load,是因为resolve时可以获取runtime type，根据type获取model
     *
     * 选中要查询的字段以及关联表
     *
     * @param Builder|Relation $query
     * @param array            $selection
     * @param int              $depth
     * @throws RuntimeException
     */
    protected function selectFieldAndWithTable(Builder | Relation $query, array $selection, int $depth)
    {
        // 深度达到极限,不在进行关联
        if ($depth === 0) {
            return $query;
        }

        $model = $query->getModel();
        $loads = $query->getEagerLoads();
        // 去除隐藏字段
        foreach ($model->getHidden() as $hidden) {
            unset($selection[$hidden]);
        }
        // 允许在model层设置关联字段
        if (method_exists($model, 'getWithFields')) {
            $selection = array_merge($selection, $model->getWithFields());
        }

        $fields = [];
        foreach ($selection as $field => $val) {
            if (!method_exists($model, $field)) {
                $fields[$model->qualifyColumn($field)] = true;
                continue;
            }

            // 已经设置过的with条件不做替换
            if (isset($loads[$field])) {
                continue;
            }

            if (!is_array($val)) {
                $val = ['*' => true];
            }

            $relation = $model->{$field}();
            $keys     = Utils::getWithKeyName($relation);

            if (empty($keys)) {
                continue;
            }

            [$localKey, $foreignKey] = $keys;
            // 选中当前表字段
            $fields[$model->qualifyColumn($localKey)] = true;

            // 一对一多态关联不做自动关联
            if (empty($foreignKey) || $relation instanceof MorphTo) {
                continue;
            }

            $val[$foreignKey] = true;
            // 选中关联表字段
            $this->withRelation($query, $field, $val, $depth);
        }

        return $query->addSelect(array_keys($fields));
    }

    /**
     * 关联子级
     *
     * @param Builder|Relation $query
     * @param string $relation
     * @param array $fields
     * @param int $depth
     */
    protected function withRelation(Builder|Relation $query, string $relation, array $fields, int $depth)
    {
        $query->with($relation, fn ($query) => $this->selectFieldAndWithTable($query, $fields, $depth - 1));
    }

    /**
     * 生成过滤条件
     *
     * @param Builder $baseQuery
     * @param array   $filters
     * @return Builder
     */
    public function buildFilter(Builder $baseQuery, array $filters): Builder
    {
        if (empty($filters)) {
            return $baseQuery;
        }

        return $baseQuery->where(function (Builder $query) use ($filters) {
            foreach ($filters as $column => $operators) {
                foreach ($operators as $operator => $value) {
                    $condition = $this->resolveCondition($column, $operator, $value);

                    if (count($condition) !== 3) {
                        continue;
                    }

                    [$column, $operator, $value] = array_values($condition);

                    if (in_array($value, ['', null], true)) {
                        continue;
                    }

                    if (!empty($this->operators[$operator])) {
                        $handler = $this->operators[$operator];
                    } elseif (!empty(static::$globalOperators[$operator])) {
                        $handler = static::$globalOperators[$operator];
                    } else {
                        continue;
                    }

                    $this->buildCondition($query, $handler, $column, $value);
                }
            }
        });
    }

    /**
     * 生成具体子sql
     *
     * @param Builder $query
     * @param callable $handler
     * @param string $column
     * @param mixed $value
     * @return void
     */
    protected function buildCondition(Builder $query, callable $handler, string $column, mixed $value)
    {
        // 如果搜索由数据库维护的时间信息，则自动转成数据库TIMESTAMP查找格式
        if (in_array($column, $this->timestampFields)) {
            if (is_numeric($value)) {
                $value = date('Y-m-d H:i:s', $value);
            }

            if (is_array($value) && count($value) >= 2) {
                $value[0] = date('Y-m-d H:i:s', $value[0]);
                $value[1] = date('Y-m-d H:i:s', $value[1]);
            }
        }

        $column = $query->getModel()->qualifyColumn($column);

        call_user_func($handler, $query, $column, $value);
    }

    /**
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return array
     */
    protected function resolveCondition(string $column, string $operator, mixed $value): array
    {
        if (!isset($this->conditionResolvers[$column])) {
            return [$column, $operator, $value];
        }

        // 部分特殊的条件构造,可以重写该方法来处理
        // 比如要无限分级下查询子分类下的全部数据
        // 就可以在此处先拿出全部的子分类id,在进行sql查询
        return call_user_func($this->conditionResolvers[$column], $value, $operator);
    }

    /**
     * 构造排序方式
     *
     * @param Builder $query
     * @param string|null $column
     * @param string $direction
     * @param string|null $table
     * @return Builder
     */
    public function buildSort(
        Builder $query,
        ?string $column = null,
        string $direction = 'desc',
        ?string $table = null
    ): Builder {
        if ($column === null) {
            return $query;
        }

        $table   = $table ?: $this->table;
        $orderBy = "{$table}.{$column}";
        // 排序时支持链表
        if ($table !== $this->table) {
            $query = $this->buildJoin($query, $table);
        }

        if (in_array($orderBy, $this->useIndexSortFields)) {
            return $query->orderBy($orderBy, $direction);
        }

        $columns = $this->getColumns($query->toBase());
        // 筛选条件与排序字段不同时,默认放弃排序索引
        if (count($columns) > 1 || (count($columns) == 1 && empty($columns[$orderBy]))) {
            // order by limit 在部分情况下会覆盖where条件中索引
            // 为了强制使用where上的索引,使用计算公式来关闭sort索引
            // @see http://mysql.taobao.org/monthly/2015/11/10/            
            $orderBy = new Expression("`{$table}`.`{$column}` + 0");
        }

        return $query->orderBy($orderBy, $direction);
    }

    /**
     * 获取sql使用的where字段
     *
     * @param $query
     * @return array
     */
    protected function getColumns(BaseBuilder $query): array
    {
        $columns = [];
        foreach ($query->wheres as $where) {
            if ($where['type'] === 'Nested') {
                $columns = array_merge($columns, $this->getColumns($where['query']));
            } elseif (!empty($where['column'])) {
                $columns[$where['column']] = true;
            }
        }

        return $columns;
    }

    /**
     * 注册filter operator
     *
     * @param string $operator
     * @param callable $handle
     * @return self
     */
    public function registerOperator(string $operator, callable $handle): self
    {
        $this->operators[$operator] = $handle;

        return $this;
    }

    /**
     * 注册column condition resolver
     *
     * @param string $column
     * @param callable $resolver
     * @return self
     */
    public function registerCondition(string $column, callable $resolver): self
    {
        $this->conditionResolvers[$column] = $resolver;

        return $this;
    }

    /**
     * 注册全局filter operator
     *
     * @param string $operator
     * @param callable $handle
     * @return void
     */
    public static function registerGlobalOperator(string $operator, callable $handle)
    {
        static::$globalOperators[$operator] = $handle;
    }

    /**
     * 初始化默认的filter operator
     */
    protected static function registerDefaultOperatorHandle()
    {
        if (!empty(static::$globalOperators)) {
            return;
        }

        foreach (static::$defaultOperators as $name => $operator) {
            $callback = function ($query, $column, $value) use ($operator) {
                $query->where($column, $operator, $value);
            };

            static::registerGlobalOperator($name, $callback);
            static::registerGlobalOperator($operator, $callback);
        }

        static::registerGlobalOperator('in', function ($query, $column, $value) {
            $query->whereIn($column, $value);
        });

        static::registerGlobalOperator('notIn', function ($query, $column, $value) {
            $query->whereNotIn($column, $value);
        });

        static::registerGlobalOperator('between', function ($query, $column, $value) {
            $query->whereBetween($column, $value);
        });

        static::registerGlobalOperator('like', function ($query, $column, $value) {
            $query->where($column, 'like', "%{$value}%");
        });

        static::registerGlobalOperator('leftLike', function ($query, $column, $value) {
            $query->where($column, 'like', "%{$value}");
        });

        static::registerGlobalOperator('rightLike', function ($query, $column, $value) {
            $query->where($column, 'like', "{$value}%");
        });

        static::registerGlobalOperator('isNull', function ($query, $column, $value) {
            if (is_bool($value)) {
                $query->{$value ? 'whereNull' : 'whereNotNull'}($column);
            } else {
                $query->whereNull($column);
            }
        });

        static::registerGlobalOperator('notNull', function ($query, $column, $value) {
            if (is_bool($value)) {
                $query->{$value ? 'whereNotNull' : 'whereNull'}($column);
            } else {
                $query->whereNull($column);
            }
        });
    }
}
