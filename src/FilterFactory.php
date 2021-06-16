<?php

declare (strict_types = 1);

namespace QT\GraphQL;

use QT\GraphQL\Definition\Type;
use QT\GraphQL\Definition\ModelType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type as BaseType;

/**
 * FilterFactory
 *
 * @package QT\GraphQL
 */
class FilterFactory
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
     * @param ModelType $type
     */
    public function __construct(protected ModelType $type)
    {
    }

    /**
     * 创建一组int类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return InputObjectType
     */
    public function int(string $name, array $operators): InputObjectType
    {
        return $this->create($name, Type::int(), $operators);
    }

    /**
     * 创建一组string类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return InputObjectType
     */
    public function string(string $name, array $operators): InputObjectType
    {
        return $this->create($name, Type::string(), $operators);
    }

    /**
     * 创建一组指定类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return InputObjectType
     */
    public function create($name, $type, $operators): InputObjectType
    {
        $fields = [];
        $name   = "{$this->type->name}_{$name}_{$type->name}_filter";
        foreach ($operators as $operator) {
            if (isset(static::$operatorsMaps[$operator])) {
                $operator = static::$operatorsMaps[$operator];
            }

            if (method_exists($this, $operator)) {
                $fields[$operator] = $this->{$operator}($name, $type);
            }
        }

        return new InputObjectType(compact('name', 'fields'));
    }

    /**
     * @return array
     */
    public function eq(string $name, BaseType $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 等于",
        ];
    }

    /**
     * @return array
     */
    public function ne(string $name, BaseType $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 不等于",
        ];
    }

    /**
     * @return array
     */
    public function gt(string $name, BaseType $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 大于",
        ];
    }

    /**
     * @return array
     */
    public function ge(string $name, BaseType $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 大于等于",
        ];
    }

    /**
     * @return array
     */
    public function lt(string $name, BaseType $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 小于",
        ];
    }

    /**
     * @return array
     */
    public function le(string $name, BaseType $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 小于等于",
        ];
    }

    /**
     * @return array
     */
    public function in(string $name, BaseType $type)
    {
        return [
            'type'        => Type::listOf($type),
            'description' => "{$name} 交集",
        ];
    }

    /**
     * @return array
     */
    public function notIn(string $name, BaseType $type)
    {
        return [
            'type'        => Type::listOf($type),
            'description' => "{$name} 差集",
        ];
    }

    /**
     * @return array
     */
    public function between(string $name, BaseType $type)
    {
        return [
            'type'        => Type::listOf($type),
            'description' => "{$name} 差集",
        ];
    }

    /**
     * @return array
     */
    public function like(string $name, BaseType $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 模糊查询",
        ];
    }

    /**
     * @return array
     */
    public function leftLike(string $name, BaseType $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 向左模糊查询",
        ];
    }

    /**
     * @return array
     */
    public function rightLike(string $name, BaseType $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 向右模糊查询",
        ];
    }

    /**
     * @return array
     */
    public function isNull(string $name, BaseType $type)
    {
        return [
            'type'        => Type::boolean(),
            'description' => "{$name} 为空",
        ];
    }

    /**
     * @return array
     */
    public function notNull(string $name, BaseType $type)
    {
        return [
            'type'        => Type::boolean(),
            'description' => "{$name} 不为空",
        ];
    }
}
