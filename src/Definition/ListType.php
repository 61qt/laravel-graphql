<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Options\ChunkOption;
use QT\GraphQL\Contracts\Resolvable;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * ListType
 *
 * @package QT\GraphQL\Definition
 */
class ListType extends ListOfType implements Resolvable
{
    /**
     * @var ModelType
     */
    public $ofType;

    /**
     * ListType Constructor
     *
     * @param ModelType $type
     */
    public function __construct(ModelType $type)
    {
        if (empty($this->name)) {
            $this->name = "{$type->name}List";
        }

        parent::__construct($type);
    }

    /**
     * {@inheritDoc}
     *
     * @param GraphQLManager $manager
     * @return array
     */
    public function getArgs(GraphQLManager $manager): array
    {
        return [
            'take'    => [
                'type'         => Type::int(),
                'description'  => '一次性获取的行数(默认为100)',
                'defaultValue' => 100,
            ],
            'skip'    => [
                'type'         => Type::int(),
                'description'  => '跳过的行数(默认为0)',
                'defaultValue' => 0,
            ],
            'all'     => [
                'type'         => Type::boolean(),
                'description'  => '是否全量获取(默认false)',
                'defaultValue' => false,
            ],
            'filters' => [
                'type'         => $this->ofType->getFiltersInput(),
                'description'  => '查询条件',
            ],
            'orderBy' => [
                'type'         => Type::listOf($this->ofType->getSortFields()),
                'description'  => '排序字段',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $node
     * @param array $args
     * @param Context $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolve(mixed $node, array $args, Context $context, ResolveInfo $info): mixed
    {
        $resolver  = $this->ofType->getResolver();
        $selection = $this->ofType->formatSelection(
            $info->getFieldSelection($context->getValue('graphql.max_depth', 5))
        );

        if ($this->ofType->isDeferrable()) {
            $resolver->disableAuthWithRelation();
        }

        return $resolver->chunk($context, new ChunkOption($args), $selection);
    }
}
