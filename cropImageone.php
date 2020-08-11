<?php
function cropImage($imagePath, $panel=3) {
    if($panel <=1) throw new Exception('no need to crop.');

    $imagick = new Imagick(realpath($imagePath));

    $size = $imagick->getImageGeometry();
    $height = $size['height'];
    $width = $size['width']/$panel;

    $panelCount=0;

    while ($panelCount<3) {
        $imagick = new Imagick(realpath($imagePath));
        $imagick->cropImage($width, $height, $width*$panelCount, 0);
        $imagick->writeImage('scene/artwork/three-panel-'.($panelCount+1).'.jpg');
        $panelCount++;
    }

    $imagick->destroy();
}

cropImage('source/three-panel-test.jpg');