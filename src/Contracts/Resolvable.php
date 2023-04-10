<?php

declare(strict_types=1);

namespace QT\GraphQL\Contracts;

use QT\GraphQL\GraphQLManager;
use GraphQL\Type\Definition\ResolveInfo;

interface Resolvable
{
    /**
     * 获取需要的形参
     *
     * @param GraphQLManager $manager
     * @return array
     */
    public function getArgs(GraphQLManager $manager): array;

    /**
     * Graphql ResolveFn
     *
     * @param mixed $node
     * @param array $args
     * @param Context $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolve(mixed $node, array $args, Context $context, ResolveInfo $info): mixed;
}
