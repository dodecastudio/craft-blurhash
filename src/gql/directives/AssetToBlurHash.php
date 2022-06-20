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

class AssetToBlurHash extends Directive
{
    const DEFAULT_AS_URI = true;

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
            'description' => 'Take a Craft CMS asset and return it as a data URI or blurhash string.',
            'args' => [
                new FieldArgument([
                    'name' => 'asUri',
                    'type' => Type::boolean(),
                    'defaultValue' => self::DEFAULT_AS_URI,
                    'description' => 'Whether to encode the blurhash string as a data URI.',
                ]),
            ],
        ]));

        return $type;
    }

    public static function name(): string
    {
        return 'assetToBlurHash';
    }

    public static function apply($source, $value, array $arguments, ResolveInfo $resolveInfo): string
    {
        $asUri = $arguments['asUri'] ?? self::DEFAULT_AS_URI;

        if ($resolveInfo->fieldName !== 'url') {
            return $value;
        }

        if ($source->kind !== 'image' || !in_array($source->mimeType, BlurHash::getInstance()->getSettings()->allowedFileTypes)) {
            return 'Unsupported filetype';
        }

        try {
            $result = $asUri ? BlurHash::getInstance()->blurHashServices->blurhash($source) : BlurHash::getInstance()->blurHashServices->blurhashEncode($source);
        } catch (BlurHash $e) {
            Craft::error('An error occured trying to generate the BlurHash image in the @assetToBlurHash GraphQL directive: ' . $e->getMessage(), __METHOD__);
            return null;
        }
        
        return $result;

    }
}