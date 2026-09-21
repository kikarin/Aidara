<?php

declare(strict_types=1);

$publicDir = dirname(__DIR__) . '/public';

$sources = [
    'favicon-96x96.png' => [16, 32],
    'apple-touch-icon.png' => [150],
];

if (! extension_loaded('gd')) {
    fwrite(STDERR, "PHP GD extension is required.\n");
    exit(1);
}

foreach ($sources as $sourceFile => $sizes) {
    $source = $publicDir . '/' . $sourceFile;

    if (! is_file($source)) {
        fwrite(STDERR, "Source not found: {$source}\n");
        exit(1);
    }

    $image = imagecreatefrompng($source);

    if ($image === false) {
        fwrite(STDERR, "Failed to load source image: {$source}\n");
        exit(1);
    }

    imagealphablending($image, false);
    imagesavealpha($image, true);

    foreach ($sizes as $size) {
        $resized = imagescale($image, $size, $size, IMG_BICUBIC);

        if ($resized === false) {
            fwrite(STDERR, "Failed to resize {$sourceFile} to {$size}x{$size}.\n");
            imagedestroy($image);
            exit(1);
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        $target = match ($size) {
            16 => $publicDir . '/favicon-16x16.png',
            32 => $publicDir . '/favicon-32x32.png',
            150 => $publicDir . '/mstile-150x150.png',
            default => $publicDir . "/icon-{$size}x{$size}.png",
        };

        if (! imagepng($resized, $target)) {
            fwrite(STDERR, "Failed to write {$target}.\n");
            imagedestroy($resized);
            imagedestroy($image);
            exit(1);
        }

        imagedestroy($resized);
        echo "Created {$target}\n";
    }

    imagedestroy($image);
}
