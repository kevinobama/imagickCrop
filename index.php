<?php
require('Series_Template.php');
$options=[
    'output'=> "scene/scene.jpg",                              // output到的地址
    'background' => 'source/three-panel-bg.png',                                  // 背景图片地址
    'img' => "source/three-panel-test.jpg",                                 // 图片地址
    'x' => 49.92,
    'y' => 33.83,
    'sub_width' => 97.50,
    'frame_width' => 0.00,
    'frame_color' => null,
    'gap_width' => 0.83,
    'name' => 'CVS_Portrait_2:3_3',
];

Series_Template::combine_design($options);
//print_r($options);
error_log(json_encode($options,JSON_PRETTY_PRINT));