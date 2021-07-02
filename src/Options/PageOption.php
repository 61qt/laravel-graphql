<?php

declare (strict_types = 1);

namespace QT\GraphQL\Options;

/**
 * PageOption
 *
 * @package QT\GraphQL\Options
 */
class PageOption extends JsonOption
{
    /**
     * @var int
     */
    public $take;

    /**
     * @var int
     */
    public $page;

    /**
     * @param mixed[] $args
     */
    public function __construct(array $args = [])
    {
        foreach (['take', 'page'] as $key) {
            if (isset($args[$key])) {
                $this->{$key} = $args[$key];
            }
        }

        parent::__construct($args);
    }
}
