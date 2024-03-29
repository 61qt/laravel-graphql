<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use Closure;
use GraphQL\Utils\Utils;
use Illuminate\Support\Str;
use QT\GraphQL\Contracts\Context;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\UnresolvedFieldDefinition;
use GraphQL\Type\Definition\ObjectType as BaseObjectType;

/**
 * ObjectType
 *
 * @package QT\GraphQL\Definition
 */
class ObjectType extends BaseObjectType implements HasFieldsType
{
    /**
     * @var array<string, FieldDefinition|UnresolvedFieldDefinition>
     */
    private $originalFields;

    /**
     * @var array<string, callable>
     */
    protected $fieldResolvers = [];

    /**
     * 字段是否加载
     *
     * @var bool
     */
    private $loadUnresolved = false;

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
     * 初始化可用字段
     *
     * @return void
     */
    protected function initializeFields(): void
    {
        if (isset($this->originalFields)) {
            return;
        }

        $this->originalFields = FieldDefinition::defineFieldMap(
            $this,
            $this->config['fields'] ?? []
        );
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @return FieldDefinition
     */
    public function getField(string $name): FieldDefinition
    {
        Utils::invariant($this->hasField($name), 'Field "%s" is not defined for type "%s"', $name, $this->name);

        return $this->findField($name);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @return ?FieldDefinition
     */
    public function findField(string $name): ?FieldDefinition
    {
        $fields = $this->getFields();

        if (!isset($fields[$name])) {
            return null;
        }

        if ($fields[$name] instanceof UnresolvedFieldDefinition) {
            $fields[$name] = $fields[$name]->resolve();
        }

        return $fields[$name];
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @return bool
     */
    public function hasField(string $name): bool
    {
        $fields = $this->getFields();

        return isset($fields[$name]);
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->getOriginalFields();
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function getFieldNames(): array
    {
        return array_keys($this->getFields());
    }

    /**
     * 获取原始字段
     *
     * @return array
     */
    public function getOriginalFields(): array
    {
        $this->initializeFields();

        if (!$this->loadUnresolved) {
            foreach ($this->originalFields as $name => $field) {
                if ($field instanceof UnresolvedFieldDefinition) {
                    $this->originalFields[$name] = $field->resolve();
                }
            }

            $this->loadUnresolved = true;
        }

        return $this->originalFields;
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
        $defaultFn = [$this, 'resolveField'];
        foreach ($this->getOriginalFields() as $field) {
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
     * @param mixed $node
     * @param array $args
     * @param Context $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolveField(mixed $node, array $args, Context $context, ResolveInfo $info): mixed
    {
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
    }
}
