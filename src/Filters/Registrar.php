<?php

declare(strict_types=1);

namespace QT\GraphQL\Filters;

use Illuminate\Support\Arr;
use QT\GraphQL\GraphQLManager;
use GraphQL\Type\Definition\Type;
use QT\GraphQL\Definition\NilType;
use QT\GraphQL\Definition\FilterType;
use QT\GraphQL\Definition\Type as GlobalType;

/**
 * 筛选类型注册器
 *
 * @package QT\GraphQL\Filters
 */
class Registrar
{
    /**
     * 筛选条件对应的操作符
     *
     * @var array
     */
    protected static $operatorsMaps = [
        // equal
        '=' => 'eq',
        // not equal
        '!=' => 'ne',
        // greater than
        '>' => 'gt',
        // greater than or equal
        '>=' => 'ge',
        // less than
        '<' => 'lt',
        // less than or equal
        '<=' => 'le',
    ];

    /**
     * 冗余可用的筛选条件,在实例化时生成
     *
     * @var array
     */
    public $filters = [];

    /**
     * 允许筛选的字段
     *
     * @var array
     */
    protected $fields = [];

    /**
     * @param string $name
     * @param GraphQLManager $manager
     */
    public function __construct(
        protected string $name,
        protected GraphQLManager $manager
    ) {
    }

    /**
     * 设置一组int类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return self
     */
    public function int(string $name, array $operators): self
    {
        return $this->set($name, Type::int(), $operators);
    }

    /**
     * 设置一组string类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return self
     */
    public function string(string $name, array $operators): self
    {
        return $this->set($name, Type::string(), $operators);
    }

    /**
     * 设置一组bigint类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return self
     */
    public function bigint(string $name, array $operators): self
    {
        return $this->set($name, GlobalType::bigint(), $operators);
    }

    /**
     * 设置筛选条件
     *
     * @param string $name
     * @param Type $type
     * @param array $operators
     * @return self
     */
    public function set(string $name, Type $type, array $operators): self
    {
        $this->filters[$name] = [$type, $operators];

        Arr::set($this->fields, $name, true);

        return $this;
    }

    /**
     * 生成查询字段
     *
     * @param array $fields
     * @param string|null $table
     * @return array
     */
    public function buildFields(array $fields, string $table = null): array
    {
        $results = [];
        foreach ($fields as $field => $child) {
            if (is_array($child)) {
                $results[$field] = $this->createJoin($field, $child);
                continue;
            }

            if ($table === null) {
                [$type, $operators] = $this->filters[$field];
            } else {
                [$type, $operators] = $this->filters["{$table}.{$field}"];
            }

            $results[$field] = $this->create("{$table}_{$field}", $type, $operators);
        }

        return $results;
    }

    /**
     * 创建联表查询的指定类型的筛选条件
     *
     * @param string $table
     * @param array $fields
     * @return Type
     */
    protected function createJoin(string $table, array $fields): Type
    {
        return $this->manager->setType(new FilterType([
            'fields' => $this->buildFields($fields, $table),
            'name'   => $this->formatFilterName($table),
        ]));
    }

    /**
     * 创建一组指定类型的筛选条件
     *
     * @param string $name
     * @param Type $type
     * @param array $operators
     * @return Type
     */
    protected function create(string $name, Type $type, array $operators): Type
    {
        $fields = [];
        foreach ($operators as $operator) {
            if (isset(static::$operatorsMaps[$operator])) {
                $operator = static::$operatorsMaps[$operator];
            }

            $fields[$operator] = Factory::{$operator}($name, $type);
        }

        return $this->manager->setType(new FilterType([
            'fields' => $fields,
            'name'   => $this->formatFilterName($name),
        ]));
    }

    /**
     * 获取filter input type
     *
     * @return FilterType|NilType
     */
    public function getFilterInput(): FilterType | NilType
    {
        if (empty($this->filters)) {
            return GlobalType::nil();
        }

        return new FilterType([
            'name'   => "{$this->name}Filters",
            'fields' => fn () => $this->buildFields($this->fields),
        ]);
    }

    /**
     * 生成filter的名称
     *
     * @param string $name
     * @return string
     */
    protected function formatFilterName(string $name): string
    {
        return "{$this->name}_{$name}";
    }
}
