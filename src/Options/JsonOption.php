<?php

declare (strict_types = 1);

namespace QT\GraphQL\Options;

/**
 * JsonOption
 *
 * @package QT\GraphQL\Options
 */
class JsonOption
{
    /**
     * @var array
     */
    public $filters = [];

    /**
     * @var array
     */
    public $orderBy = [];

    /**
     * @var array
     */
    protected $jsonKeys = [
        'orderBy',
        'filters',
    ];

    /**
     * @param mixed[] $args
     */
    public function __construct(array $args = [])
    {
        foreach ($this->jsonKeys as $key) {
            if (isset($args[$key])) {
                $this->{$key} = is_string($args[$key])
                    ? json_decode($args[$key], true)
                    : $args[$key];
            }
        }
    }
}
