<?php

namespace QT\GraphQL\Traits;

use QT\GraphQL\Contracts\Context;

trait ResolverTriggers
{
    protected function beforeList(Context $context)
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

    protected function beforeUpdate(Context $context, int|string $id)
    {
    }

    protected function afterUpdate($model)
    {
    }

    protected function beforeStore(Context $context)
    {
    }

    protected function afterStore($model)
    {
    }

    protected function beforeDestroy(Context $context, int|string $id)
    {
    }

    protected function afterDestroy($model)
    {
        return $model;
    }

    protected function beforeBatchDestroy($models)
    {

    }
}
