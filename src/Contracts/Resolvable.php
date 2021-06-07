<?php

namespace QT\GraphQL\Contracts;

use QT\GraphQL\GraphQLManager;
use GraphQL\Type\Definition\ResolveInfo;

interface Resolvable
{
    public function getArgs(GraphQLManager $manager): array;

    public function resolve(mixed $node, array $args, Context $context, ResolveInfo $info): mixed;
}