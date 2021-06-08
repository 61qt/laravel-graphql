<?php
namespace QT\GraphQL\Definition;

use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Queries\ModelType;
use QT\GraphQL\Queries\ObjectType;
use GraphQL\Type\Definition\Type;
use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Options\PageOption;
use QT\GraphQL\Contracts\Resolvable;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class PaginationType
 * 
 * @package QT\GraphQL\Definition
 */
class PaginationType extends ObjectType implements Resolvable
{
    /**
     * @var ModelType
     */
    public $ofType;

    /**
     * PaginationType Constructor
     * 
     * @param ModelType $type
     */
    public function __construct(ModelType $type)
    {
        $this->ofType = $type;

        parent::__construct([
            'name'   => isset($this->name) ? $this->name : "{$type->name}Pagination",
            'fields' => [$this, 'getDataStructure'],
        ]);
    }

    /**
     * @return array
     */
    public function getDataStructure(): array
    {
        return [
            'items'        => [
                'type'        => Type::listOf($this->ofType),
                'description' => '数据集',
            ],
            'currentPage'  => [
                'type'        => Type::int(),
                'description' => '当前页',
            ],
            'lastPage'     => [
                'type'        => Type::int(),
                'description' => '最后一页',
            ],
            'perPage'      => [
                'type'        => Type::int(),
                'description' => '当前页行数',
            ],
            'total'        => [
                'type'        => Type::int(),
                'description' => '总行数',
            ],
            'hasMorePages' => [
                'type'        => Type::boolean(),
                'description' => '是否有下一页',
            ],
        ];
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
                'type'        => Type::int(),
                'description' => '一次性获取的行数(默认为100)',
            ],
            'page'    => [
                'type'         => Type::int(),
                'description'  => '页码',
                'defaultValue' => 0,
            ],
            'filters' => [
                'type'         => $this->ofType->getFiltersInput(),
                'description'  => '查询条件',
                'defaultValue' => [],
            ],
            'orderBy' => [
                'type'        => Type::string(),
                'description' => '排序字段',
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
        $selection = $this->ofType->formatSelection(
            $info->getFieldSelection($context->getValue('max_depth', 5))['items']
        );

        return $this->ofType->getResolver()->pagination(
            $context, new PageOption($args), $selection
        );
    }
}
