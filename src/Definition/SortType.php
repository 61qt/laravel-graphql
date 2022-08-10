<?php

declare(strict_types=1);

namespace QT\GraphQL\Definition;

use Illuminate\Support\Str;
use GraphQL\Type\Definition\InputObjectType;

/**
 * SortType
 *
 * @package QT\GraphQL\Definition
 */
class SortType extends InputObjectType
{
    /**
     * 初始化数据结构
     *
     * @param ModelType $type
     * @param array $sortFields
     */
    public function __construct(ModelType $type, protected array $sortFields)
    {
        parent::__construct([
            'name'   => "{$type->name}SortFields",
            'fields' => [$this, 'getSortFields'],
        ]);
    }

    /**
     * 获取排序字段
     *
     * @return array
     */
    protected function getSortFields()
    {
        $sortFields = [];
        foreach ($this->sortFields as $field) {
            if (!Str::contains($field, '.')) {
                $sortFields[$field] = ['type' => Type::direction()];
            } else {
                [$table, $field] = explode('.', $field, 2);

                $sortFields[$table] = ['type' => new InputObjectType([
                    'name'   => "{$this->name}SortFields_{$table}",
                    'fields' => [$field => ['type' => Type::direction()]],
                ])];
            }
        }

        return $sortFields;
    }
}
