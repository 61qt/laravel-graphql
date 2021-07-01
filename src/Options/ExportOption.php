<?php

declare (strict_types = 1);

namespace QT\GraphQL\Options;

/**
 * ExportOption
 *
 * @package QT\GraphQL\Options
 */
class ExportOption
{
    /**
     * @var array
     */
    public $filters = [];

    /**
     * @var int
     */
    public $offset;

    /**
     * @var int
     */
    public $limit;

    /**
     * @param mixed[] $args
     */
    public function __construct(array $args = [])
    {
        foreach (['filters', 'offset', 'limit'] as $key) {
            if (isset($args[$key])) {
                $this->{$key} = $args[$key];
            }
        }
    }
}
