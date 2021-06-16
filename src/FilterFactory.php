<?php

declare (strict_types = 1);

namespace QT\GraphQL;

use QT\GraphQL\Definition\Type;
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
     * 创建一组int类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return InputObjectType
     */
    public static function int(string $name, array $operators): InputObjectType
    {
        return self::create($name, Type::int(), $operators);
    }

    /**
     * 创建一组string类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return InputObjectType
     */
    public static function string(string $name, array $operators): InputObjectType
    {
        return self::create($name, Type::string(), $operators);
    }

    /**
     * 创建一组指定类型的筛选条件
     *
     * @param string $name
     * @param array $operators
     * @return InputObjectType
     */
    public static function create($name, $type, $operators): InputObjectType
    {
        $fields  = [];
        $factory = new self($name, $type);
        foreach ($operators as $operator) {
            if (isset(static::$operatorsMaps[$operator])) {
                $operator = static::$operatorsMaps[$operator];
            }

            if (method_exists($factory, $operator)) {
                $fields[$operator] = $factory->{$operator}();
            }
        }

        return new InputObjectType(['name' => "{$name}Filters", 'fields' => $fields]);
    }

    /**
     * @param string $name
     * @param BaseType $type
     */
    public function __construct(protected string $name, protected BaseType $type)
    {
    }

    /**
     * @return array
     */
    public function eq()
    {
        return [
            'type'        => $this->type,
            'description' => "{$this->name} 等于",
        ];
    }

    /**
     * @return array
     */
    public function ne()
    {
        return [
            'type'        => $this->type,
            'description' => "{$this->name} 不等于",
        ];
    }

    /**
     * @return array
     */
    public function gt()
    {
        return [
            'type'        => $this->type,
            'description' => "{$this->name} 大于",
        ];
    }

    /**
     * @return array
     */
    public function ge()
    {
        return [
            'type'        => $this->type,
            'description' => "{$this->name} 大于等于",
        ];
    }

    /**
     * @return array
     */
    public function lt()
    {
        return [
            'type'        => $this->type,
            'description' => "{$this->name} 小于",
        ];
    }

    /**
     * @return array
     */
    public function le()
    {
        return [
            'type'        => $this->type,
            'description' => "{$this->name} 小于等于",
        ];
    }

    /**
     * @return array
     */
    public function in()
    {
        return [
            'type'        => Type::listOf($this->type),
            'description' => "{$this->name} 交集",
        ];
    }

    /**
     * @return array
     */
    public function notIn()
    {
        return [
            'type'        => Type::listOf($this->type),
            'description' => "{$this->name} 差集",
        ];
    }

    /**
     * @return array
     */
    public function between()
    {
        return [
            'type'        => Type::listOf($this->type),
            'description' => "{$this->name} 差集",
        ];
    }

    /**
     * @return array
     */
    public function like()
    {
        return [
            'type'        => $this->type,
            'description' => "{$this->name} 模糊查询",
        ];
    }

    /**
     * @return array
     */
    public function leftLike()
    {
        return [
            'type'        => $this->type,
            'description' => "{$this->name} 向左模糊查询",
        ];
    }

    /**
     * @return array
     */
    public function rightLike()
    {
        return [
            'type'        => $this->type,
            'description' => "{$this->name} 向右模糊查询",
        ];
    }

    /**
     * @return array
     */
    public function isNull()
    {
        return [
            'type'        => Type::boolean(),
            'description' => "{$this->name} 为空",
        ];
    }

    /**
     * @return array
     */
    public function notNull()
    {
        return [
            'type'        => Type::boolean(),
            'description' => "{$this->name} 不为空",
        ];
    }
}
