<?php
/**
 * BlurHash plugin for Craft CMS
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
    private $fileExistsResults = [];

    // Cached in place of a blurhash when one can't be generated (contains a space, so can't be a valid blurhash)
    private const FAILED = 'no blurhash';

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
     * @param checkFileExists Bool 
     *
     * @return bool
     */
    private function validAsset($asset, $checkFileExists = true) {

        // Make sure it is an asset object
        if (!$asset instanceof Asset) {
            return false;
        }

        // Ensure it's a supported file type
        if (!in_array($asset->mimeType, BlurHash::getInstance()->getSettings()->allowedFileTypes)) {
            return false;
        }

        // Should we check that the file exists?
        if ($checkFileExists && BlurHash::getInstance()->getSettings()->checkFileExists) {
            return $this->fileExists($asset);
        }

        return true;

    }


    /**
     * fileExists: Check an asset's file exists, remembering the result for the rest of the request
     *
     * @param asset Asset
     *
     * @return bool
     */
    private function fileExists($asset) {

        $key = $asset->id . '-' . $asset->path;
        if (!isset($this->fileExistsResults[$key])) {
            $this->fileExistsResults[$key] = true;
            if (method_exists($asset, 'getVolume')) {
                if (method_exists($asset->getVolume(), 'fileExists')) {
                    $this->fileExistsResults[$key] = $asset->getVolume()->fileExists($asset->path);
                }
            }
        }

        return $this->fileExistsResults[$key];

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

        // Check it's a valid asset resource. 
        // Only check on a cache miss (on remote volumes that is a network request for every asset)
        if (!$this->validAsset($asset, false)) {
            return false;
        }

        // Set size of copied / sampled image
        $sampleSize = BlurHash::getInstance()->getSettings()->sampleMaxImageSize;

        // Set unique cacheKey
        $date = $asset->dateModified ?? $asset->dateUpdated;
        $dateModified = $date ? $date->format('YmdHis') : '';
        $cacheKey = 'blurhashstring-' . $asset->id . $dateModified . $sampleSize;
        $cachedValue = \Craft::$app->cache->get($cacheKey);

        // Check for cached value
        if ($cachedValue) {
            return $cachedValue === self::FAILED ? false : $cachedValue;

        } else {
            // About to read the file, so make sure it's there
            if (!$this->validAsset($asset)) {
                return $this->cacheFailure($cacheKey);
            }

            $typesToTransform = ["image/heic", "image/tiff"];
            $sourceImage = null;
            $thumbnailImage = null;

            try {
                $sampleImageWidth = (int)round($sampleSize * ($asset->width > $asset->height ? 1 : $asset->width / $asset->height));
                $sampleImageHeight = (int)round($sampleSize * ($asset->height > $asset->width ? 1 : $asset->height / $asset->width));

                $thumbnailImage = imagecreatetruecolor($sampleImageWidth, $sampleImageHeight);

                $assetContents = $asset->getContents();

                if (in_array($asset->mimeType, $typesToTransform)) {
                    if (!class_exists('Imagick')) {
                        Craft::error('Imagick is required to generate a BlurHash for ' . $asset->mimeType . ' images: ' . $asset->filename, __METHOD__);
                        return $this->cacheFailure($cacheKey);
                    }
                    $imagick = new \Imagick();
                    $imagick->readImageBlob($assetContents);
                    $imagick->setImageFormat('png');
                    $assetContents = $imagick->getImageBlob();
                }

                $sourceImage = @imagecreatefromstring($assetContents);
                if (!$sourceImage) {
                    Craft::error('An error occurred trying to read the image ' . $asset->filename . ' to generate a BlurHash.', __METHOD__);
                    return $this->cacheFailure($cacheKey);
                }

                imagecopyresampled($thumbnailImage, $sourceImage, 0, 0, 0, 0, $sampleImageWidth, $sampleImageHeight, $asset->width, $asset->height);

                // Clean up source image immediately to free memory
                $this->destroyImage($sourceImage);

                $width = imagesx($thumbnailImage);
                $height = imagesy($thumbnailImage);

                // Get colors for image
                $pixels = [];
                for ($y = 0; $y < $height; ++$y) {
                    $row = [];
                    for ($x = 0; $x < $width; ++$x) {
                        $rgb = imagecolorat($thumbnailImage, $x, $y);
                        $row[] = [
                            ($rgb >> 16) & 0xFF,
                            ($rgb >> 8) & 0xFF,
                            $rgb & 0xFF
                        ];
                    }
                    $pixels[] = $row;
                }

                $this->destroyImage($thumbnailImage);

                // Generate a blurhash from the image data.
                $components_x = (int)($asset->width > $asset->height ? 6 : ceil(6 * ($asset->width / $asset->height)));
                $components_y = (int)($asset->width < $asset->height ? 6 : ceil(6 * ($asset->height / $asset->width)));

                $blurhash = KornRunnerBlurhash::encode($pixels, $components_x, $components_y);
                \Craft::$app->cache->set($cacheKey, $blurhash, 0); // Cache until the asset changes

                return $blurhash;
            } catch (\Throwable $e) {
                Craft::error('An error occurred trying to generate a BlurHash for ' . $asset->filename . ': ' . $e->getMessage(), __METHOD__);
                return $this->cacheFailure($cacheKey);
            } finally {
                $this->destroyImage($sourceImage);
                $this->destroyImage($thumbnailImage);
            }
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
        $sampleSize = BlurHash::getInstance()->getSettings()->blurredMaxImageSize;

        // Make sure it is an asset object
        if ($asset instanceof Asset) {
            $blurredImageWidth = round($sampleSize * ($asset->width > $asset->height ? 1 : $asset->width / $asset->height));
            $blurredImageHeight = round($sampleSize * ($asset->height > $asset->width ? 1 : $asset->height / $asset->width));
        } else {
            $blurredImageWidth = $sampleSize;
            $blurredImageHeight = $sampleSize;
        }
        
        // Set unique cacheKey
        $cacheKey = 'blurhashimagedata-' . $blurhash . $blurredImageWidth . $blurredImageHeight;
        $cachedValue = \Craft::$app->cache->get($cacheKey);

        // Check for cached value
        if ($cachedValue) {
            return $cachedValue;
        }

        // Decode the blurhash to an image file.
        $pixels = KornRunnerBlurhash::decode($blurhash, (int)$blurredImageWidth, (int)$blurredImageHeight);
        $decodedImage = imagecreatetruecolor((int)$blurredImageWidth, (int)$blurredImageHeight);

        // Disable alpha blending for faster raw pixel writing
        imagealphablending($decodedImage, false);

        for ($y = 0; $y < $blurredImageHeight; ++$y) {
            for ($x = 0; $x < $blurredImageWidth; ++$x) {
                [$r, $g, $b] = $pixels[$y][$x];
                
                // Use bit-shifting for color calculation
                $color = ($r << 16) | ($g << 8) | $b;
                imagesetpixel($decodedImage, $x, $y, $color);
            }
        }

        // Render the image and return it.
        ob_start(); 
        // Set PNG compression level to 6 (default) but keep PNG for consistency.
        imagepng($decodedImage);
        $decodedImageData = ob_get_clean();
        
        \Craft::$app->cache->set($cacheKey, $decodedImageData, 0);
        
        $this->destroyImage($decodedImage); // Clean up memory immediately
        return $decodedImageData;

    }


    /**
     * cacheFailure: Remember that a blurhash couldn't be generated, so it isn't retried on every request.
     *
     * @param cacheKey String
     *
     * @return bool
     */
    private function cacheFailure($cacheKey)
    {
        \Craft::$app->cache->set($cacheKey, self::FAILED, 60 * 60 * 24); // Cache for 1 day
        return false;
    }


    /**
     * destroyImage: Free a GD image's memory straight away. Only needed on PHP < 8.
     *
     * @param image GD image
     *
     * @return void
     */
    private function destroyImage(&$image)
    {
        if (PHP_VERSION_ID < 80000 && is_resource($image)) {
            imagedestroy($image);
        }
        $image = null;
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
            // Encode only if necessary
            $blurhash = $this->blurhashEncode($source);
            if (!$blurhash) {
                return new ColorData(BlurHash::getInstance()->getSettings()->defaultAverageColor);
            }
        }
        
        // If we've just got a string then continue with that
        if ($isString) {
            $blurhash = $source;
        }
        
        try {
            // The average color is encoded in the first few characters of the string
            $averageColor = substr($blurhash, 2, 4);
            $srgb = Base83::decode($averageColor);

            $hex = "#" . str_pad(dechex($srgb), 6, "0", STR_PAD_LEFT);

            return new ColorData(ColorValidator::normalizeColor($hex));
        } catch (\Exception $e) {
            Craft::error('An error occurred trying to parse the BlurHash string: ' . $e->getMessage(), __METHOD__);
            return null;
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
