<?php
/**
 * BlurHash plugin for Craft CMS 4.x
 *
 * Render a BlurHash from a given image.
 *
 * @link      https://dodeca.studio
 * @copyright Copyright (c) 2021 Dodeca Studio
 */

namespace dodecastudio\blurhash\twigextensions;

use dodecastudio\blurhash\BlurHash;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class BlurHashTwigExtension extends AbstractExtension
{
    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'Craft BlurHash';
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
            new TwigFilter('averageColor', [$this, 'averageColor']),
            new TwigFilter('memoryInfo', [$this, 'memoryInfo']),
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
            new TwigFunction('averageColor', [$this, 'averageColor']),
            new TwigFilter('memoryInfo', [$this, 'memoryInfo']),
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
        return BlurHash::getInstance()->blurHashServices->blurhash($asset);
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
        return BlurHash::getInstance()->blurHashServices->blurhashToUri($blurhash);
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
        return BlurHash::getInstance()->blurHashServices->blurhashEncode($asset);
    }


    /**
     * averageColor: Take a Craft CMS asset or a Craft CMS asset and return its average color.
     *
     * @param source Asset or String
     *
     * @return image
     */
    public function averageColor($source)
    {
        return BlurHash::getInstance()->blurHashServices->averageColor($source);
    }


    /**
     * memoryInfo: Return some information about memory usage.
     *
     * @param asset Asset
     * @param infoType String
     *
     * @return string
     */
    public function memoryInfo($asset, $infoType = '') {
        return BlurHash::getInstance()->blurHashServices->memoryInfo($asset, $infoType);
    }
}