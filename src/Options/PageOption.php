<?php

declare (strict_types = 1);

namespace QT\GraphQL\Options;

/**
 * PageOption
 *
 * @package QT\GraphQL\Options
 */
class PageOption
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
        // format的逻辑放在option中处理,resolver只需要依赖option即可
        // 给未来restful使用resolver留出自定义空间
        foreach (['filters', 'orderBy', 'take', 'page'] as $key) {
            if (isset($args[$key])) {
                $this->{$key} = $args[$key];
            }
        }
    }
}
