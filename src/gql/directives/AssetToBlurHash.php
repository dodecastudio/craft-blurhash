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
            'args' => [
                new FieldArgument([
                    'name' => 'asUri',
                    'type' => Type::boolean(),
                    'defaultValue' => self::DEFAULT_AS_URI,
                    'description' => 'Whether to encode the blurhash string as a data URI.',
                ]),
            ],
            'description' => 'Take a Craft CMS asset and return it as a data URI or blurhash string.',
        ]));

        return $type;
    }

    public static function name(): string
    {
        return 'assetToBlurHash';
    }

    public static function apply($source, $value, array $arguments, ResolveInfo $resolveInfo)
    {
        $asUri = $arguments['asUri'] ?? self::DEFAULT_AS_URI;

        if ($resolveInfo->fieldName !== 'url') {
            return $value;
        }

        if ($source->kind !== 'image' || !in_array($source->mimeType, BlurHash::getInstance()->getSettings()->allowedFileTypes)) {
            return null;
        }

        try {
            $result = $asUri ? BlurHash::getInstance()->blurHashServices->blurhash($source) : BlurHash::getInstance()->blurHashServices->blurhashEncode($source);
        } catch (ImagerException $e) {
            Craft::error('An error occured trying to generate the BlurHash image in the @assetToBlurHash GraphQL directive: ' . $e->getMessage(), __METHOD__);
            return null;
        }
        
        return $result;


        // $onAssetElement = $value instanceof Asset;
        // $onAssetElementList = is_array($value) && !empty($value);
        // $onApplicableAssetField = $source instanceof Asset;
        // $asUri = $arguments['inlineOnly'] ?? self::DEFAULT_AS_URI;

        // if (!($onAssetElement || $onAssetElementList || $onApplicableAssetField)) {
        //     return $value;
        // }

        // // If this directive is applied to a single asset
        // if ($onAssetElement) {
        //     return $value->setTransform($transform);
        // }

        // // ...or a list of assets
        // if ($onAssetElementList) {
        //     foreach ($value as &$asset) {
        //         if ($asset instanceof Asset) {
        //             $asset->setTransform($transform);
        //         }
        //     }
        //     return $value;
        // }

        // // $sourceAsset = $onApplicableAssetField ? $source : $value;

        // // $result = $asUri ? BlurHash::getInstance()->blurHashServices->blurhash($sourceAsset) : BlurHash::getInstance()->blurHashServices->blurhashEncode($sourceAsset);

        // // return $result;

        // return $value;

    }
}