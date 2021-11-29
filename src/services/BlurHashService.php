<?php
/**
 * BlurHash plugin for Craft CMS 3.x
 *
 * Render a BlurHash from a given image.
 *
 * @link      https://dodeca.studio
 * @copyright Copyright (c) 2021 Dodeca Studio
 */

namespace dodecastudio\blurhash\services;

use dodecastudio\blurhash\BlurHash;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\models\AssetTransform;

use kornrunner\Blurhash\Blurhash as KornRunnerBlurhash;

class BlurHashService extends Component
{

    /**
     * blurhash: Take a Craft CMS asset and return a data URI string.
     *
     * @param asset Asset
     *
     * @return string
     */
    public function blurhash($asset)
    {
        $blurhash = $this->blurhashEncode($asset);
        $blurhashImage = $this->blurhashToImage($blurhash);
        $blurhashImageDataUri = $this->imageToUri($blurhashImage);
        
        return $blurhashImageDataUri;
    }


    /**
     * blurhash: Take a blurhash string and return a data URI string.
     *
     * @param blurhash $string
     *
     * @return string
     */
    public function blurhashToUri($blurhash)
    {
        $blurhashImage = $this->blurhashToImage($blurhash);
        $blurhashImageDataUri = $this->imageToUri($blurhashImage);
        
        return $blurhashImageDataUri;
    }


    /**
     * blurhashEncode: Take a Craft CMS asset and return a blurhash string from it.
     *
     * @param asset Asset
     *
     * @return string
     */
    public function blurhashEncode($asset)
    {
        // Make sure it is an asset object
        if (!$asset instanceof Asset) {
            return false;
        }

        // Valid file types
        if (!in_array($asset->mimeType, BlurHash::getInstance()->getSettings()->allowedFileTypes)) {
            return false;
        }

        // Set size of copied / sampled image
        $sampleSize = 64;

        // Set unique cacheKey
        $cacheKey = 'blurhashstring-' . $asset->id . $asset->dateModified->format('YmdHis') . $sampleSize;
        $cachedValue = \Craft::$app->cache->get($cacheKey);

        // Check for cached value
        if ($cachedValue) {
            return $cachedValue;
            
        } else {

            $thumbnailImage = imagecreatetruecolor($sampleSize, $sampleSize);
            $sourceImage = imagecreatefromstring($asset->getContents());
            imagecopyresized($thumbnailImage, $sourceImage, 0, 0, 0, 0, $sampleSize, $sampleSize, $asset->width, $asset->height);
            $width = imagesx($thumbnailImage);
            $height = imagesy($thumbnailImage);            
            
            // Get colours for image
            $pixels = [];
            for ($y = 0; $y < $height; ++$y) {
                $row = [];
                for ($x = 0; $x < $width; ++$x) {
                    $index = imagecolorat($thumbnailImage, $x, $y);
                    $colors = imagecolorsforindex($thumbnailImage, $index);

                    $row[] = [$colors['red'], $colors['green'], $colors['blue']];
                }
                $pixels[] = $row;
            }
            
            imagedestroy($thumbnailImage);

            // Generate a blurhash from the image data.
            $components_x = $asset->width > $asset->height ? 4 : 3;
            $components_y = $asset->width < $asset->height ? 4 : 3;
            $blurhash = KornRunnerBlurhash::encode($pixels, $components_x, $components_y);
            \Craft::$app->cache->set($cacheKey, $blurhash, 60 * 60 * 24 * 7 * 4); // Cache for approx 1 month
            return $blurhash;
        }

    }


    /**
     * blurhashToImage: Take a blurhash string and decode it, returning a gdimage resource.
     *
     * @param blurhash $string
     *
     * @return image
     */
    private function blurhashToImage($blurhash)
    {
        // Check we're given a string.
        if (!is_string($blurhash)) {
            return false;
        }

        // Set size of copied / sampled image
        $blurredImageSize = 64;

        // Set unique cacheKey
        $cacheKey = 'blurhashimagedata-' . $blurhash . $blurredImageSize;
        $cachedValue = \Craft::$app->cache->get($cacheKey);

        // Check for cached value
        if ($cachedValue) {
            return $cachedValue;
            
        } else {

            // Set size of returned image. Keep it small!
            $width = $blurredImageSize;
            $height = $blurredImageSize;

            // Decode the blurhash to an image file.
            $pixels = KornRunnerBlurhash::decode($blurhash, $width, $height);
            $decodedImage  = imagecreatetruecolor($width, $height);
            for ($y = 0; $y < $height; ++$y) {
                for ($x = 0; $x < $width; ++$x) {
                    [$r, $g, $b] = $pixels[$y][$x];
                    imagesetpixel($decodedImage, $x, $y, imagecolorallocate($decodedImage, $r, $g, $b));
                }
            }

            // Render the image and return it.
            ob_start(); 
            imagepng($decodedImage);
            $decodedImageData = ob_get_contents();            
            \Craft::$app->cache->set($cacheKey, $decodedImageData, 60 * 60 * 24 * 7 * 4); // Cache for approx 1 month
            return ob_get_clean();
        }

    }


    /**
     * imageToUri: Takes an image resource and converts it to a data URI string.
     *
     * @param image $string
     *
     * @return string
     */
    private function imageToUri($image)
    {   
        return sprintf('data:%s;base64,%s', 'image/png', base64_encode($image));
    }
    
}
