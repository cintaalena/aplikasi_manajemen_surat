<?php
$dir = __DIR__ . '/public/images';

$wide = imagecreatetruecolor(1280, 720);
$bg = imagecolorallocate($wide, 124, 58, 237);
imagefilledrectangle($wide, 0, 0, 1280, 720, $bg);
imagepng($wide, "$dir/screenshot-wide.png");
imagedestroy($wide);

$mob = imagecreatetruecolor(390, 844);
$bg2 = imagecolorallocate($mob, 124, 58, 237);
imagefilledrectangle($mob, 0, 0, 390, 844, $bg2);
imagepng($mob, "$dir/screenshot-mobile.png");
imagedestroy($mob);

echo "Screenshots created\n";
