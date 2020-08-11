
<?php
class Series_Template  {

    static protected $_model=['table'=>'series_template'];

    public $id;
    public $name;
    public $sku;
    public $size;
    public $panel;
    public $frame_color;
    public $type;
    public $last_update;

    // 换算用的，实际是%来的, 所以要/100
    public $x_scale, $y_scale, $sub_width, $frame_width, $gap_width;

    // 防止他们选错
    public static $SKUS=[
        'CVS', 'FCV', 'FPM', 'WFH', 
    ];

    public static $SIZES=[
        'Landscape', 'Portrait', 'Square', 
    ];

    public static $PANELS=[
        '1', '2', '3', 
    ];

    public static $FRAME_COLORS=[
        'Black', 'White', 'Wood', 
    ];

    public static $TYPES=[
        '3D', 'Flat', 'Scene', 
    ];

    public static function get_template_by($symbol, $type='Scene') {
        list($sku, $size, $scale, $panel) = explode("_", $symbol);
        $cond=sprintf("sku='%s' and size='%s' and panel=%d and type='%s'", $sku, $size, $panel, $type);
        return self::find_all(["$cond", "order by id asc"]);
    }

    public function has_template() {
        return file_exists(sprintf("%s/%s.png", DIR_SERIES_TEMPLATES, $this->id));
    }

    public function get_template() {
        if($this->has_template()){
            return url_path(sprintf("%s/%s.png", DIR_SERIES_TEMPLATES, $this->id));
        }
        return null;
    }

    public function delete_template() {
        @unlink(sprintf("%s/%s.png", DIR_SERIES_TEMPLATES, $this->id));
    }

    public function upload_template($path) {
        $folder = DIR_SERIES_TEMPLATES;
        if(!is_dir($folder)) @mkdir($folder);

        move_uploaded_file($path, sprintf("%s/%d.%s", $folder, $this->id, 'png'));
    }

    // 生成合成scene图
    public static function render_design_scene($options) {
        // 必要的一些参数
        $output = $options['output'];           // output到的地址
        $background = $options['background'];       // 背景图片地址
        $img = $options['img'];                 // 图片地址
        $x_scale = $options['x'];
        $y_scale = $options['y'];
        $sub_width = $options['sub_width'];
        $frame_width = $options['frame_width'];
        $gap_width = $options['gap_width'];
        $frame_color = $options['frame_color'] ?: "black";
        list($sku, $size, $scale, $panel) = explode("_", $options['name']);     // Ex: CVS_Portrait_2:3_1
        // 没有地址，退出
        if (!$output) return null;

        // 建立场景
        $bg = self::create_imagick(0, 0, $background);
        $scene = self::create_imagick($bg->getImageWidth(), $bg->getImageHeight(), null, 'white');
        $scene->setImageFormat('png'); 
        // 图片的width和height
        $w = $scene->getImageWidth();
        $h = $scene->getImageHeight();
        // 场景中心坐标
        $cx = $w * $x_scale / 100;
        $cy = $h * $y_scale / 100;
        $sub_width = $w * $sub_width / 100;         // 场景中心总宽
        $frame_width = $w * $frame_width / 100;     // frame宽度, 如果有frame的话
        $gap_width = $w * $gap_width / 100;         // gap宽度
        // 用每块panel的长度从比例里找出高度, 保留小数点
        list($w_scale, $h_scale) = explode(":", $scale);
        $panel_width = sprintf("%.1f", ($sub_width / $panel));        
        $panel_height = sprintf("%.1f", (($panel_width / $w_scale) * $h_scale));
        $gap_width = sprintf("%.1f", ($gap_width));
        $frame_width = sprintf("%.1f", ($frame_width));
        // 数panel， 从开始到完
        $end_i = sprintf("%.1f", (intval($panel)/2) );
        $cnt = 0;
        for ($i = -intval($panel)/2; $i < $end_i; $i++){
            // 阴影

            // 框子, 如果用png盖在上面，是不是不用算框子了?s
            $panel_frame = self::create_imagick($panel_width+2*$frame_width, $panel_height+2*$frame_width, null, $frame_color);
            $x_frame = sprintf("%.1f", ($cx + $panel_frame->getImageWidth()*$i + ( $gap_width * ($i+0.5) )) );
            $y_frame = sprintf("%.1f",($cy - $panel_frame->getImageHeight()/2) );
            // 框子合成到scene
            $scene->compositeImage($panel_frame, Imagick::COMPOSITE_OVER, $x_frame, $y_frame);            
            $panel_frame->destroy();
            // 画心实际长度和高度
            $x_frame = sprintf("%.1f", ($x_frame + $frame_width));
            $y_frame = sprintf("%.1f", ($y_frame + $frame_width));

            if ($img) {
                $tmp = self::create_imagick(0, 0, $img, 'white');
                $tmp_w = $tmp->getImageWidth();
                $tmp_h = $tmp->getImageHeight();
                $tmp_pw = ( (($tmp_w/$panel)*$h_scale) > ($w_scale*$tmp_h) )?sprintf("%.1f", (($w_scale*$tmp_h)/$h_scale) ):sprintf("%.1f", ($tmp_w/$panel) );
                $tmp_gap = ($tmp_w-$tmp_pw*$panel)/$panel;
                $tmp->cropImage($tmp_pw, $tmp_h, sprintf("%.1f", ($cnt*$tmp_pw+$cnt*$tmp_gap) ),0);
                $tmp->thumbnailImage($panel_width, $panel_height);
                $scene->compositeImage($tmp, Imagick::COMPOSITE_OVER, $x_frame, $y_frame);
                $tmp->destroy();
                $cnt++;
            } else {
                $tmp = self::create_imagick($panel_width, $panel_height, null, 'white');
                $scene->compositeImage($tmp, Imagick::COMPOSITE_OVER, $x_frame, $y_frame);
                $tmp->destroy();
            }
        }

        // 最后将背景盖上
        $scene->compositeImage($bg, Imagick::COMPOSITE_OVER, 0, 0);
        $bg->destroy();

        $ext=pathinfo($output)['extension'];
        $folder=dirname($output);
        if(!is_dir($folder)) mkdir($folder, 0775, true);

        $scene->setImageFormat($ext);
        $scene->writeImage($output);
        $scene->destroy();
        return $output;
    }

