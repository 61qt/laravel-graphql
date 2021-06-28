<?php

declare (strict_types = 1);

namespace QT\GraphQL\Definition;

use QT\GraphQL\Resolver;
use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Filters\Registrar;
use QT\GraphQL\Definition\ListType;
use QT\GraphQL\Contracts\Resolvable;
use GraphQL\Type\Definition\ResolveInfo;
use QT\GraphQL\Definition\PaginationType;
use GraphQL\Type\Definition\InputObjectType;
use Illuminate\Support\Arr;

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
    protected $mustSelection = [];

    /**
     * 详细页面进行展示的字段(填充超长字段)
     *
     * @var array
     */
    protected $detailedFields = [];

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
     * @return array
     */
    abstract public function getDataStructure(GraphQLManager $manager): array;

    /**
     * 获取逻辑层
     *
     * @return Resolver
     */
    abstract public function getResolver(): Resolver;

    /**
     * Constructor
     *
     * @param GraphQLManager $manager
     * @param array $options
     */
    public function __construct(protected GraphQLManager $manager, array $options = [])
    {
        parent::__construct(array_merge($options, [
            'fields' => function () {
                return $this->getDataStructure($this->manager);
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
     * 注册筛选条件
     *
     * @param Registrar $registrar
     */
    public function registrationFilters(Registrar $registrar)
    {

    }

    /**
     * 生成列表页使用的 Filters
     *
     * @return InputObjectType|NilType
     */
    public function getFiltersInput(): InputObjectType | NilType
    {
        if (!empty($this->filterInput)) {
            return $this->filterInput;
        }

        $registrar = new Registrar($this, $this->manager);

        $this->registrationFilters($registrar);

        $this->filterInput = $registrar->getFilterInput();

        return $this->manager->setType($this->filterInput);
    }

    /**
     * 获取筛选条件
     *
     * @return InputObjectType|NilType
     */
    public function getSortFields(): InputObjectType | NilType
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

        $this->sortInput = new InputObjectType([
            'name'   => "{$this->name}SortFields",
            'fields' => $sortFields,
        ]);

        return $this->manager->setType($this->sortInput);
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
        foreach ($this->mustSelection as $field => $val) {
            if (is_int($field) && is_string($val)) {
                $field = $val;
                $val   = true;
            }

            Arr::set($selection, $field, $val);
        }

        foreach ($this->detailedFields as $field) {
            Arr::forget($selection, $field);
        }

        return $selection;
    }
}
