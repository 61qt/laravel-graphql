<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use Closure;
use Illuminate\Support\Str;
use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Contracts\Deferrable;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ObjectType as BaseObjectType;

/**
 * ObjectType
 *
 * @package QT\GraphQL\Definition
 */
class ObjectType extends BaseObjectType
{
    /**
     * @var array
     */
    protected $fieldResolvers = [];

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['resolveField'])) {
            // 对象内属性的解析回调
            $config['resolveField'] = $this->getFieldResolver();
        }

        parent::__construct($config);
    }

    /**
     * 是否支持延迟加载
     * 
     * @return bool
     */
    public function isDeferrable()
    {
        return $this instanceof Deferrable;
    }

    /**
     * 获取字段处理回调
     *
     * @return Closure
     */
    public function getFieldResolver(): callable
    {
        return function ($node, $args, Context $context, ResolveInfo $info) {
            if (empty($this->fieldResolvers)) {
                $this->initializeFieldResolver();
            }

            if (empty($this->fieldResolvers[$info->fieldName])) {
                return null;
            }

            $resolver = $this->fieldResolvers[$info->fieldName];

            return call_user_func($resolver, $node, $args, $context, $info);
        };
    }

    /**
     * 初始化字段回调函数
     */
    protected function initializeFieldResolver()
    {
        $defaultFn = Closure::bind(static::getDefaultFieldResolver(), $this);
        foreach ($this->getFields() as $field) {
            $method = 'resolve' . ucfirst(Str::camel($field->name));

            if (method_exists($this, $method)) {
                $this->fieldResolvers[$field->name] = [$this, $method];
            } else {
                $this->fieldResolvers[$field->name] = $defaultFn;
            }
        }
    }

    /**
     * 获取字段默认处理回调
     *
     * @return Closure
     */
    public static function getDefaultFieldResolver(): Closure
    {
        return function ($node, $args, Context $context, ResolveInfo $info) {
            if ($node instanceof Model) {
                if (method_exists($node, $info->fieldName)) {
                    // 检查model是否加载了该字段
                    if ($node->relationLoaded($info->fieldName)) {
                        return $node->getRelation($info->fieldName);
                    }

                    return $this instanceof Deferrable
                        ? $this->getDeferred($node, $args, $context, $info)
                        : null;
                }

                return $node->getAttributeValue($info->fieldName);
            }

            if (is_array($node)) {
                return $node[$info->fieldName] ?? null;
            }

            if (is_object($node)) {
                $value = $node->{$info->fieldName} ?? null;

                if ($value !== null) {
                    return $value;
                }

                if (method_exists($node, $info->fieldName)) {
                    return $node->{$info->fieldName}();
                }
            }

            return null;
        };
    }
}
