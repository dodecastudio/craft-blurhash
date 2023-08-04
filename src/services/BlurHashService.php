<?php
/**
 * BlurHash plugin for Craft CMS 4.x
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
use craft\fields\data\ColorData;
use craft\validators\ColorValidator;

use kornrunner\Blurhash\Blurhash as KornRunnerBlurhash;
use kornrunner\Blurhash\Base83;

class BlurHashService extends Component
{

    private $allowedInfoTypes = ['required', 'usage'];
    private $DEFAULT_INFO_TYPE = 'required';

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
        // Quit if blurhashEncode failed
        if (!$blurhash) {
            return false;
        }
        $blurhashImage = $this->blurhashToImage($blurhash, $asset);
        $blurhashImageDataUri = $this->imageToUri($blurhashImage);
        
        return $blurhashImageDataUri;
    }


    /**
     * blurhash: Take a blurhash string and return a data URI string.
     *
     * @param blurhash String
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
     * validAsset: Check an asset is valid
     *
     * @param asset Asset
     *
     * @return string
     */
    private function validAsset($asset) {

        // Make sure it is an asset object
        if (!$asset instanceof Asset) {
            return false;
        }

        // Ensure it's a supported file type
        if (!in_array($asset->mimeType, BlurHash::getInstance()->getSettings()->allowedFileTypes)) {
            return false;
        }

        // Check file exists
        if (method_exists($asset, 'getFs')) {
            if (method_exists($asset->getFs(), 'fileExists')) {
                if ( !$asset->getFs()->fileExists($asset->path) ) {
                    return false;
                }
            }
        }
        if (method_exists($asset, 'getVolume')) {
            if (method_exists($asset->getVolume(), 'fileExists')) {
                if ( !$asset->getVolume()->fileExists($asset->path) ) {
                    return false;
                }
            }
        }

        return true;
    
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

        // Check it's a valid asset resource
        if (!$this->validAsset($asset)) {
            return false;
        }

        // Set size of copied / sampled image
        $sampleSize = BlurHash::getInstance()->getSettings()->sampleMaxImageSize;

        // Set unique cacheKey
        $cacheKey = 'blurhashstring-' . $asset->id . $asset->dateModified->format('YmdHis') . $sampleSize;
        $cachedValue = \Craft::$app->cache->get($cacheKey);

        // Check for cached value
        if ($cachedValue) {
            return $cachedValue;
            
        } else {

            $sampleImageWidth = round($sampleSize * ($asset->width > $asset->height ? 1 : $asset->width / $asset->height));
            $sampleImageHeight = round($sampleSize * ($asset->height > $asset->width ? 1 : $asset->height / $asset->width));

            $thumbnailImage = imagecreatetruecolor($sampleImageWidth, $sampleImageHeight);
            $sourceImage = imagecreatefromstring($asset->getContents());
            imagecopyresized($thumbnailImage, $sourceImage, 0, 0, 0, 0, $sampleImageWidth, $sampleImageHeight, $asset->width, $asset->height);
            $width = imagesx($thumbnailImage);
            $height = imagesy($thumbnailImage);
            
            // Get colors for image
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
            $components_x = $asset->width > $asset->height ? 6 : ceil(6 * ($asset->width / $asset->height));
            $components_y = $asset->width < $asset->height ? 6 : ceil(6 * ($asset->height / $asset->width));
            $blurhash = KornRunnerBlurhash::encode($pixels, $components_x, $components_y);
            \Craft::$app->cache->set($cacheKey, $blurhash, 60 * 60 * 24 * 7 * 4); // Cache for approx 1 month
            return $blurhash;
        }

    }


    /**
     * blurhashToImage: Take a blurhash string and decode it, returning a gdimage resource.
     *
     * @param blurhash String
     *
     * @return image
     */
    private function blurhashToImage($blurhash, $asset = false)
    {
        // Check we're given a string.
        if (!is_string($blurhash)) {
            return false;
        }

        // Make sure it is an asset object
        if ($asset !== false && $asset instanceof Asset) {
            $blurredImageWidth = round(BlurHash::getInstance()->getSettings()->blurredMaxImageSize * ($asset->width > $asset->height ? 1 : $asset->width / $asset->height));
            $blurredImageHeight = round(BlurHash::getInstance()->getSettings()->blurredMaxImageSize * ($asset->height > $asset->width ? 1 : $asset->height / $asset->width));
        } else {
            $blurredImageWidth = BlurHash::getInstance()->getSettings()->blurredMaxImageSize;
            $blurredImageHeight = BlurHash::getInstance()->getSettings()->blurredMaxImageSize;
        }
        
        // Set unique cacheKey
        $cacheKey = 'blurhashimagedata-' . $blurhash . $blurredImageWidth . $blurredImageHeight;
        $cachedValue = \Craft::$app->cache->get($cacheKey);

        // Check for cached value
        if ($cachedValue) {
            return $cachedValue;
            
        } else {

            // Decode the blurhash to an image file.
            $pixels = KornRunnerBlurhash::decode($blurhash, $blurredImageWidth, $blurredImageHeight);
            $decodedImage  = imagecreatetruecolor($blurredImageWidth, $blurredImageHeight);
            for ($y = 0; $y < $blurredImageHeight; ++$y) {
                for ($x = 0; $x < $blurredImageWidth; ++$x) {
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
     * averageColor: Take a Craft CMS asset or a blurhash string and return its average color.
     *
     * @param source Asset or String
     *
     * @return string
     */
    public function averageColor($source)
    { 
        $isAsset = $source instanceof Asset;
        $isString = is_string($source);

        // If we've not got a string or an asset, return the original value
        if (!$isAsset && !$isString) {
            return $source;
        }

        // If it's an asset
        if ($isAsset) {
            // Check it's valid
            if (!$this->validAsset($source)) {
                return new ColorData("#000000");
            }
            $blurhash = $this->blurhashEncode($source);
        }
          
        // If we've just got a string then continue with that
        if ($isString) {
            $blurhash = $source;
        }
        
        // Set unique cacheKey
        $cacheKey = 'blurhashavereagecolor-' . $blurhash;
        $cachedValue = \Craft::$app->cache->get($cacheKey);

        // Check for cached value
        if ($cachedValue) {
            return new ColorData($cachedValue);
            
        } else {
            // Attempt to decode as means of validation
            try {
                $pixels = KornRunnerBlurhash::decode($blurhash, BlurHash::getInstance()->getSettings()->blurredMaxImageSize, BlurHash::getInstance()->getSettings()->blurredMaxImageSize);
            } catch (KornRunnerBlurhash $e) {
                Craft::error('An error occured trying to decode the BlurHash string: ' . $e->getMessage(), __METHOD__);
                return null;
            }

            $averageColor = substr($blurhash, 2, 4);
            $srgb = Base83::decode($averageColor);
            $hex = "#" . dechex($srgb);

            $value = ColorValidator::normalizeColor($hex);
            \Craft::$app->cache->set($cacheKey, $value, 60 * 60 * 24 * 7 * 4); // Cache for approx 1 month
            
            return new ColorData($value);
        }

    }


    /**
     * imageToUri: Takes an image resource and converts it to a data URI string.
     *
     * @param image String
     *
     * @return string
     */
    private function imageToUri($image)
    {   
        return sprintf('data:%s;base64,%s', 'image/png', base64_encode($image));
    }
    


    /**
     * memoryInfo: Take a Craft CMS asset and return the estimated memory required to load it in to memory, as well as the current memory usage.
     *
     * @param asset Asset
     *
     * @return string
     */
    public function memoryInfo($asset, $infoType = '')
    {

        // Check it's a valid asset resource
        if (!$this->validAsset($asset)) {
            return 'unknown';
        }

        // Check we've recieved a valid info type
        if (!in_array($infoType, $this->allowedInfoTypes)) {
            $infoType = $this->DEFAULT_INFO_TYPE;
        }
        
        $assetSize = @getimagesize($asset->getCopyOfFile()); 
        if (isset($assetSize['bits'])) {
            $estMemoryRequired = round(($assetSize[0] * $assetSize[1] * $assetSize['bits'] * (isset($assetSize['channels']) ? $assetSize['channels'] : 3) / 8 + Pow(2, 16)) * 1.65);
            switch ($infoType) {
                case "required":
                    return strval($estMemoryRequired);
                    break;
                case "usage":
                    return memory_get_usage();
                    break;
            }
        } else {
            return 'unknown';
        }
    }
    
    
}
