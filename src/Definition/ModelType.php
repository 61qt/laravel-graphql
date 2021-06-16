<?php

declare (strict_types = 1);

namespace QT\GraphQL\Definition;

use QT\GraphQL\Resolver;
use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Definition\ListType;
use QT\GraphQL\Contracts\Resolvable;
use GraphQL\Type\Definition\ResolveInfo;
use QT\GraphQL\Definition\PaginationType;
use GraphQL\Type\Definition\InputObjectType;

/**
 * ModelType
 * 
 * @package QT\GraphQL\Definition
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
     * 支持排序的字段
     *
     * @var array
     */
    protected $sortFields = [];

    /**
     * 列表页通用筛选条件
     * 
     * @var InputObjectType|NilType
     */
    protected $filterInput;

    /**
     * 列表页通用排序条件
     * 
     * @var InputObjectType|NilType
     */
    protected $sortInput;

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
            'fields' => function () use ($manager) {
                return $this->getDataStructure($manager);
            },
        ]));
    }

    /**
     * {@inheritDoc}
     * 
     * @param GraphQLManager $manager
     * @return array
     */
    public function getArgs(GraphQLManager $manager): array
    {
        return [];
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
        return $this->getResolver()->show(
            $context,
            $args,
            $info->getFieldSelection($context->getValue('max_depth', 5))
        );
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
     * 生成列表页使用的 Filters
     *
     * @return InputObjectType|NilType
     */
    public function getFiltersInput(): InputObjectType|NilType
    {
        if (!empty($this->filterInput)) {
            return $this->filterInput;
        }

        $filters = $this->getFilters();
        if (empty($filters)) {
            return $this->filterInput = Type::nil();
        }

        return $this->filterInput = new InputObjectType([
            'name'   => "{$this->name}Filters",
            'fields' => $filters,
        ]);
    }

    /**
     * 获取筛选条件
     *
     * @return InputObjectType|NilType
     */
    public function getSortFields(): InputObjectType|NilType
    {
        if (!empty($this->sortInput)) {
            return $this->sortInput;
        }

        if (empty($this->sortFields)) {
            return $this->sortInput = Type::nil();
        }

        $sortFields = [];
        foreach ($this->sortFields as $field) {
            $sortFields[$field] = ['type' => Type::direction()];
        }

        return $this->sortInput = new InputObjectType([
            'name'   => "{$this->name}SortFields",
            'fields' => $sortFields,
        ]);
    }

    /**
     * 获取额外的 Graphql Type
     *
     * @return array<Resolvable|Type>
     */
    public function getExtraTypes(): array
    {
        $types = [];
        if ($this->useList) {
            $types[] = new ListType($this);
        }
        if ($this->usePagination) {
            $types[] = new PaginationType($this);
        }

        return $types;
    }

    /**
     * 格式化选中的字段
     *
     * @param array $selection
     * @return array
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
