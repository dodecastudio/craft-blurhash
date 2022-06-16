<?php

namespace dodecastudio\blurhash\gql\directives;

use dodecastudio\blurhash\BlurHash;

use Craft;
use craft\elements\Asset;
use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Directive as GqlDirective;

class AverageColor extends Directive
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
            'description' => 'Take an asset or blurhash string and return its average color.',
            'args' => [],
        ]));

        return $type;
    }

    public static function name(): string
    {
        return 'averageColor';
    }

    public static function apply($source, $value, array $arguments, ResolveInfo $resolveInfo): string
    {
        if ($source instanceof Asset && $source->kind === 'image') {
            return BlurHash::getInstance()->blurHashServices->averageColor($source);
        }

        return BlurHash::getInstance()->blurHashServices->averageColor($value);
        
    }
}