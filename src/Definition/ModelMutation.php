<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use Generator;
use QT\GraphQL\Resolver;
use App\Exceptions\Error;
use Illuminate\Support\Arr;
use QT\GraphQL\GraphQLManager;
use GraphQL\Type\Definition\Type;
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
    }

    /**
     * 获取ModelType
     *
     * @return ModelType
     */
    public function getModelType(): ModelType
    {
        if ($this->ofType !== null) {
            return $this->ofType;
        }

        if (empty($this->objectType)) {
            throw new GraphQLException('Mutation绑定的Object Type不能为空');
        }

        $this->ofType = $this->manager->getType($this->objectType);

        if (!$this->ofType instanceof ModelType) {
            throw new GraphQLException('Mutation必须绑定Model Type');
        }

        return $this->ofType;
    }

    /**
     * 获取业务层逻辑
     *
     * @return Resolver
     */
    public function getResolver(): Resolver
    {
        return $this->getModelType()->getResolver();
    }

    /**
     * 处理mutation
     *
     * @param $node
     * @param $args
     * @param Context $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolve($node, $args, Context $context, ResolveInfo $info)
    {
        $resolver = $this->getResolver();

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
     * @throws Error
     * @return Generator<string, array>
     */
    public function getMutationConfig()
    {
        $globalArgs = $this->args();

        foreach ($this->getMutationArgs() as $mutation => $args) {
            yield $mutation => $this->getMutationResolveInfo($mutation, $globalArgs, $args);
        }
    }

    /**
     * 获取mutation需要的形参,返回类型
     *
     * @param string $mutation
     * @param array $globalArgs
     * @param array $args
     * @return array
     */
    protected function getMutationResolveInfo(string $mutation, array $globalArgs, array $args): array
    {
        // 获取方法备注信息
        $description = $this->getMutationDescription($mutation);
        $inputArgs   = $this->getInputArgs($mutation, $globalArgs, $args);

        if (!method_exists($this, 'get' . ucfirst($mutation) . 'Config')) {
            return [$this->getReturnType($mutation), $inputArgs, [$this, 'resolve'], $description];
        }

        return $this->{'get' . ucfirst($mutation) . 'Config'}($inputArgs, $description);
    }

    /**
     * 获取返回的数据结构
     *
     * @param string $name
     * @return Type
     */
    public function getReturnType(string $name): Type
    {
        return $this->getModelType();
    }

    /**
     * 获取mutation的形参
     *
     * @param string $mutation
     * @param array $globalArgs
     * @param array $args
     * @return array
     */
    protected function getInputArgs(string $mutation, array $globalArgs, array $args): array
    {
        if ($this->inputKey === null) {
            return !empty($args) ? Arr::only($globalArgs, $args) : [];
        }

        // object type 延迟加载形参
        $inputName = "{$mutation}Input";
        if (empty($args)) {
            $inputType = new NilType(['name' => $inputName]);
        } else {
            $inputType = new InputObjectType([
                'name'   => $inputName,
                'fields' => fn () => Arr::only($globalArgs, $args),
            ]);
        }

        return [$this->inputKey => $this->manager->setType($inputType)];
    }

    /**
     * 获取mutation的介绍
     *
     * @param string $mutation
     * @return string
     */
    protected function getMutationDescription(string $mutation): string
    {
        return $mutation;
    }
}
