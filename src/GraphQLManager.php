<?php

namespace QT\GraphQL;

use Closure;
use RuntimeException;
use GraphQL\Type\Definition\Type;
use QT\GraphQL\Mutation\Mutation;
use QT\GraphQL\Type\Scalar\JsonType;
use QT\GraphQL\Type\Scalar\MixedType;
use QT\GraphQL\Type\Scalar\BigIntType;
use GraphQL\Type\Definition\ObjectType;
use QT\GraphQL\Type\Scalar\TimestampType;

/**
 * @method JsonType      json()
 * @method MixedType     mixed()
 * @method BigIntType    bigint()
 * @method TimestampType timestamp()
 *
 * Class GraphQLManager
 * @package App\GraphQL
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
     * @var array
     */
    protected $defaultTypes = [
        JsonType::class,
        MixedType::class,
        BigIntType::class,
        TimestampType::class,
    ];

    public function __construct()
    {
        foreach ($this->defaultTypes as $type) {
            $this->setType($type);
        }
    }

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
     * @throws RuntimeException
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
            throw new RuntimeException("类型错误");
        }

        return $this->types[$type->name] = $type;
    }

    /**
     * @param string $name
     * @return ?Mutation
     */
    public function getMutation(string $name): Mutation
    {
        return $this->find($name, static::MUTATION);
    }

    /**
     * @param Mutation $mutation
     * @return BaseMutation
     */
    public function setMutation(Mutation $mutation)
    {
        $this->mutations[$mutation->name] = $mutation;
    }

    /**
     * @param string $name
     * @param string $space
     * @return Type|Mutation
     */
    protected function find(string $name, string $space): Type | Mutation
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

        $type = call_user_func($this->getTypeFinder(), $name, $space);

        if (empty($type) || (!$type instanceof Type && !$type instanceof Mutation)) {
            throw new RuntimeException("{$name} 类型不存在");
        }

        return $containers[$name] = $type;
    }

    /**
     * 获取 Graphql type 查询回调 
     * 
     * @return Closure
     */
    public function getTypeFinder(): Closure
    {
        return $this->typeFinder ?: function () {

        };
    }

    /**
     * 设置 Graphql type 查询回调
     * 
     * @param Closure $typeFinder
     * @return self
     */
    public function setTypeFinder(Closure $typeFinder)
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
