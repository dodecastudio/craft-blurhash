<?php

namespace dodecastudio\blurhash\models;

use craft\base\Model;

class Settings extends Model
{
    // Allowed image types.
    public $allowedFileTypes = ['image/jpeg', 'image/png', 'image/webp'];
    
    // Size of blurred image. Smaller is better for performance.
    public $blurredImageWidth = 64;
    public $blurredImageHeight = 64;

    public function rules(): array
    {
        return [
            ['allowedFileTypes', 'required'],
            ['blurredImageWidth', 'required'],
            ['blurredImageHeight', 'required'],
        ];
    }
}
