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
    protected $inputKey = 'input';

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
    public function resolve($node, $input, Context $context, ResolveInfo $info)
    {
        $resolver = $this->ofType->getResolver();

        return $resolver->{$info->fieldName}($context, $input[$this->inputKey] ?? []);
    }

    /**
     * 获取Mutation的配置信息
     *
     * @return Generator
     * @throws Error
     */
    public function getMutationConfig()
    {
        $globalArgs = $this->args();
        if (empty($this->reflect)) {
            $this->reflect = new ReflectionClass($this->ofType->getResolver());
        }

        foreach ($this->getMutationArgs() as $mutation => $args) {
            $name   = "{$mutation}Input";
            $fields = !empty($args) ? Arr::only($globalArgs, $args) : $globalArgs;

            $inputObject = $this->manager->setType(
                new InputObjectType(compact('name', 'fields'))
            );

            $mutationArg = array_merge(
                $this->getDefaultMutationArgs(),
                [$this->inputKey => $inputObject]
            );

            // 获取方法备注信息
            $description = '';
            if (isDevelopEnv()) {
                $description = $this->getMethodComment($mutation);
            }

            if (method_exists($this, 'get' . ucfirst($mutation) . 'Config')) {
                yield $mutation => $this->{'get' . ucfirst($mutation) . 'Config'}($mutationArg, $description);
            } else {
                yield $mutation => [$this->ofType, $mutationArg, [$this, 'resolve'], $description];
            }
        }
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

    /**
     * 根据方法名称获取方法备注信息
     *
     * @param string $method
     * @return null|string
     */
    protected function getMethodComment(string $method)
    {
        if (!$this->reflect->hasMethod($method)) {
            return '';
        }

        $lines = $this->parseDocComment($this->reflect->getMethod($method)->getDocComment());
        if ($lines === false) {
            return '';
        }

        return trim(Arr::first($lines));
    }

    /**
     * 解析文档内容为行
     *
     * @param $comment
     */
    protected function parseDocComment($comment)
    {
        if ($comment === false) {
            return false;
        }

        if (preg_match('#^/\*\*(.*)\*/#s', $comment, $matches) === false) {
            return false;
        }

        $comment = $matches[1];
        if (preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false) {
            return false;
        }

        return $lines[1];
    }
}
