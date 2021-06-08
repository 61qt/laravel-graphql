<?php

declare (strict_types = 1);

namespace QT\GraphQL\Definition;

use QT\GraphQL\Definition\JsonType;
use QT\GraphQL\Definition\MixedType;
use QT\GraphQL\Definition\BigIntType;
use GraphQL\Type\Definition\ScalarType;
use QT\GraphQL\Definition\TimestampType;
use QT\GraphQL\Definition\DirectionType;
use GraphQL\Type\Definition\Type as BaseType;

abstract class Type extends BaseType
{
    public const JSON      = 'json';
    public const MIXED     = 'mixed';
    public const BIGINT    = 'bigint';
    public const TIMESTAMP = 'timestamp';
    public const DIRECTION = 'direction';

    /** 
     * @var array<ScalarType|DirectionType> 
     * */
    protected static $globalTypes = [];

    /**
     * @api
     */
    public static function json() : JsonType
    {
        if (! isset(static::$globalTypes[self::JSON])) {
            static::$globalTypes[self::JSON] = new JsonType;
        }

        return static::$globalTypes[self::JSON];
    }

    /**
     * @api
     */
    public static function mixed() : MixedType
    {
        if (! isset(static::$globalTypes[self::MIXED])) {
            static::$globalTypes[self::MIXED] = new MixedType;
        }

        return static::$globalTypes[self::MIXED];
    }

    /**
     * @api
     */
    public static function timestamp() : TimestampType
    {
        if (! isset(static::$globalTypes[self::TIMESTAMP])) {
            static::$globalTypes[self::TIMESTAMP] = new TimestampType;
        }

        return static::$globalTypes[self::TIMESTAMP];
    }

    /**
     * @api
     */
    public static function bigint() : BigIntType
    {
        if (! isset(static::$globalTypes[self::BIGINT])) {
            static::$globalTypes[self::BIGINT] = new BigIntType;
        }

        return static::$globalTypes[self::BIGINT];
    }

    /**
     * @api
     */
    public static function direction() : DirectionType
    {
        if (! isset(static::$globalTypes[self::DIRECTION])) {
            static::$globalTypes[self::DIRECTION] = new DirectionType;
        }

        return static::$globalTypes[self::DIRECTION];
    }
}
