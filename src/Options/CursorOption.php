<?php

declare (strict_types = 1);

namespace QT\GraphQL\Options;

/**
 * CursorOption
 *
 * @package QT\GraphQL\Options
 */
class CursorOption extends QueryOption
{
    /**
     * @var int
     */
    public $offset;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var array
     */
    protected $jsonKeys = [
        'filters',
    ];

    /**
     * @param mixed[] $args
     */
    public function __construct(array $args = [])
    {
        foreach (['offset', 'limit'] as $key) {
            if (isset($args[$key])) {
                $this->{$key} = $args[$key];
            }
        }

        parent::__construct($args);
    }
}
