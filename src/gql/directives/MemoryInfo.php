<?php

namespace dodecastudio\blurhash\gql\directives;

use dodecastudio\blurhash\BlurHash;

use Craft;
use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class MemoryInfo extends Directive
{
    private static $DEFAULT_INFO_TYPE = 'required';

    public static function create(): GqlDirective
    {
        if ($type = GqlEntityRegistry::getEntity(self::name())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(static::name(), new self([
            'name' => static::name(),
            'locations' => [
                DirectiveLocation::FIELD,
            ],
            'description' => 'Take a Craft CMS asset and return the estimated memory required to load it in to memory.',
            'args' => [
                new FieldArgument([
                    'name' => 'infoType',
                    'type' => Type::string(),
                    'defaultValue' => self::$DEFAULT_INFO_TYPE,
                    'description' => 'The information you would like to return (supports either \'required\' or \'usage\').',
                ]),
            ],
        ]));

        return $type;
    }

    public static function name(): string
    {
        return 'memoryInfo';
    }

    public static function apply($source, $value, array $arguments, ResolveInfo $resolveInfo): string
    {
        $infoType = $arguments['infoType'] ?? self::$DEFAULT_INFO_TYPE;
        return BlurHash::getInstance()->blurHashServices->memoryInfo($source, $infoType);
    }
}
