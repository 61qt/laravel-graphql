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
     * @param string $name
     * @param array $sortFields
     */
    public function __construct(string $name, protected array $sortFields)
    {
        parent::__construct([
            'name'   => "{$name}SortFields",
            'fields' => [$this, 'getSortFields'],
        ]);
    }

    /**
     * 获取排序字段
     *
     * @return array
     */
    protected function getSortFields(): array
    {
        $sortFields = $tableFields = [];
        foreach ($this->sortFields as $field) {
            if (!Str::contains($field, '.')) {
                $sortFields[$field] = ['type' => Type::direction()];
            } else {
                [$table, $field] = explode('.', $field, 2);

                $tableFields[$table][$field] = ['type' => Type::direction()];
            }
        }

        foreach ($tableFields as $table => $fields) {
            $sortFields[$table] = ['type' => new InputObjectType([
                'name'   => "{$this->name}SortFields_{$table}",
                'fields' => $fields,
            ])];
        }

        return $sortFields;
    }
}
