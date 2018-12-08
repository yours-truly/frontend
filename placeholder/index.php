<?php

// Config
$defaultWidth = 600;
$defaultHeight = 600;
$defaultBgColor = 'ccc';
$defaultFont = 'arial';
$gridSize = 40;
$expiresInDays = 14;

// PHP config
putenv('GDFONTPATH=' . realpath('.'));
ini_set('memory_limit', -1);

function createImage() {
	global $width, $height, $bgColor;
	$image = @imagecreate($width, $height) or die ('The image could not be created.');
	$background = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
	return $image;
}

function imageTTFCenter($image, $txt, $font, $fontSize) {
	// Find the size of the image
	$xi = imagesx($image);
	$yi = imagesy($image);

	// Find the size of the text
	$box = imagettfbbox($fontSize, 0, $font, $txt);
	$xr = abs(max($box[2], $box[4]));
	$yr = abs(max($box[5], $box[7]));

	// Compute centering
	$x = intval(($xi-$xr) / 2);
	$y = intval(($yi+$yr) / 2);
	return array($x, $y);
}

function addTextToImage($font) {
	global $image, $width, $height, $bgColor, $textColor, $text;

	$textColor = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
	$size = $width / 100*9;
	list($x, $y) = imageTTFCenter($image, $text, $font, $size);
	imagettftext($image, $size, 0, $x, $y, $textColor, $font, $text);
}

function getContrastYIQ($hexcolor) {
	$r = hexdec($hexcolor[0]);
	$g = hexdec($hexcolor[1]);
	$b = hexdec($hexcolor[2]);
	$yiq = (($r*299) + ($g*587) + ($b*114)) / 1000;
	return ($yiq >= 128) ? 'black' : 'white';
}

$width = isset($_GET['width']) ? $_GET['width'] : $defaultWidth;
$height = isset($_GET['height']) ? $_GET['height'] : $defaultHeight;
$bgColor = isset($_GET['bgColor']) ? $_GET['bgColor'] : $defaultBgColor;
$textColor = isset($_GET['textColor']) ? $_GET['textColor'] : NULL;
$font = isset($_GET['font']) ? $_GET['font'] : $defaultFont;
$text = isset($_GET['text']) ? $_GET['text'] : "{$width}x{$height}";
$style = isset($_GET['style']) && $_GET['style'] != 'style' ? $_GET['style'] : false;

$font = file_exists("$font.ttf") ? "$font.ttf" : "arial.ttf";

// Convert HEX{3} to HEX{6}
$textColor = (strlen($textColor) == 3) ? $textColor . $textColor : $textColor;
$bgColor = (strlen($bgColor) == 3) ? $bgColor . $bgColor : $bgColor;

// Convert HEX to RGB value
$textColor = sscanf($textColor, '%2x%2x%2x');
$bgColor = sscanf($bgColor, '%2x%2x%2x');

// Calculate by contrast if color is not defined
$textColor = isset($textColor) ? $textColor : ((getContrastYIQ($bgColor) == 'black') ? array(0, 0, 0) : array(255, 255, 255));

$image = createImage();

$lineColor = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
imagesetthickness($image, 1);

$gridSize = max($width / 15, $gridSize);

if ($style == 'grid') {
	// Draw grid overlay
	for ($i=0; $i < $width; $i++) {
		$i = $i == 0 ? $gridSize/2 : $i + $gridSize;
		imageline($image, $i, 0, $i, $height, $lineColor);
	}
	for ($i=0; $i < $height; $i++) {
		$i = $i == 0 ? $gridSize/2 : $i + $gridSize;
		imageline($image, 0, $i, $width, $i, $lineColor);
	}
} else if(!$style) {
	// Draw the cross overlay
	imageline($image, $width, $height, 0, 0, $lineColor);
	imageline($image, $width, 0, 0, $height, $lineColor);
}

addTextToImage($font);

header('Content-Type: image/gif');

// Set image cache
$expires = 60*60*24 * $expiresInDays;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');

imagegif($image);
imagedestroy($image);
imagedestroy($pattern);
?>
