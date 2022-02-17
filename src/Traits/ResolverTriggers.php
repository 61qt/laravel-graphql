<?php

declare (strict_types = 1);

namespace QT\GraphQL\Traits;

use QT\GraphQL\Contracts\Context;
use QT\GraphQL\Options\QueryOption;
use QT\GraphQL\Options\CursorOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

trait ResolverTriggers
{
    /**
     * 在查询前触发
     * 
     * @param Context $context
     */
    protected function beforeQuery(Context $context)
    {

    }

    /**
     * 在查询列表前触发
     * 
     * @param Context $context
     * @param QueryOption $option
     */
    protected function beforeList(Context $context, QueryOption $option)
    {
        $this->beforeQuery($context);
    }

    /**
     * 列表查询后触发
     * 
     * @param Collection $models
     */
    protected function afterList($models)
    {
        return $models;
    }

    /**
     * 查询单条记录前触发
     * 
     * @param Context $context
     * @param int|string $id
     */
    protected function beforeShow(Context $context, int|string $id)
    {
        $this->beforeQuery($context);
    }

    /**
     * 查询单条记录后触发
     * 
     * @param Model $model
     */
    protected function afterShow($model)
    {
        return $model;
    }

    /**
     * 创建记录前触发
     * 
     * @param Context $context
     * @param Model $model
     * @param array $input
     */
    protected function beforeStore(Context $context, Model $model, array $input)
    {

    }

    /**
     * 创建记录后触发
     * 
     * @param Model $model
     */
    protected function afterStore($model)
    {

    }

    /**
     * 修改记录前触发
     * 
     * @param Context $context
     * @param Model $model
     * @param array $input
     */
    protected function beforeUpdate(Context $context, Model $model, array $input)
    {

    }

    /**
     * 修改记录后触发
     * 
     * @param Model $model
     */
    protected function afterUpdate($model)
    {

    }

    /**
     * 删除记录前触发
     * 
     * @param Context $context
     * @param Model $model
     */
    protected function beforeDestroy(Context $context, Model $model)
    {
        
    }

    /**
     * 删除记录前触发
     * 
     * @param Model $model
     */
    protected function afterDestroy($model)
    {
        return $model;
    }

    /**
     * 批量删除前触发
     * 
     * @param Collection $models
     */
    protected function beforeBatchDestroy($models)
    {

    }

    /**
     * 导出列表前触发
     * 
     * @param Context $context
     * @param CursorOption $option
     */
    protected function beforeExport(Context $context, CursorOption $option)
    {
        $this->beforeList($context, $option);
    }
}
