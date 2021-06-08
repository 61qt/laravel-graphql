<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use App\Exceptions\Error;
use Illuminate\Support\Arr;
use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Definition\ModelType;
use GraphQL\Type\Definition\ResolveInfo;
use QT\GraphQL\Exceptions\GraphQLException;

/**
 * Class Mutation
 *
 * @package QT\GraphQL\Definition
 */
abstract class ModelMutation
{
    /**
     * object type
     *
     * @var string
     */
    protected $objectType = null;

    /**
     * 对应的model type
     *
     * @var ModelType
     */
    protected $ofType;

    /**
     * 可输入参数
     *
     * @return array
     */
    abstract public function args(): array;

    /**
     * 允许对外调用的方法
     *
     * @return array
     */
    abstract public function getMutationList(): array;

    /**
     * TODO 添加触发器,用于检查权限
     * Constructor
     *
     * @param GraphQLManager $manager
     */
    public function __construct(GraphQLManager $manager)
    {
        if (empty($this->objectType)) {
            throw new GraphQLException("Mutation绑定的Object Type不能为空");
        }

        $this->ofType = $manager->getType($this->objectType);

        if (!$this->ofType instanceof ModelType) {
            throw new GraphQLException("Mutation必须绑定Model Type");
        }
    }

    /**
     * 处理mutation
     *
     * @param $node
     * @param $args
     * @param AppContext $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolve($node, $input, Context $context, ResolveInfo $info)
    {
        $resolver = $this->ofType->getResolver();

        return $resolver->{$info->fieldName}($context, $input);
    }

    /**
     * @return \Iterable
     * @throws Error
     */
    public function getMutationConfig(GraphQLManager $manager)
    {
        $globalArgs   = $this->args();
        $mutationArgs = $this->getMutationArgs();

        foreach ($this->getMutationList() as $mutation) {
            $mutationArg = isset($mutationArgs[$mutation])
                ? Arr::only($globalArgs, $mutationArgs[$mutation])
                : $globalArgs;

            $mutationArg = array_merge($this->getDefaultMutationArgs(), $mutationArg);

            yield $mutation => [$this->ofType, $mutationArg, [$this, 'resolve']];
        }
    }

    /**
     * 方法对应可输入参数
     *
     * @return array
     */
    public function getMutationArgs()
    {
        return [];
    }

    /**
     * 所有Mutation共用的默认参数
     *
     * @return array
     */
    protected function getDefaultMutationArgs()
    {
        return [];
    }
}
