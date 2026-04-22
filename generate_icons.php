<?php
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$src   = __DIR__ . '/public/images/logo.png';
$dir   = __DIR__ . '/public/images/icons';

if (!is_dir($dir)) mkdir($dir, 0755, true);

$info = getimagesize($src);
$orig = imagecreatefrompng($src);

foreach ($sizes as $s) {
    $dst = imagecreatetruecolor($s, $s);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefilledrectangle($dst, 0, 0, $s, $s, $transparent);
    imagecopyresampled($dst, $orig, 0, 0, 0, 0, $s, $s, $info[0], $info[1]);
    imagepng($dst, "$dir/icon-{$s}x{$s}.png");
    imagedestroy($dst);
    echo "Created icon-{$s}x{$s}.png\n";
}
imagedestroy($orig);
echo "Done\n";
