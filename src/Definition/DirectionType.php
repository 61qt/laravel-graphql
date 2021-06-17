<?php

declare (strict_types = 1);

namespace QT\GraphQL\Definition;

use GraphQL\Type\Definition\EnumType;

/**
 * DirectionType
 *
 * @package QT\GraphQL\Definition
 */
class DirectionType extends EnumType
{
    /**
     * @var string
     */
    public $name = Type::DIRECTION;

    /**
     * @var string
     */
    public $description = 'Sql排序关键字';

    /**
     * @param array $config
     */
    public function __construct()
    {
        parent::__construct([
            'values'      => [
                'asc'  => [
                    'value'       => 'asc',
                    'description' => '正序',
                ],
                'desc' => [
                    'value'       => 'desc',
                    'description' => '倒序',
                ],
            ],
        ]);
    }
}
