<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use Generator;
use ReflectionClass;
use App\Exceptions\Error;
use Illuminate\Support\Arr;
use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Contracts\Context;
use GraphQL\Type\Definition\ResolveInfo;
use QT\GraphQL\Exceptions\GraphQLException;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Mutation
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
     * 对应反射信息
     *
     * @var ReflectionClass
     */
    protected $reflect;

    /**
     * input在args中的key
     *
     * @var string
     */
    protected $inputKey = null;

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
    abstract public function getMutationArgs(): array;

    /**
     * Constructor
     *
     * @param GraphQLManager $manager
     */
    public function __construct(protected GraphQLManager $manager)
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
    public function resolve($node, $args, Context $context, ResolveInfo $info)
    {
        $resolver = $this->ofType->getResolver();

        if ($this->inputKey === null) {
            $input = $args;
        } else {
            $input = $args[$this->inputKey] ?? [];
        }

        return $resolver->{$info->fieldName}($context, $input);
    }

    /**
     * 获取Mutation的配置信息
     *
     * @return Generator
     * @throws Error
     */
    public function getMutationConfig()
    {
        $globalArgs  = $this->args();
        $defaultArgs = $this->getDefaultMutationArgs();
        if (empty($this->reflect)) {
            $this->reflect = new ReflectionClass($this->ofType->getResolver());
        }

        foreach ($this->getMutationArgs() as $mutation => $args) {
            $inputArgs = !empty($args) ? Arr::only($globalArgs, $args) : $globalArgs;
            $inputArgs = array_merge($defaultArgs, $inputArgs);

            if ($this->inputKey !== null) {
                $name      = "{$mutation}Input";
                $inputType = new InputObjectType(['name' => $name, 'fields' => $inputArgs]);
                $inputArgs = [$this->inputKey => $this->manager->setType($inputType)];
            }

            yield $mutation => $this->getMutationResolveInfo($mutation, $inputArgs);
        }
    }

    /**
     * 获取mutation需要的形参,返回类型
     *
     * @param string $mutation
     * @param array $inputArgs
     * @return array
     */
    protected function getMutationResolveInfo(string $mutation, array $inputArgs): array
    {
        // 获取方法备注信息
        $description = $this->getMutationDescription($mutation);

        if (!method_exists($this, 'get' . ucfirst($mutation) . 'Config')) {
            return [$this->ofType, $inputArgs, [$this, 'resolve'], $description];
        }

        return $this->{'get' . ucfirst($mutation) . 'Config'}($inputArgs, $description);
    }

    /**
     * 获取mutation的介绍
     *
     * @param string $description
     * @return string
     */
    protected function getMutationDescription(string $mutation): string
    {
        return '';
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
