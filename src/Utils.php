<?php

declare(strict_types=1);

namespace QT\GraphQL;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
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
 * 常用方法封装
 *
 * @package QT\GraphQL
 */
final class Utils
{
    /**
     * 允许自动select with key的relation
     *
     * @var array
     */
    protected static $withMethods = [
        BelongsTo::class      => ['getForeignKeyName', 'getOwnerKeyName'],
        BelongsToMany::class  => ['getParentKeyName', 'getRelatedKeyName'],
        HasOne::class         => ['getLocalKeyName', 'getForeignKeyName'],
        HasMany::class        => ['getLocalKeyName', 'getForeignKeyName'],
        HasManyThrough::class => ['getLocalKeyName', 'getForeignKeyName'],
        HasOneThrough::class  => ['getLocalKeyName', 'getForeignKeyName'],
        MorphTo::class        => ['getForeignKeyName', 'getOwnerKeyName'],
        MorphToMany::class    => ['getParentKeyName', 'getRelatedKeyName'],
        MorphOne::class       => ['getLocalKeyName', 'getForeignKeyName'],
        MorphMany::class      => ['getLocalKeyName', 'getForeignKeyName'],
    ];

    /**
     * 获取关联字段
     *
     * @param Relation $relation
     * @return array
     */
    public static function getWithKeyName(Relation $relation): array
    {
        $class = get_class($relation);

        if (empty(static::$withMethods[$class])) {
            return [];
        }

        [$localKeyFn, $foreignKeyFn] = static::$withMethods[$class];

        return [$relation->{$localKeyFn}(), $relation->{$foreignKeyFn}()];
    }

    /**
     * 获取当前表关联字段
     *
     * @param Relation $relation
     * @return string
     */
    public static function getWithLocalKey(Relation $relation): string
    {
        return $relation->{static::$withMethods[get_class($relation)][0]}();
    }

    /**
     * 获取目标表关联字段
     *
     * @param Relation $relation
     * @return string
     */
    public static function getWithForeignKey(Relation $relation): string
    {
        return $relation->{static::$withMethods[get_class($relation)][1]}();
    }

    /**
     * 获取GraphQL请求的字段
     *
     * @param int $depth
     * @return array<string, mixed>
     */
    public static function getFieldSelection(ResolveInfo $info, int $depth = 0): array
    {
        $fields = [];

        foreach ($info->fieldNodes as $node) {
            if ($node->selectionSet === null) {
                continue;
            }

            $fields = array_merge_recursive(
                $fields,
                static::foldSelectionSet($info, $node->selectionSet, $depth)
            );
        }

        return $fields;
    }

    /**
     * 递归获取查询的字段
     * 
     * @param ResolveInfo $info
     * @param SelectionSetNode $selectionSet
     * @param int $depth
     * @return array<string, bool|array>
     */
    private static function foldSelectionSet(ResolveInfo $info, SelectionSetNode $selectionSet, int $depth): array
    {
        $fields = [];
        foreach ($selectionSet->selections as $node) {
            if ($node instanceof FieldNode) {
                $fields[$node->name->value] = $depth > 0 && $node->selectionSet !== null
                    ? static::foldSelectionSet($info, $node->selectionSet, $depth - 1)
                    : true;
            } elseif ($node instanceof FragmentSpreadNode) {
                $spreadName = $node->name->value;
                if (isset($info->fragments[$spreadName])) {
                    $fragment = $info->fragments[$spreadName];
                    $fields   = array_merge_recursive(
                        static::foldSelectionSet($info, $fragment->selectionSet, $depth),
                        $fields
                    );
                }
            } elseif ($node instanceof InlineFragmentNode) {
                $fields[$node->typeCondition->name->value] = static::foldSelectionSet($info, $node->selectionSet, $depth);
            }
        }

        return $fields;
    }
}
