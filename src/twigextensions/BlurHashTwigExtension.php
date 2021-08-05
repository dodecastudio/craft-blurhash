<?php
/**
 * BlurHash plugin for Craft CMS 3.x
 *
 * Render a BlurHash from a given image.
 *
 * @link      https://dodeca.studio
 * @copyright Copyright (c) 2021 Dodeca Studio
 */

namespace dodecastudio\blurhash\twigextensions;

use dodecastudio\blurhash\BlurHash;
use craft\elements\Asset;
use craft\models\AssetTransform;

use Craft;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use kornrunner\Blurhash\Blurhash as KornRunnerBlurhash;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    Dodeca Studio
 * @package   BlurHash
 * @since     1.0.0
 */
class BlurHashTwigExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'BlurHash';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *
     *      {{ 'something' | someFilter }}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('blurhash', [$this, 'blurhashEncode']),
        ];
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     *blurhash
    * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('blurhash', [$this, 'blurhash']),
            new TwigFunction('blurhashToUri', [$this, 'blurhashToUri']),
        ];
    }


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

        // Make sure the asset is an image.
        if (0 !== strpos($asset->getMimeType(), 'image/')) {
            return false;
        }

        // Set unique cacheKey
        $cacheKey = 'blurhashstring-' . $asset->id . $asset->dateModified->format('YmdHis') . '128';
        $cachedValue = \Craft::$app->cache->get($cacheKey);

        // Check for cached value
        if ($cachedValue) {
            return $cachedValue;
            
        } else {

            // Generate new image
            $sampleSize = 128;

            $thumbnailImage = imagecreatetruecolor($sampleSize, $sampleSize);
            imagecopyresampled($thumbnailImage, imagecreatefromstring($asset->getContents()), 0, 0, 0, 0, $sampleSize, $sampleSize, $asset->width, $asset->height);
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
            $components_x = 3;
            $components_y = 3;
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

        // Set unique cacheKey
        $cacheKey = 'blurhashimagedata-' . $blurhash . '64';
        $cachedValue = \Craft::$app->cache->get($cacheKey);

        // Check for cached value
        if ($cachedValue) {
            return $cachedValue;
            
        } else {

            // Set size of returned image. Keep it small!
            $width = 64;
            $height = 64;

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
    function imageToUri($image)
    {   
        return sprintf('data:%s;base64,%s', 'image/png', base64_encode($image));
    }
}