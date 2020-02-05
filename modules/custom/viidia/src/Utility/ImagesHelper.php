<?php

namespace Drupal\viidia\Utility;

class ImagesHelper
{
    public function __construct()
    {
    }

    public function getImageUrl($imageField, $size = 'large')
    {
        if (!empty($imageField) && !empty($imageField->entity)) {
            $imageUri = $imageField->entity->getFileUri();
            return $imageUri ? \Drupal\image\Entity\ImageStyle::load($size)->buildUrl($imageUri) : '';
        }

        return '';
    }
}