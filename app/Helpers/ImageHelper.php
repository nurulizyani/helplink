<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    public static function url($path)
    {
        if (!$path) {
            return asset('images/placeholder.png');
        }

        $path = trim($path);
        $path = ltrim($path, '/');

        // normalize old data
        $path = preg_replace('#^storage/#', '', $path);
        $path = preg_replace('#^public/#', '', $path);

        return Storage::url($path);
    }
}

