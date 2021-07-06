<?php

declare (strict_types = 1);

namespace QT\GraphQL\Filters;

use GraphQL\Type\Definition\Type;
use Illuminate\Support\Traits\Macroable;

/**
 * 筛选类型生成器
 *
 * @package QT\GraphQL\Filters
 */
class Factory
{
    use Macroable;

    /**
     * @return array
     */
    public static function eq(string $name, Type $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 等于",
        ];
    }

    /**
     * @return array
     */
    public static function ne(string $name, Type $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 不等于",
        ];
    }

    /**
     * @return array
     */
    public static function gt(string $name, Type $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 大于",
        ];
    }

    /**
     * @return array
     */
    public static function ge(string $name, Type $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 大于等于",
        ];
    }

    /**
     * @return array
     */
    public static function lt(string $name, Type $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 小于",
        ];
    }

    /**
     * @return array
     */
    public static function le(string $name, Type $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 小于等于",
        ];
    }

    /**
     * @return array
     */
    public static function in(string $name, Type $type)
    {
        return [
            'type'        => Type::listOf($type),
            'description' => "{$name} 交集",
        ];
    }

    /**
     * @return array
     */
    public static function notIn(string $name, Type $type)
    {
        return [
            'type'        => Type::listOf($type),
            'description' => "{$name} 差集",
        ];
    }

    /**
     * @return array
     */
    public static function between(string $name, Type $type)
    {
        return [
            'type'        => Type::listOf($type),
            'description' => "{$name} 差集",
        ];
    }

    /**
     * @return array
     */
    public static function like(string $name, Type $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 模糊查询",
        ];
    }

    /**
     * @return array
     */
    public static function leftLike(string $name, Type $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 向左模糊查询",
        ];
    }

    /**
     * @return array
     */
    public static function rightLike(string $name, Type $type)
    {
        return [
            'type'        => $type,
            'description' => "{$name} 向右模糊查询",
        ];
    }

    /**
     * @return array
     */
    public static function isNull(string $name, Type $type)
    {
        return [
            'type'        => Type::boolean(),
            'description' => "{$name} 为空",
        ];
    }

    /**
     * @return array
     */
    public static function notNull(string $name, Type $type)
    {
        return [
            'type'        => Type::boolean(),
            'description' => "{$name} 不为空",
        ];
    }
}
