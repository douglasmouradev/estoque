<?php
/** @var callable(string): string $asset */
$public = dirname(__DIR__, 4) . '/public';
$v = static function (string $file) use ($public): string {
    $path = $public . '/' . ltrim($file, '/');
    return is_file($path) ? (string) filemtime($path) : '1';
};
$icon16 = htmlspecialchars($asset('assets/img/favicon-16.png')) . '?v=' . $v('assets/img/favicon-16.png');
$icon32 = htmlspecialchars($asset('assets/img/favicon-32.png')) . '?v=' . $v('assets/img/favicon-32.png');
$icon48 = htmlspecialchars($asset('assets/img/favicon-48.png')) . '?v=' . $v('assets/img/favicon-48.png');
$apple = htmlspecialchars($asset('assets/img/apple-touch-icon.png')) . '?v=' . $v('assets/img/apple-touch-icon.png');
$icoV = $v('favicon.ico');
?>
<link rel="icon" href="/favicon.ico?v=<?= $icoV ?>" sizes="any">
<link rel="icon" type="image/png" sizes="16x16" href="<?= $icon16 ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= $icon32 ?>">
<link rel="icon" type="image/png" sizes="48x48" href="<?= $icon48 ?>">
<link rel="shortcut icon" href="/favicon.ico?v=<?= $icoV ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?= $apple ?>">
