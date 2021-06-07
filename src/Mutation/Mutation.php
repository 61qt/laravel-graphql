<?php

namespace QT\GraphQL\Mutation;

use RuntimeException;
use App\Exceptions\Error;
use App\GraphQL\AppContext;
use Illuminate\Support\Arr;
use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Type\ModelType;
use QT\GraphQL\Contracts\Context;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\InputObjectType;

/**
 * Class Mutation
 *
 * @package QT\GraphQL\Mutation
 */
abstract class Mutation
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
     * Constructor
     *
     * @param GraphQLManager $manager
     * @param array $options
     */
    public function __construct(GraphQLManager $manager)
    {
        if (empty($this->objectType)) {
            throw new RuntimeException("Mutation绑定的Object Type不能为空");
        }

        $this->ofType = $manager->getType($this->objectType);

        if (!$this->ofType instanceof ModelType) {
            throw new RuntimeException("Mutation必须绑定Model Type");
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
        foreach ($input as $key => $value) {
            if ($value === null) {
                $input[$key] = '';
            }
        }

        $resolver = $this->ofType->getResolver();

        dd($resolver, $info->fieldName);
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
            $inputName   = "{$mutation}Input";
            $mutationArg = isset($mutationArgs[$mutation])
                ? Arr::only($globalArgs, $mutationArgs[$mutation])
                : $globalArgs;

            $inputType = $manager->setType(new InputObjectType([
                'name'   => $inputName,
                'fields' => array_merge($this->getDefaultMutationArgs(), $mutationArg),
            ]));

            yield $mutation => [$this->ofType, $inputType, [$this, 'resolve']];
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
