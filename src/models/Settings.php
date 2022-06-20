<?php

namespace dodecastudio\blurhash\models;

use craft\base\Model;

class Settings extends Model
{
    
    // Size of blurred image. Smaller is better for performance.
    public $blurredMaxImageSize = 64;

    // Size of the sample image. Smaller is better for performance.
    public $sampleMaxImageSize = 64;

    // Allowed image types.
    public $allowedFileTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/tiff', 'image/bmp', 'image/gif'];

    public function rules(): array
    {
        return [
            ['blurredMaxImageSize', 'required'],
            ['sampleMaxImageSize', 'required'],
            ['allowedFileTypes', 'required'],
        ];
    }
}