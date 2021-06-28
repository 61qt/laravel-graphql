<?php

declare (strict_types = 1);

namespace QT\GraphQL\Definition;

use Illuminate\Support\Str;
use QT\GraphQL\Contracts\Context;
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
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['resolveField'])) {
            // 对象内属性的解析回调
            $config['resolveField'] = $this->getResolveFieldFn();
        }

        parent::__construct($config);
    }

    /**
     * 获取字段处理回调
     *
     * @return callable
     */
    public function getResolveFieldFn(): callable
    {
        return function ($node, $args, Context $context, ResolveInfo $info) {
            $method = 'resolve' . ucfirst(Str::camel($info->fieldName));

            if (method_exists($this, $method)) {
                return $this->{$method}($node, $args, $context, $info);
            }

            if ($node instanceof Model) {
                // 检查model是否加载了该字段
                if ($node->relationLoaded($info->fieldName)) {
                    return $node->getRelation($info->fieldName);
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
