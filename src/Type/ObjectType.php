<?php
namespace QT\GraphQL\Type;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ObjectType as BaseObjectType;

class ObjectType extends BaseObjectType
{
    public function __construct(array $config = [])
    {
        if (!isset($config['resolveField'])) {
            // 对象内属性的解析回调
            $config['resolveField'] = $this->getResolveFieldFn();
        }

        parent::__construct($config);
    }

    public function getResolveFieldFn()
    {
        return function ($node, $args, $context, ResolveInfo $info) {
            $method = 'resolve' . ucfirst(Str::camel($info->fieldName));

            if (method_exists($this, $method)) {
                return $this->{$method}($node, $args, $context, $info);
            }

            if ($node instanceof Model) {
                $node = $node->toArray();
            }

            if (is_array($node)) {
                return $node[$info->fieldName] ?? null;
            }

            if (is_object($node)) {
                $value = $node->{$info->fieldName} ?? null;

                if (!is_null($value)) {
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