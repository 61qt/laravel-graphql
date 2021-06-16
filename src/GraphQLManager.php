<?php

declare (strict_types = 1);

namespace QT\GraphQL;

use Closure;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use QT\GraphQL\Definition\ModelMutation;
use QT\GraphQL\Exceptions\GraphQLException;

/**
 * GraphQLManager
 * 
 * @package QT\GraphQL
 */
class GraphQLManager
{
    const TYPE     = 'Type';
    const MUTATION = 'Mutation';

    /**
     * @var array
     */
    protected $types = [];

    /**
     * @var array
     */
    protected $mutations = [];

    /**
     * @var Closure
     */
    protected $typeFinder;

    /**
     * @param string $name
     * @return Type $type
     */
    public function getType(string $name): Type
    {
        return $this->find($name, static::TYPE);
    }

    /**
     * @param string $name
     * @param string|Type $type
     * @throws GraphQLException
     */
    public function setType(string|Type $type): Type
    {
        if (
            (is_string($type) && class_exists($type)) && 
            is_subclass_of($type, Type::class)
        ) {
            $type = new $type;
        }

        if (!$type instanceof Type) {
            throw new GraphQLException("类型错误");
        }

        return $this->types[$type->name] = $type;
    }

    /**
     * @param string $name
     * @return ModelMutation
     */
    public function getMutation(string $name): ModelMutation
    {
        return $this->find($name, static::MUTATION);
    }

    /**
     * @param ModelMutation $mutation
     * @return ModelMutation
     */
    public function setMutation(ModelMutation $mutation): ModelMutation
    {
        return $this->mutations[$mutation->name] = $mutation;
    }

    /**
     * @param string $name
     * @param string $space
     * @return Type|ModelMutation
     * @throws GraphQLException
     */
    protected function find(string $name, string $space): Type | ModelMutation
    {
        if ($space === static::TYPE) {
            $containers = &$this->types;
        } else {
            $containers = &$this->mutations;
        }

        if (isset($containers[$name])) {
            if (is_string($containers[$name])) {
                $containers[$name] = new $containers[$name];
            }

            return $containers[$name];
        }

        $type = call_user_func($this->getTypeFinder(), $name, $space, $this);

        if (empty($type) || (!$type instanceof Type && !$type instanceof ModelMutation)) {
            throw new GraphQLException("{$name} 类型不存在");
        }

        return $containers[$name] = $type;
    }

    /**
     * 获取 Graphql type 查询回调 
     * 
     * @return callable
     */
    public function getTypeFinder(): callable
    {
        return $this->typeFinder ?: function () {

        };
    }

    /**
     * 设置 Graphql type 查询回调
     * 
     * @param callable $typeFinder
     * @return self
     */
    public function setTypeFinder(callable $typeFinder)
    {
        $this->typeFinder = $typeFinder;

        return $this;
    }

    /**
     * @param string            $name
     * @param callable|array    $fields
     * @param array             $args
     * @return ObjectType
     * @throws Error
     */
    public function create(string $name, callable | array $fields, array $args = []): ObjectType
    {
        return tap(new ObjectType(compact('name', 'fields', 'args')), function ($object) {
            $this->setType($object);
        });
    }

    /**
     * @param $name
     * @param $arguments
     * @return BaseMutation|ObjectType|Type
     */
    public function __call($name, $arguments)
    {
        if (method_exists(Type::class, $name)) {
            return Type::{$name}(...$arguments);
        }

        return $this->getType($name);
    }
}
