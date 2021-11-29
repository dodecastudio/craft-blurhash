<?php

namespace dodecastudio\blurhash\models;

use craft\base\Model;

class Settings extends Model
{
    public $allowedFileTypes = ['image/jpeg', 'image/png', 'image/webp'];

    public function rules()
    {
        return [
            ['allowedFileTypes', 'required'],
        ];
    }
}
