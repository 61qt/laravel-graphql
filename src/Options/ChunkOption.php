<?php

declare(strict_types=1);

namespace QT\GraphQL\Options;

/**
 * ChunkOption
 *
 * @package QT\GraphQL\Options
 */
class ChunkOption extends QueryOption
{
    /**
     * @var int
     */
    public $skip;

    /**
     * @var int
     */
    public $take;

    /**
     * @var bool
     */
    public $all;

    /**
     * @param array $args
     */
    public function __construct(array $args = [])
    {
        foreach (['skip', 'take', 'all'] as $key) {
            if (isset($args[$key])) {
                $this->{$key} = $args[$key];
            }
        }

        parent::__construct($args);
    }
}
