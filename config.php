<?php
/**
 * BlurHash plugin for Craft CMS 4.x
 *
 * Render a BlurHash from a given image.
 *
 * @link      https://dodeca.studio
 * @copyright Copyright (c) 2021 Dodeca Studio
 */

/**
 * BlurHash config.php
 *
 * This file is a template for the BlurHash settings.
 * 
 * To use it, copy it to 'craft/config/' and rename to 'blur-hash.php'. Make any changes you wish to there.
 * 
 * For more information, see the Craft Docs here:
 * https://craftcms.com/docs/3.x/extend/plugin-settings.html#overriding-setting-values
 * 
 */

return [

  /**
   * @var int The maximum size in pixels (width or height) of the blurhash image that is output.
   * 
   * Important: Changing this will affect the size and quality of the output image. However, its performance impacts are two-fold, as it will affect both the amount of memory required to generate the image, as well as the resulting file size for clients loading the image. Edit with caution!
   * The recommended size is 64. Larger sizes may not necessarily create "better looking" blur hash images.
   * 
   */
  'blurredMaxImageSize' => 64,

  /**
   * @var int The maximum size of the resized sample. To generate a blurhash, the original image is loaded in to memory, copied and resized, then each pixel is sent to the blurhash algorithm. This determines the maximum size of the copied image.
   * 
   * Important: Increasing it from the default value will increase memory usage significantly, so edit with caution.
   * The recommended size is 64. Reduce this to reduce memory usage. Larger sample sizes make only tiny visual differences to the output image.
   * 
   */
  'sampleMaxImageSize' => 64,
  
  /**
   * @var array An array of bitmap mime-types allowed by blurhash. 
   */
  'allowedFileTypes' => ['image/jpeg', 'image/png', 'image/webp'],
];