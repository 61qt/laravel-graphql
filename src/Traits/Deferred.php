<?php

declare(strict_types=1);

namespace QT\GraphQL\Traits;

use QT\GraphQL\Utils;
use GraphQL\Type\Schema;
use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Dataloaders\Dataloader;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\AbstractType;
use QT\GraphQL\Dataloaders\RelationLoader;
use GraphQL\Executor\Promise\Adapter\SyncPromise;
use Illuminate\Database\Eloquent\Relations\Relation;

trait Deferred
{
    /**
     * Relation primary key
     * 
     * @var array<string, string>
     */
    private $keyNames = [];

    /**
     * Resolve field dataloader
     *
     * @var array<string, Dataloader>
     */
    private $dataloaders = [];

    /**
     * 获取字段的Promise
     *
     * @param mixed $node
     * @param array $args
     * @param Context $context
     * @param ResolveInfo $info
     * @return SyncPromise
     */
    public function getDeferred(mixed $node, array $args, Context $context, ResolveInfo $info): SyncPromise
    {
        $path = array_filter($info->path, 'is_string');

        if ($info->returnType instanceof AbstractType) {
            $relation    = Relation::noConstraints(fn () => $node->{$info->fieldName}());
            $runtimeType = $info->returnType->resolveType($relation->getRelated(), $context, $info);

            if (is_callable($runtimeType)) {
                $runtimeType = Schema::resolveType($runtimeType);
            }

            $path[] = $runtimeType->name;
        }

        // 根据语法树节点来存储dataloader,减少重复的初始化工作
        $path = join('.', $path);
        if (!empty($this->dataloaders[$path])) {
            return $this->dataloaders[$path]->get($node?->{$this->keyNames[$path]});
        }

        $selection = [];
        if ($info->returnType instanceof AbstractType) {
            $unionSelection = Utils::getFieldSelection($info, 1);
            // 多态类型根据返回的类型推断请求的字段
            if (isset($unionSelection[$runtimeType->name])) {
                $selection = $unionSelection[$runtimeType->name];
            }
        } else {
            $selection = $info->getFieldSelection(1);
            $relation  = Relation::noConstraints(fn () => $node->{$info->fieldName}());
        }

        if (!empty($selection)) {
            $this->dataloaders[$path] = $this->getDataLoader($relation, $selection, $args);
        } else {
            // 没有请求具体的字段,不加载数据
            $this->dataloaders[$path] = new Dataloader(fn() => null);
        }

        $this->keyNames[$path] = Utils::getWithLocalKey($relation);

        return $this->dataloaders[$path]->get($node?->{$this->keyNames[$path]});
    }

    /**
     * 获取Relation Dataloader
     *
     * @param Relation $relation
     * @param array $selection
     * @param array $args
     * @return Dataloader
     */
    public function getDataLoader(Relation $relation, array $selection, array $args): Dataloader
    {
        $key = $relation->toBase()->toSql();
        if (!isset(Dataloader::$pool[$key])) {
            Dataloader::$pool[$key] = new RelationLoader($relation);
        }

        return Dataloader::$pool[$key]->setArgs($args)->setSelection($selection);
    }
}
