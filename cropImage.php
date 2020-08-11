<?php
function cropImagebak($imagePath, $startX, $startY) {
    $panel = 3;
    $imagick = new Imagick(realpath($imagePath));

    $size = $imagick->getImageGeometry();
    $height = $size['height'];
    $width = $size['width']/$panel;
    error_log($width);

    $panelCount=1;

    while ($panelCount<=3) {
        $imagick->cropImage($width, $height, $startX, $startY);

        $imagick->writeImage('scene/artwork/three-panel-'.$panelCount.'.jpg');

        $panelCount++;
        $startX +=$width;
        $startY +=$width;
    }

    $imagick->destroy();
}

function cropImage($imagePath, $startX, $startY,$panelCount=1) {
    $panel = 3;
    $imagick = new Imagick(realpath($imagePath));

    $size = $imagick->getImageGeometry();
    $height = $size['height'];
    $width = $size['width']/$panel;
    error_log($width);

    $imagick->cropImage($width, $height, $startX, $startY);
    $imagick->writeImage('scene/artwork/three-panel-'.$panelCount.'.jpg');

    $imagick->destroy();
}

$startX=653.66666666667*0;
$startY=0;

cropImage('source/three-panel-test.jpg', $startX, $startY);
cropImage('source/three-panel-test.jpg', 653.66666666667*1, 0,2);
cropImage('source/three-panel-test.jpg', 653.66666666667*2, 0,3);

//$thumb = new Imagick($file)
//$thumb->resizeImage($r_w1,$r_h1,Imagick::FILTER_CATROM,0.9, false);
//$thumb->cropImage($w1,$h1,$l1,$t1);
//$thumb->writeImage($destinationPath.'/'.$fileName);
