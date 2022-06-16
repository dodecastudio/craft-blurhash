<?php

namespace dodecastudio\blurhash\gql\directives;

use dodecastudio\blurhash\BlurHash;

use Craft;
use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Directive as GqlDirective;

class BlurHashToUri extends Directive
{

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
            'description' => 'Take a blurhash string and return it as a data URI string.',
            'args' => [],
        ]));

        return $type;
    }

    public static function name(): string
    {
        return 'blurhashToUri';
    }

    public static function apply($source, $value, array $arguments, ResolveInfo $resolveInfo): string
    {
        return BlurHash::getInstance()->blurHashServices->blurhashToUri($value);
    }
}