<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use QT\GraphQL\Utils;
use QT\GraphQL\Context;
use GraphQL\Type\Schema;
use Illuminate\Support\Collection;
use QT\GraphQL\Dataloaders\Dataloader;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\AbstractType;
use QT\GraphQL\Dataloaders\RelationLoader;
use Illuminate\Database\Eloquent\Relations\Relation;
use QT\GraphQL\Contracts\Context as ContextContract;

/**
 * 延迟加载relation
 * 
 * @package QT\GraphQL\Definition
 */
trait Deferrable
{
    /**
     * Relation primary key
     *
     * @var array<string, string>
     */
    private $keyNames = [];

    /**
     * AST节点缓存key
     *
     * @var string
     */
    protected $nodeCacheKey = 'g-ast-%s';

    /**
     * 是否支持延迟加载
     * 
     * @return bool
     */
    public function isDeferrable()
    {
        return true;
    }

    /**
     * 获取字段默认处理回调
     *
     * @param mixed $node
     * @param array $args
     * @param ContextContract $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolveField(mixed $node, array $args, ContextContract $context, ResolveInfo $info): mixed
    {
        if (!method_exists($node, $info->fieldName)) {
            return $node->getAttributeValue($info->fieldName);
        }
        // 检查model是否加载了该字段
        if ($node->relationLoaded($info->fieldName)) {
            return $node->getRelation($info->fieldName);
        }

        $path = array_filter($info->path, 'is_string');

        if (count($path) > $context->getValue('graphql.max_depth', 5)) {
            return null;
        }

        if ($info->returnType instanceof AbstractType) {
            $relation    = Relation::noConstraints(fn () => $node->{$info->fieldName}());
            $runtimeType = $info->returnType->resolveType($relation->getRelated(), $context, $info);

            if (is_callable($runtimeType)) {
                $runtimeType = Schema::resolveType($runtimeType);
            }

            $path[] = $runtimeType->name;
        }

        // 根据语法树节点来存储dataloader
        // 保证同一个节点能复用,减少重复的初始化工作
        // 储存路径为[type.path],type+path可以保证loader不会重复
        $path    = join('-', $path);
        $loaders = $this->getLoaders($context);
        if (!empty($loaders[$path])) {
            return $loaders[$path]->get($node?->{$this->keyNames[$path]});
        }

        $selection = [];
        if ($info->returnType instanceof AbstractType) {
            $unionSelection = Utils::getFieldSelection($info, 0);
            // 多态类型根据返回的类型推断请求的字段
            if (isset($unionSelection[$runtimeType->name])) {
                $selection = $unionSelection[$runtimeType->name];
            }
        } else {
            $selection = $info->getFieldSelection(0);
            $relation  = Relation::noConstraints(fn () => $node->{$info->fieldName}());
        }

        $this->keyNames[$path] = Utils::getWithLocalKey($relation);

        // 没有请求具体的字段,不加载数据
        if (empty($selection)) {
            $loaders[$path] = new Dataloader(fn () => null);
        } else {
            $loaders[$path] = $this->createSqlDataloader($context, $relation, $selection, $args);
        }

        return $loaders[$path]->get($node?->{$this->keyNames[$path]});
    }

    /**
     * 获取graphql dataloader池
     *
     * @param Context $context
     * @return Collection<string, Dataloader>
     */
    protected function getLoaders(Context $context): Collection
    {
        // 将dataloader冗余在上下文中,保证不同的请求不会相互影响
        $key = sprintf($this->nodeCacheKey, $this->name);

        if (!$context->has($key)) {
            $context->setValue($key, new Collection());
        }

        return $context->getValue($key);
    }

    /**
     * 获取Relation Dataloader
     *
     * @param Context $context
     * @param Relation $relation
     * @param array $selection
     * @param array $args
     * @return Dataloader
     */
    protected function createSqlDataloader(Context $context, Relation $relation, array $selection, array $args): Dataloader
    {
        $model = $relation->getRelated();

        foreach ($selection as $field => $_) {
            // 根据relation推断关联必要的字段
            if (method_exists($model, $field)) {
                $field = Utils::getWithLocalKey($model->{$field}());
            }

            $relation->addSelect($model->qualifyColumn($field));
        }

        $key = $relation->toBase()->toSql();

        if (!$context->loaders->offsetExists($key)) {
            $context->loaders->offsetSet($key, new RelationLoader($relation));
        }

        return $context->loaders->offsetGet($key);
    }
}
