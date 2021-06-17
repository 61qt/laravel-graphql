<?php

declare (strict_types = 1);

namespace QT\GraphQL\Options;

/**
 * ListOption
 *
 * @package QT\GraphQL\Options
 */
class ListOption
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
     * @param mixed[] $args
     */
    public function __construct(array $args = [])
    {
        // format的逻辑放在option中处理,resolver只需要依赖option即可
        // 给未来restful使用resolver留出自定义空间
        foreach (['filters', 'orderBy', 'skip', 'take', 'all'] as $key) {
            if (isset($args[$key])) {
                $this->{$key} = $args[$key];
            }
        }
    }
}
