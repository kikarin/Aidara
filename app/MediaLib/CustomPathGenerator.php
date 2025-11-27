<?php

namespace App\MediaLib;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

class CustomPathGenerator extends DefaultPathGenerator
{
    public function getPath(Media $media): string
    {
        if ($media->model_type == 'App\\Models\\User') {
            return 'user/'.$media->getKey().'/';
        }
        if ($media->model_type == 'App\\Models\\Atlet') {
            return 'atlet/'.$media->getKey().'/';
        }
        if ($media->model_type == 'App\\Models\\AtletSertifikat') {
            return 'atlet_sertifikat/'.$media->getKey().'/';
        }

        return $media->id.'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive/';
    }
}
