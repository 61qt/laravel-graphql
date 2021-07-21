<?php

declare (strict_types = 1);

namespace QT\GraphQL\Traits;

use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Options\JsonOption;
use QT\GraphQL\Options\CursorOption;
use Illuminate\Database\Eloquent\Model;

trait ResolverTriggers
{
    protected function beforeList(Context $context, JsonOption $option)
    {
    }

    protected function afterList($models)
    {
        return $models;
    }

    protected function beforeShow(Context $context, int|string $id)
    {
    }

    protected function afterShow($model)
    {
        return $model;
    }

    protected function beforeUpdate(Context $context, Model $model, array $input)
    {
    }

    protected function afterUpdate($model)
    {
    }

    protected function beforeStore(Context $context, Model $model, array $input)
    {
    }

    protected function afterStore($model)
    {
    }

    protected function beforeDestroy(Context $context, Model $model)
    {
    }

    protected function afterDestroy($model)
    {
        return $model;
    }

    protected function beforeBatchDestroy($models)
    {

    }

    protected function beforeExport(Context $context, CursorOption $option)
    {
        $this->beforeList($context, $option);
    }
}
