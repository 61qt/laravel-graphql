<?php
namespace QT\GraphQL\Type\Scalar;

/**
 * TODO Json类型
 *
 * Class JsonType
 * @package QT\GraphQL\Type\Scalar
 */
class JsonType extends MixedType
{
    /**
     * @var string
     */
    public $name = 'json';

    /**
     * @var string
     */
    public $description = 'json类型';
}