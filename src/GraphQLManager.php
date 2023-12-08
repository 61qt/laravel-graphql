<?php

declare(strict_types=1);

namespace QT\GraphQL;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use QT\GraphQL\Definition\ModelMutation;
use QT\GraphQL\Exceptions\GraphQLException;
use QT\GraphQL\Definition\Type as GlobalType;

/**
 * GraphQLManager
 *
 * @package QT\GraphQL
 */
class GraphQLManager
{
    public const TYPE     = 'Type';
    public const MUTATION = 'Mutation';

    /**
     * @var array
     */
    protected $types = [];

    /**
     * @var array
     */
    protected $mutations = [];

    /**
     * Type查询器
     *
     * @var callable
     */
    protected $typeFinder;

    /**
     * @param string $name
     * @return Type
     */
    public function getType(string $name): Type
    {
        if (method_exists(GlobalType::class, $name)) {
            return GlobalType::{$name}();
        }

        return $this->find($name, static::TYPE);
    }

    /**
     * @param string|Type $type
     * @throws GraphQLException
     * @return Type
     */
    public function setType(string | Type $type): Type
    {
        if (
            (is_string($type) && class_exists($type)) &&
            is_subclass_of($type, Type::class)
        ) {
            $type = new $type();
        }

        if (!$type instanceof Type) {
            throw new GraphQLException('Invalid graphql type');
        }

        return $this->types[$type->name] = $type;
    }

    /**
     * @param string $name
     * @param callable|array $fields
     * @throws Error
     * @return ObjectType
     */
    public function create(string $name, callable | array $fields): ObjectType
    {
        return $this->setType(new ObjectType(['name' => $name, 'fields' => $fields]));
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
     * @param string $name
     * @param string $space
     * @throws GraphQLException
     * @return Type|ModelMutation
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
                $containers[$name] = new $containers[$name]();
            }

            return $containers[$name];
        }

        $type = call_user_func($this->getTypeFinder(), $name, $space, $this);

        if (empty($type) || (!$type instanceof Type && !$type instanceof ModelMutation)) {
            throw new GraphQLException("\"{$name}\" Not found");
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
        return $this->typeFinder ?: function () {};
    }

    /**
     * 设置 Graphql type 查询回调
     *
     * @param callable $typeFinder
     * @return self
     */
    public function setTypeFinder(callable $typeFinder): self
    {
        $this->typeFinder = $typeFinder;

        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * @return ModelMutation|ObjectType|Type
     */
    public function __call($name, $arguments)
    {
        return $this->getType($name);
    }
}
