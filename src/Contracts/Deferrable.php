<?php

declare(strict_types=1);

namespace QT\GraphQL\Contracts;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Executor\Promise\Adapter\SyncPromise;

interface Deferrable extends Resolvable
{
    /**
     * 获取字段的Promise
     * 
     * @param mixed $node
     * @param array $args
     * @param Context $context
     * @param ResolveInfo $info
     * @return SyncPromise
     */
    public function getDeferred(mixed $node, array $args, Context $context, ResolveInfo $info): SyncPromise;
}