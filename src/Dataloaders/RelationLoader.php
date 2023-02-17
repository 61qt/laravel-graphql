<?php

declare(strict_types=1);

namespace QT\GraphQL\Dataloaders;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Eloquent relation Dataloader
 *
 * @package QT\GraphQL\Dataloaders
 */
class RelationLoader extends Dataloader
{
    /**
     * Relation resolver
     *
     * @var array
     */
    protected $resolvers = [
        BelongsTo::class      => 'resolveBelongsTo',
        BelongsToMany::class  => 'resolveBelongsToMany',
        HasOne::class         => 'resolveHasOneOrMany',
        HasMany::class        => 'resolveHasOneOrMany',
        HasOneThrough::class  => 'resolveHasOneOrManyThrough',
        HasManyThrough::class => 'resolveHasOneOrManyThrough',
        MorphOne::class       => 'resolveMorphOneOrMany',
        MorphMany::class      => 'resolveMorphOneOrMany',
        MorphTo::class        => 'resolveBelongsTo',
        MorphToMany::class    => 'resolveBelongsToMany',
    ];

    /**
     * 关联目标model
     * 
     * @var Model
     */
    protected $model;

    /**
     * 查询字段
     *
     * @var array
     */
    protected $selection = [];

    /**
     * 入参
     *
     * @var array
     */
    protected $args = [];

    /**
     * 关联加载器
     *
     * @param Relation $relation
     */
    public function __construct(protected Relation $relation)
    {
        parent::__construct([$this, '__invoke']);

        $this->model = $relation->getRelated();
    }

    /**
     * 设置可用参数
     *
     * @param array $args
     * @return static
     */
    public function setArgs(array $args)
    {
        $this->args = array_merge($this->args, $args);

        return $this;
    }

    /**
     * 设置要查询的字段
     *
     * @param array $selection
     * @return static
     */
    public function setSelection(array $selection)
    {
        $this->selection = array_merge($this->selection, $selection);

        return $this;
    }

    /**
     * @param array $keys
     * @return array
     */
    public function __invoke(array $keys): array
    {
        $name = get_class($this->relation);
        if (empty($this->resolvers[$name])) {
            return [];
        }

        $relation = clone $this->relation;

        if (empty($this->selection)) {
            $this->selection = [$this->model->getKeyName() => true];
        }

        foreach ($this->selection as $field => $_) {
            if (!method_exists($this->model, $field)) {
                $relation->addSelect($this->model->qualifyColumn($field));
            }
        }

        return $this->{$this->resolvers[$name]}($relation, $keys);
    }

    /**
     * @param MorphTo|BelongsTo $relation
     * @param array $keys
     * @return array
     */
    protected function resolveBelongsTo(BelongsTo $relation, array $keys): array
    {
        $keyName = $relation->getQualifiedOwnerKeyName();
        $results = $relation->addSelect($keyName)->whereIn($keyName, $keys)->get();

        return $this->toMaps($keys, $results, $relation->getOwnerKeyName());
    }

    /**
     * @param MorphToMany|BelongsToMany $relation
     * @param array $keys
     * @return array
     */
    protected function resolveBelongsToMany(MorphToMany|BelongsToMany $relation, array $keys): array
    {
        $relation = $relation->addSelect($relation->getQualifiedRelatedKeyName())
            ->whereIn($relation->getQualifiedForeignPivotKeyName(), $keys);

        if ($relation instanceof MorphToMany) {
            $relation->where($relation->qualifyPivotColumn($relation->getMorphType()), $relation->getMorphClass());
        }

        return $this->toMaps(
            $keys,
            $relation->get(),
            "{$relation->getPivotAccessor()}.{$relation->getForeignPivotKeyName()}",
            true
        );
    }

    /**
     * @param HasOne|HasMany $relation
     * @param array $keys
     * @return array
     */
    protected function resolveHasOneOrMany(HasOne|HasMany $relation, array $keys): array
    {
        $keyName = $relation->getQualifiedForeignKeyName();
        $results = $relation->addSelect($keyName)->whereIn($keyName, $keys)->get();

        return $this->toMaps(
            $keys,
            $results,
            $relation->getForeignKeyName(),
            $relation instanceof HasMany
        );
    }

    /**
     * @param HasOneThrough|HasManyThrough $relation
     * @param array $keys
     * @return array
     */
    protected function resolveHasOneOrManyThrough(HasOneThrough|HasManyThrough $relation, array $keys): array
    {
        $keyName = $relation->getQualifiedFirstKeyName();
        $results = $relation->whereIn($keyName, $keys)->get();

        return $this->toMaps(
            $keys,
            $results,
            'laravel_through_key',
            !$relation instanceof HasOneThrough
        );
    }

    /**
     * @param MorphOne|MorphMany $relation
     * @param array $keys
     * @return array
     */
    protected function resolveMorphOneOrMany(MorphOne|MorphMany $relation, array $keys): array
    {
        $keyName = $relation->getQualifiedForeignKeyName();
        $results = $relation->addSelect($keyName)->whereIn($keyName, $keys)->get();

        return $this->toMaps(
            $keys,
            $results,
            $relation->getForeignKeyName(),
            $relation instanceof MorphMany
        );
    }

    /**
     * 把结果集变为hash map
     *
     * @param array $keys
     * @param Collection $results
     * @param string $matchKey
     * @param bool $isMany
     * @return array
     */
    protected function toMaps(array $keys, Collection $results, string $matchKey, bool $isMany = false)
    {
        $maps = [];

        if (!$isMany) {
            foreach ($results as $result) {
                $maps[Arr::get($result, $matchKey)] = $result;
            }
        } else {
            foreach ($keys as $key) {
                $maps[$key] = [];
            }

            foreach ($results as $result) {
                $maps[Arr::get($result, $matchKey)][] = $result;
            }
        }

        return $maps;
    }
}
