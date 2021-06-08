<?php

declare(strict_types=1);

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
        if (isset($args['filters'])) {
            // TODO 添加Filters格式判断
            $this->filters = $args['filters'];
        }

        if (isset($args['orderBy'])) {
            // TODO 添加OrderBy格式判断
            $this->orderBy = $args['orderBy'];
        }

        foreach (['skip', 'take', 'all'] as $arg) {
            if (isset($args[$arg])) {
                $this->{$arg} = $args[$arg];
            }
        }
    }
}