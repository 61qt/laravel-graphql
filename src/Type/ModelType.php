<?php

namespace QT\GraphQL\Type;

use QT\GraphQL\Resolver;
use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Contracts\Resolvable;
use QT\GraphQL\Type\Custom\ListType;
use GraphQL\Type\Definition\ResolveInfo;
use QT\GraphQL\Type\Custom\PaginationType;
use GraphQL\Type\Definition\InputObjectType;

/**
 * abstract ModelType
 * 
 * @package QT\GraphQL\Type
 */
abstract class ModelType extends ObjectType implements Resolvable
{
    /**
     * 是否启用分页类型
     *
     * @var bool
     */
    public $usePagination = true;

    /**
     * 是否启用list类型(全量获取)
     *
     * @var bool
     */
    public $useList = false;

    /**
     * 必须选中的字段(填充关联字段)
     *
     * @var array
     */
    public $mustSelection = [];

    /**
     * 详细页面进行展示的字段(填充超长字段)
     *
     * @var array
     */
    public $detailedFields = [];

    /**
     * 列表页通用筛选条件
     * 
     * @var InputObjectType
     */
    protected $filterInput;

    /**
     * 获取model数据结构
     *
     * @return string
     */
    abstract public function getDataStructure(GraphQLManager $manager): array;

    /**
     * 获取逻辑层
     *
     * @return string
     */
    abstract public function getResolver(): Resolver;

    /**
     * Constructor
     *
     * @param GraphQLManager $manager
     * @param array $options
     */
    public function __construct(GraphQLManager $manager, array $options = [])
    {
        parent::__construct(array_merge($options, [
            'fields' => $this->getDataStructure($manager),
        ]));
    }

    /**
     * 获取可查询条件
     *
     * @return array
     */
    public function getArgs(GraphQLManager $manager): array
    {
        return [];
    }

    /**
     * @param $node
     * @param $args
     * @param Context $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolve(mixed $node, array $args, Context $context, ResolveInfo $info): mixed
    {
        return $this->getResolver()->show(
            $context,
            $args,
            $info->getFieldSelection($context->getValue('max_depth', 5))
        );
    }

    /**
     * 生成列表页使用的 Filters
     *
     * @return array
     */
    public function getFiltersInput(): InputObjectType
    {
        if (!empty($this->filterInput)) {
            return $this->filterInput;
        }

        return $this->filterInput = new InputObjectType([
            'name'   => "{$this->name}Filters",
            'fields' => $this->getFilters(),
        ]);
    }

    /**
     * 获取筛选条件
     *
     * @return array
     */
    public function getFilters(): array
    {
        return [];
    }

    /**
     * 获取可用的 Graphql Type
     *
     * @param GraphQLManager $manager
     */
    public function getQueryable(GraphQLManager $manager): array
    {
        $types = [$manager->setType($this)];

        if ($this->useList) {
            $types[] = $manager->setType(new ListType($this));
        }
        if ($this->usePagination) {
            $types[] = $manager->setType(new PaginationType($this));
        }

        return $types;
    }

    /**
     * 格式化选中的字段
     *
     * @param GraphQLManager $manager
     */
    public function formatSelection(array $selection): array
    {
        $selection = array_merge($selection, $this->mustSelection);

        // TODO 生成Schema时,就从列表页的fields中剥离
        foreach ($this->detailedFields as $field) {
            unset($selection[$field]);
        }

        return $selection;
    }
}
