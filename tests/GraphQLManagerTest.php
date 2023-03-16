<?php

namespace QT\Test;

use QT\GraphQL\GraphQLManager;
use PHPUnit\Framework\TestCase;
use QT\GraphQL\Definition\Type;
use QT\GraphQL\Definition\NilType;
use GraphQL\Type\Definition\IDType;
use QT\GraphQL\Definition\JsonType;
use GraphQL\Type\Definition\IntType;
use QT\GraphQL\Definition\MixedType;
use QT\GraphQL\Definition\BigIntType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\BooleanType;
use QT\GraphQL\Definition\DirectionType;
use QT\GraphQL\Definition\TimestampType;
use QT\GraphQL\Exceptions\GraphQLException;

class GraphQLManagerTest extends TestCase
{
    public function testGetGlobalType()
    {
        $globalTypes = [
            Type::STRING    => new StringType(),
            Type::INT       => new IntType(),
            Type::BOOLEAN   => new BooleanType(),
            Type::FLOAT     => new FloatType(),
            Type::ID        => new IDType(),
            Type::NIL       => new NilType(),
            Type::JSON      => new JsonType(),
            Type::MIXED     => new MixedType(),
            Type::BIGINT    => new BigIntType(),
            Type::TIMESTAMP => new TimestampType(),
            Type::DIRECTION => new DirectionType(),
        ];

        $manager = new GraphQLManager();

        foreach ($globalTypes as $name => $globalType) {
            $this->assertEquals($globalType, $manager->getType($name));
        }
    }

    public function testCreateType()
    {
        $manager = new GraphQLManager();
        $type    = $manager->create('foo', fn () => ['bar' => Type::int()]);

        $this->assertInstanceOf(ObjectType::class, $manager->foo());
        $this->assertEquals(spl_object_id($type), spl_object_id($manager->foo()));
        // Test object struct
        $this->assertSame('bar', $manager->foo()->getField('bar')->name);
        $this->assertSame(Type::int(), $manager->foo()->getField('bar')->getType());
    }

    public function testSetInvalidType()
    {
        $manager = new GraphQLManager();

        $this->expectException(GraphQLException::class);
        $this->expectExceptionMessage('Invalid graphql type');

        $manager->setType(GraphQLManager::class);
    }

    public function testGetNotFoundType()
    {
        $manager = new GraphQLManager();

        $this->expectException(GraphQLException::class);
        $this->expectExceptionMessage('"foobar" Not found');

        $manager->getType('foobar');
    }

    public function testGetNotFoundMutation()
    {
        $manager = new GraphQLManager();

        $this->expectException(GraphQLException::class);
        $this->expectExceptionMessage('"foobarMutation" Not found');

        $manager->getMutation('foobarMutation');
    }
}
