<?php

namespace dodecastudio\blurhash\models;

use craft\base\Model;

class Settings extends Model
{
    // Allowed image types.
    public $allowedFileTypes = ['image/jpeg', 'image/png', 'image/webp'];

    // Allowed info types
    public $allowedInfoTypes = ['required', 'usage'];
    public $DEFAULT_INFO_TYPE = 'required';
    
    // Size of blurred image. Smaller is better for performance.
    public $blurredImageWidth = 64;
    public $blurredImageHeight = 64;

    public function rules()
    {
        return [
            ['allowedFileTypes', 'required'],
            ['blurredImageWidth', 'required'],
            ['blurredImageHeight', 'required'],
            ['allowedInfoTypes', 'required'],
        ];
    }
}
