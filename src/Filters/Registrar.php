<?php

declare (strict_types = 1);

namespace QT\GraphQL\Filters;

use QT\GraphQL\GraphQLManager;
use GraphQL\Type\Definition\Type;
use QT\GraphQL\Definition\NilType;
use QT\GraphQL\Definition\ModelType;
use QT\GraphQL\Definition\Type as ExtraType;
use GraphQL\Type\Definition\InputObjectType;

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
        '='  => 'eq',
        // not equal
        '!=' => 'ne',
        // greater than
        '>'  => 'gt',
        // greater than or equal
        '>=' => 'ge',
        // less than
        '<'  => 'lt',
        // less than or equal
        '<=' => 'le',
    ];

    /**
     * 允许筛选的字段
     *
     * @var array
     */
    protected $filters = [];

    /**
     * @param ModelType $type
     * @param GraphQLManager $manager
     */
    public function __construct(
        protected ModelType $modelType,
        protected GraphQLManager $manager
    ) {
    }

    /**
     * 创建一组int类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return self
     */
    public function int(string $name, array $operators): self
    {
        return $this->create($name, Type::int(), $operators);
    }

    /**
     * 创建一组string类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return self
     */
    public function string(string $name, array $operators): self
    {
        return $this->create($name, Type::string(), $operators);
    }

    /**
     * 创建一组指定类型的筛选条件
     *
     * @param string $name
     * @param Type $type
     * @param array $operators
     * @return self
     */
    public function create(string $name, Type $type, array $operators): self
    {
        $fields = [];
        foreach ($operators as $operator) {
            if (isset(static::$operatorsMaps[$operator])) {
                $operator = static::$operatorsMaps[$operator];
            }
    
            $fields[$operator] = Factory::{$operator}($name, $type);
        }

        $filter = new InputObjectType([
            'fields' => $fields,
            'name'   => $this->formatFilterName($name, $type),
        ]);

        $this->filters[$name] = $this->manager->setType($filter);

        return $this;
    }

    /**
     * 获取filter input type
     *
     * @return InputObjectType|NilType
     */
    public function getFilterInput(): InputObjectType | NilType
    {
        if (empty($this->filters)) {
            return ExtraType::nil();
        }

        return new InputObjectType([
            'name'   => "{$this->modelType->name}Filters",
            'fields' => $this->filters,
        ]);
    }

    /**
     * 创建一组string类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return string
     */
    protected function formatFilterName(string $name, Type $type): string
    {
        return "{$this->modelType->name}_{$name}_{$type->name}_filter";
    }
}
