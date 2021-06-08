<?php

namespace QT\GraphQL\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait SqlBuilder
{
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
     * 支持排序的字段
     *
     * @var array
     */
    protected $orderFields = [];

    /**
     * 筛选条件对应的操作符
     *
     * @var array
     */
    protected $operatorsMaps = [
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
     * 查询条件对应的回调函数
     *
     * @var array
     */
    protected $operatorHandles = [];

    /**
     * 筛选时需要进行时间戳转换的字段
     *
     * @var array
     */
    protected $timestampFields = ['created_at', 'updated_at'];

    /**
     * 生成sql
     *
     * @param Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws Error
     */
    public function buildSql(
        Builder $query,
        array $selection = [],
        array $filters = [],
        array $orderBy = []
    ): Builder {
        $this->buildSelect($query, $selection);
        $this->buildFilter($query, $filters);

        foreach ($orderBy as $colunm => $direction) {
            $this->buildSort($query, $colunm, $direction);
        }

        return $query;
    }

    /**
     * 构造字段选择
     *
     * @param Builder $query
     * @param array   $selection
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws Error
     */
    public function buildSelect(Builder $query, array $selection = [])
    {
        if (empty($selection)) {
            // 如果没有选择查询字段,只选中主键(用于计算pageInfo)
            $selection = [$query->getModel()->getKeyName() => true];
        }

        return $this->selectFieldAndWithTable($query, $selection, $this->maxDepth);
    }

    /**
     * @param Builder|Relation $query
     * @param array            $selection
     * @param bool             $detail
     * @param int              $depth
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws Error
     */
    protected function selectFieldAndWithTable(Builder|Relation $query, array $selection, int $depth): Builder|Relation
    {
        // 深度达到极限,不在进行关联
        if ($depth === 0) {
            return $query;
        }

        $model = $query->getModel();
        $table = $model->getTable();

        // 去除隐藏字段
        $selection = array_diff_key(
            array_merge($selection, [$model->getKeyName() => true]), 
            array_flip($model->getHidden())
        );

        $fields = [];
        foreach ($selection as $field => $val) {
            if (!method_exists($model, $field)) {
                $fields[] = $this->formatField($field, $table);
                continue;
            }

            if (!is_array($val)) {
                $query->with($field);
                continue;
            }

            // 只取出选中字段
            $query->with([$field => function ($query) use ($val, $depth) {
                $this->selectFieldAndWithTable($query, $val, $depth - 1);
            }]);
        }

        return $query->select($fields);
    }

    /**
     * 生成过滤条件
     *
     * @param Builder $baseQuery
     * @param array   $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildFilter(Builder $baseQuery, $filters)
    {
        if (empty($filters)) {
            return $baseQuery;
        }

        return $baseQuery->where(function (Builder $query) use ($baseQuery, $filters) {
            foreach ($filters as $column => $operators) {
                foreach ($operators as $operator => $value) {
                    if (
                        in_array($value, ['', null], true) ||
                        empty($this->operatorHandles[$operator])
                    ) {
                        continue;
                    }

                    // 检查是否需要进行join查询
                    if (false !== strpos($column, '.')) {
                        // where回调传入的query对象为新建的Builder对象
                        // 在其上进行join表无法生效,故使用原Builder对象进行join表
                        $this->buildJoin($baseQuery, $column);
                    }

                    $this->buildCondition($query, $column, $operator, $value);
                }
            }
        });
    }

    /**
     * 生成具体子sql
     *
     * @param Builder $query
     * @param string $column
     * @param string $operator
     * @param mixed $value
     */
    protected function buildCondition(
        Builder $query, 
        string $column, 
        string $operator, 
        mixed $value
    ) {
        [$column, $operator, $value] = $this->resolveCondition(
            $column, $operator, $value
        );

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

        $column = $this->formatField($column, $query->getModel()->getTable());

        call_user_func(
            $this->operatorHandles[$operator], $query, $column, $value
        );
    }

    /**
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return array
     */
    protected function resolveCondition(string $column, string $operator, mixed $value): array
    {
        // TODO 改为注册回调的方式
        // 部分特殊的条件构造,可以重写该方法来处理
        // 比如要无限分级下查询子分类下的全部数据
        // 就可以在此处先拿出全部的子分类id,在进行sql查询
        return [$column, $operator, $value];
    }

    /**
     * 根据filter进行join
     *
     * @param $query
     * @param $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildJoin(Builder $query, $column): Builder
    {
        list($table) = explode('.', $column, 2);

        if (isset($this->joinTable[$table])) {
            $method   = 'join';
            $relation = array_values($this->joinTable[$table]);

            if (count($relation) > 3) {
                [$first, $operator, $second, $method] = $relation;
            } else {
                [$first, $operator, $second] = $relation;
            }

            $query->{$method}($table, "{$query->getQuery()->from}.{$first}", $operator, "{$table}.{$second}");
            // 只关联一次
            unset($this->joinTable[$table]);
        }

        return $query;
    }

    /**
     * 构造排序方式
     *
     * @param Builder    $query
     * @param null       $orderBy
     * @param string     $direction
     * @param Collection $filters
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws Error
     */
    public function buildSort(
        Builder $query,
        string $column = null,
        string $direction = 'desc'
    ): Builder {
        if (!in_array($column, $this->orderFields)) {
            $column = $this->model->getKeyName();
        }

        $table = $this->getTable();
        if (strpos($column, '.') !== false) {
            list($table, $column) = explode('.', $column, 2);
        }

        $orderBy = "{$table}.{$column}";
        // 排序时支持链表
        if ($table !== $this->getTable()) {
            $query = $this->buildJoin($query, $orderBy);
        }

        $columns = $this->getColumns($query);
        // 筛选条件与排序字段不同时,默认放弃排序索引
        if (count($columns) > 1 || (count($columns) == 1 && empty($columns[$orderBy]))) {
            // order by limit 在部分情况下会覆盖where条件中索引
            // 为了强制使用where上的索引,使用计算公式来关闭sort索引
            // @see http://mysql.taobao.org/monthly/2015/11/10/
            $orderBy = DB::raw("`{$table}`.`{$column}` + 0");
        }

        return $query->orderBy($orderBy, $direction);
    }

    /**
     * 获取sql使用的where字段
     *
     * @param $query
     * @return array
     */
    protected function getColumns(Builder $query): array
    {
        $columns = [];
        foreach ($query->toBase()->wheres as $where) {
            if ($where['type'] === 'Nested') {
                $columns = array_merge($columns, $this->getColumns($where['query']));
            } elseif (!empty($where['column'])) {
                $columns[$where['column']] = true;
            }
        }

        return $columns;
    }

    /**
     * @param string      $field
     * @param null|string $table
     * @return string
     */
    protected function formatField(string $field, ?string $table = null): string
    {
        if (strpos($field, '.')) {
            return $field;
        }

        $table = $table ?: $this->table;

        return "{$table}.{$field}";
    }

    /**
     * 注册filter operator
     *
     * @param string $operator
     * @param callable $handle
     * @return static
     */
    public function registerOperatorHandle(string $operator, callable $handle): static
    {
        $this->operatorHandles[$operator] = $handle;

        return $this;
    }

    /**
     * 初始化默认的filter operator
     */
    protected function registorDefaultOperatorHandle()
    {
        foreach ($this->operatorsMaps as $name => $operator) {
            $callback = function ($query, $column, $value) use ($operator) {
                $query->where($column, $operator, $value);
            };

            $this->registerOperatorHandle($name, $callback);
        }

        $this->registerOperatorHandle('in', function ($query, $column, $value) {
            $query->whereIn($column, $value);
        });

        $this->registerOperatorHandle('notIn', function ($query, $column, $value) {
            $query->whereNotIn($column, $value);
        });

        $this->registerOperatorHandle('between', function ($query, $column, $value) {
            $query->whereBetween($column, $value);
        });

        $this->registerOperatorHandle('like', function ($query, $column, $value) {
            $query->where($column, 'like', "%{$value}%");
        });

        $this->registerOperatorHandle('leftLike', function ($query, $column, $value) {
            $query->where($column, 'like', "%{$value}");
        });

        $this->registerOperatorHandle('rightLike', function ($query, $column, $value) {
            $query->where($column, 'like', "{$value}%");
        });

        $this->registerOperatorHandle('isNull', function ($query, $column, $value) {
            if (is_bool($value)) {
                $query->{$value ? 'whereNull' : 'whereNotNull'}($column);
            } else {
                $query->whereNull($column);
            }
        });

        $this->registerOperatorHandle('notNull', function ($query, $column, $value) {
            if (is_bool($value)) {
                $query->{$value ? 'whereNotNull' : 'whereNull'}($column);
            } else {
                $query->whereNull($column);
            }
        });
    }
}
