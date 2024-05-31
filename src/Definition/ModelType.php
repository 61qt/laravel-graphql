<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use QT\GraphQL\Resolver;
use Illuminate\Support\Arr;
use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Filters\Registrar;
use QT\GraphQL\Contracts\Resolvable;
use GraphQL\Type\Definition\ResolveInfo;
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
     * @param GraphQLManager $manager
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
     * @param array $config
     */
    public function __construct(protected GraphQLManager $manager, array $config = [])
    {
        parent::__construct(array_merge($config, [
            'fields'      => fn () => $this->getModelFields(),
            'description' => $this->description,
        ]));
    }

    /**
     * 获取model可用字段,允许继承细分可用字段
     *
     * @return array
     */
    protected function getModelFields(): array
    {
        return $this->getDataStructure($this->manager);
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
        $depth     = $context->getValue('graphql.max_depth', 5);
        $selection = $this->formatSelection($info->getFieldSelection($depth), true);

        return $this->getResolver()->show($context, $args, $selection);
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

        $registrar = new Registrar($this->name, $this->manager);

        $this->registrationFilters($registrar);

        if (empty($registrar->filters)) {
            return $this->filterInput = Type::nil();
        }

        return $this->manager->setType(
            $this->filterInput = $registrar->getFilterInput()
        );
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

        return $this->manager->setType(
            $this->sortInput = new SortType($this->name, $this->sortFields)
        );
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
     * @param boolean $isDetail
     * @return array
     */
    public function formatSelection(array $selection, bool $isDetail = false): array
    {
        if (!$isDetail) {
            foreach ($this->detailedFields as $field) {
                Arr::forget($selection, $field);
            }
        }

        return $selection;
    }
}
