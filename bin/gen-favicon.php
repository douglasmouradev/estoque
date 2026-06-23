<?php

declare(strict_types=1);

/**
 * Gera favicons a partir da logo (PNG embutido em ICO — compatível com Chrome/Edge).
 */

$root = dirname(__DIR__);
$src = $root . '/public/assets/img/logo-oficina.png';

if (!extension_loaded('gd')) {
    fwrite(STDERR, "Extensão GD não disponível.\n");
    exit(1);
}

$img = imagecreatefrompng($src);
if ($img === false) {
    fwrite(STDERR, "Não foi possível abrir a logo.\n");
    exit(1);
}

/** @param resource $image */
function savePng($image, string $dest, int $size): void
{
    $scaled = imagescale($image, $size, $size, IMG_BILINEAR_FIXED);
    if ($scaled === false) {
        return;
    }
    imagesavealpha($scaled, true);
    imagepng($scaled, $dest, 9);
}

/** ICO com um ou mais PNGs embutidos (formato Windows Vista+). */
function buildIco(array $pngPaths, string $dest): void
{
    $count = count($pngPaths);
    $header = pack('vvv', 0, 1, $count);
    $entries = '';
    $data = '';
    $offset = 6 + (16 * $count);

    foreach ($pngPaths as $path) {
        $png = file_get_contents($path);
        if ($png === false) {
            continue;
        }
        $size = getimagesize($path);
        $w = $size[0] ?? 32;
        $h = $size[1] ?? 32;
        $wb = $w >= 256 ? 0 : $w;
        $hb = $h >= 256 ? 0 : $h;

        $entries .= pack(
            'CCCCvvVV',
            $wb,
            $hb,
            0,
            0,
            1,
            32,
            strlen($png),
            $offset
        );
        $data .= $png;
        $offset += strlen($png);
    }

    file_put_contents($dest, $header . $entries . $data);
}

$sizes = [
    16 => $root . '/public/assets/img/favicon-16.png',
    32 => $root . '/public/assets/img/favicon-32.png',
    48 => $root . '/public/assets/img/favicon-48.png',
    180 => $root . '/public/assets/img/apple-touch-icon.png',
];

foreach ($sizes as $size => $dest) {
    savePng($img, $dest, $size);
}

buildIco(
    [$sizes[16], $sizes[32], $sizes[48]],
    $root . '/public/favicon.ico'
);

copy($sizes[32], $root . '/public/favicon.png');

echo "Favicons gerados (ICO válido).\n";