    public static function create_imagick($width = 0, $height = 0, $src = null , $color = 'transparent'){
        //生成透明图片
        if(!$src) {
            $imagick = new Imagick();
            $imagick->newImage($width, $height, new ImagickPixel($color));
        } else {
            $imagick = new Imagick($src);
            if($width && $height) {
                $imagick->thumbnailImage($width,$height);
            }
        }
        return $imagick;
    }

    public static function combine_design($options) {
        // 必要的一些参数
        $output = $options['output'];           // output到的地址
        $background = $options['background'];       // 背景图片地址
        $img = $options['img'];                 // 图片地址
        $x_scale = $options['x'];
        $y_scale = $options['y'];
        $sub_width = $options['sub_width'];
        $frame_width = $options['frame_width'];
        $gap_width = $options['gap_width'];
        $frame_color = $options['frame_color'] ?: "black";
        list($sku, $size, $scale, $panel) = explode("_", $options['name']);     // Ex: CVS_Portrait_2:3_1
        // 没有地址，退出
        if (!$output) return null;

        // 建立场景
        $bg = self::create_imagick(0, 0, $background);
        $scene = self::create_imagick($bg->getImageWidth(), $bg->getImageHeight(), null, 'white');
        $scene->setImageFormat('png'); 
        // 图片的width和height
        $w = $scene->getImageWidth();   // 2000
        $h = $scene->getImageHeight();  // 2000
        // 场景中心坐标
        $cx = $w * $x_scale / 100;                  // 中心x坐标
        $cy = $h * $y_scale / 100;                  // 中心y坐标
        $sub_width = $w * $sub_width / 100;         // 场景中心总宽 (包括gap的总宽)
        $frame_width = $w * $frame_width / 100;     // frame宽度, 如果有frame的话
        $gap_width = $w * $gap_width / 100;         // gap宽度
        // 用每块panel的长度从比例里找出高度, 保留小数点
        list($w_scale, $h_scale) = explode(":", $scale);
        if ($panel > 1) {
            $sub_width = $sub_width - ($gap_width * ($panel - 1)); // 去掉gap的总宽
        }
        $pw = $sub_width / $panel;         // 每个panel的width和height
        $ph = $pw / $w_scale * $h_scale;

        // 数panel， 从开始到完
        $end_i = sprintf("%.1f", (intval($panel)/2) );
        $cnt = 0;   // i=-1.5, i=-0.5, i=0.5
        for ($i = -intval($panel)/2; $i < $end_i; $i++) {
            // 每个panel左上角的x的坐标
            // EX 3p: 第一个panel是从中心-1.5pw-gap, 第二个panel是中心-0.5pw, 第三个panel是中心+0.5pw+gap
            $x_frame = ($cx + $pw * $i + ($gap_width * ($i+0.5) ));
            $y_frame = $cy - $ph / 2;   // 每个panel左上角的y的坐标

            $tmp = self::create_imagick(0, 0, $img, 'white');
            $tmp_w = $tmp->getImageWidth();
            $tmp_h = $tmp->getImageHeight();
            
            // 参考用
            // $tmp_pw = ( ($tmp_w/$panel*$h_scale) > ($w_scale*$tmp_h) ) ? $w_scale*$tmp_h/$h_scale : $tmp_w/$panel;
            $tmp_pw = $tmp_w / $panel;
            $tmp->cropImage($tmp_pw, $tmp_h, $cnt*$tmp_pw, 0);  // width, height, x, y

            $tmp->thumbnailImage($pw, $ph);
            $scene->compositeImage($tmp, Imagick::COMPOSITE_OVER, $x_frame, $y_frame);
            $tmp->destroy();
            $cnt++;            
        }

        // 最后将背景盖上
        $scene->compositeImage($bg, Imagick::COMPOSITE_OVER, 0, 0);
        $bg->destroy();

        $ext=pathinfo($output)['extension'];
        $folder=dirname($output);
        if(!is_dir($folder)) mkdir($folder, 0775, true);

        $scene->setImageFormat($ext);
        $scene->writeImage($output);
        $scene->destroy();
        return $output;
    }
}