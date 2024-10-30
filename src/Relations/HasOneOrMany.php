<?php

namespace QT\GraphQL\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use QT\GraphQL\Contracts\RelationExtraKeys;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany as Base;

abstract class HasOneOrMany extends Base implements RelationExtraKeys
{
    /**
     * Extra key relations
     *
     * @var array
     */
    protected $extraKeys = [];

    /**
     * Create a new has one or many relationship instance.
     *
     * @param Builder $query
     * @param Model $parent
     * @param string $foreignKey
     * @param string $localKey
     * @param array $extraKeys
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey, array $extraKeys = [])
    {
        $this->extraKeys = $extraKeys;

        parent::__construct($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Match the eagerly loaded results to their single parents.
     *
     * @param array $models
     * @param Collection $results
     * @param string $relation
     * @return array
     */
    public function matchOne(array $models, Collection $results, $relation)
    {
        return $this->matchOneOrMany($models, $results, $relation, 'one');
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param array $models
     * @param Collection $results
     * @param string $relation
     * @return array
     */
    public function matchMany(array $models, Collection $results, $relation)
    {
        return $this->matchOneOrMany($models, $results, $relation, 'many');
    }

    /**
     * Match the eagerly loaded results to their many parents.
     *
     * @param array $models
     * @param Collection $results
     * @param string $relation
     * @param string $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        if (empty($this->extraKeys)) {
            return parent::matchOneOrMany($models, $results, $relation, $type);
        }

        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $keys = [$this->getDictionaryKey($model->getAttribute($this->localKey))];
            foreach ($this->extraKeys as $localKey => $_) {
                $keys[] = $this->getDictionaryKey($model->getAttribute($localKey));
            }

            $key = $this->arrayToKey($keys);

            if (isset($dictionary[$key])) {
                $model->setRelation(
                    $relation,
                    $this->getRelationValue($dictionary, $key, $type)
                );
            }
        }

        return $models;
    }

    /**
     * Build model dictionary keyed by the relation's foreign key.
     *
     * @param Collection $results
     * @return array
     */
    protected function buildDictionary(Collection $results)
    {
        if (empty($this->extraKeys)) {
            return parent::buildDictionary($results);
        }

        $foreign = $this->getForeignKeyName();

        return $results->mapToDictionary(function ($result) use ($foreign) {
            $keys = [$this->getDictionaryKey($result->{$foreign})];
            foreach ($this->extraKeys as $foreignKey) {
                $keys[] = $this->getDictionaryKey($result->{$foreignKey});
            }

            return [$this->arrayToKey($keys) => $result];
        })->all();
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $query = $this->getRelationQuery();

            $query->where($this->foreignKey, '=', $this->getParentKey());

            $query->whereNotNull($this->foreignKey);

            foreach ($this->extraKeys as $localKey => $foreignKey) {
                $query->where($foreignKey, $this->parent->getAttribute($localKey));
            }
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $whereIn = $this->whereInMethod($this->parent, $this->localKey);

        $this->getRelationQuery()->{$whereIn}(
            $this->foreignKey,
            $this->getKeys($models, $this->localKey)
        );

        foreach ($this->extraKeys as $localKey => $foreignKey) {
            $this->getRelationQuery()->{$whereIn}(
                $foreignKey,
                $this->getKeys($models, $localKey)
            );
        }
    }

    /**
     * 获取额外的关联localKey以及foreignKey
     *
     * @return array
     */
    public function getExtraKeyNames(): array
    {
        return $this->extraKeys;
    }

    /**
     * set array value to string key
     *
     * @param array $array
     * @return string
     */
    protected function arrayToKey(array $array): string
    {
        return implode("\t", $array);
    }
}
