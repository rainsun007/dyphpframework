<?php
/**
 * 图片处理工具类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
 **/
class DyGDImg {
    private static $instance;

    private static function instance(){
        if(self::$instance){
            return self::$instance;
        }
        self::$instance = new DyGDImgRealize();
        return self::$instance;
    }

    /**
     * 按坐标及宽高生成缩略图(此方法适合用于前端可视化切图)
     *
     * @param string 源图片地址 如：$_FILES['files']['tmp_name']
     * @param string 保存生成图像的地址(目录如不存在会自动创建)
     * @param string 生成图像的文件名
     * @param int    新图的宽度
     * @param int    新图的高度
     * @param int    截图距原图左上角的宽
     * @param int    截图距原图左上角的高
     *
     * 实例
     * DyGDImg::cut('/tmp/a.jpg','/www/upload/','b.jpg',125,125,30,50);
     **/
    public static function cut($srcimg,$save_path,$save_name,$width,$heigth,$srcx=0,$srcy=0){
        self::instance()->resize($srcimg,$save_path,$save_name,$width,$heigth,$srcx,$srcy,true);
        $save_path = rtrim($save_path,'/');
        $saveFile = $save_path.'/'.$save_name;
        return file_exists($saveFile);
    }

    /**
     * 按照原图比例生成缩略图
     *
     * @param string 源图片地址 如：$_FILES['files']['tmp_name']
     * @param string 保存生成图像的地址(目录如不存在会自动创建)
     * @param string 生成图像的文件名
     * @param int    新图的宽度
     * @param int    新图的高度
     *
     * 实例
     * DyGDImg::resize('/tmp/a.jpg','/www/upload/','b.jpg',125,125);
     **/
    public static function resize($srcimg,$save_path,$save_name,$width,$heigth){
        self::instance()->resize($srcimg,$save_path,$save_name,$width,$heigth,0,0,false);
        $save_path = rtrim($save_path,'/');
        $saveFile = $save_path.'/'.$save_name;
        return file_exists($saveFile);
    }

    /**
     * @brief    上传图片
     * @param    $upFileName  表单上传控件name
     * @param    $save_path
     * @param    $save_name
     * @param    $extensions
     * @param    $maxSize    单位MB
     * @return   bool|int
     **/
    public static function upload($upFileName,$save_path,$save_name,$maxSize=2,$extensions ='jpg|gif|bmp|png'){
        self::instance()->save_path = $save_path;
        self::instance()->save_name = $save_name;
        self::instance()->extensions = explode('|',$extensions);
        self::instance()->maxSize = $maxSize*1024*1024;
        return self::instance()->upload($upFileName);
    }

    /**
     * @brief  获取保存后的文件名
     **/
    public static function getFileSaveName(){
        return self::instance()->fileSaveName;
    }

    /**
     * 无页面刷新框架上传 callback处理
     * @param string callback
     * @param string 返回上传状态
     * @param string 返回上传信息
     */
    public static function iframJsCallBack($callbackFunName='',$type='seccess',$msg="upload seccess"){
        return '<script language="javascript" type="text/javascript">window.top.window.'.$callbackFunName.'(\''.$type."','".$msg.'\');</script>';
    }
}

/**
 * 实现类
 */
class DyGDImgRealize{
    //图片类型
    private $type;
    //实际宽度
    private $width;
    //实际高度
    private $height;
    //截图距原图左上角的宽
    private $src_x;
    //截图距原图左上角的高
    private $src_y;
    //是否裁图
    private $cut;
    //源图象
    private $srcimg;
    //新生成图像的地址
    private $dstimg;
    //改变后的宽度
    private $resize_width;
    //改变后的高度
    private $resize_height;
    //保存切图的路径
    private $resize_save_path;
    //临时创建的图象
    private $im;

    /** 上传使用参数 **/
    public $save_path = '';
    public $save_name = '';
    public $extensions = array('jpg','gif','bmp','png');
    public $maxSize = 2097152;
    public $fileSaveName;

    public function __construct(){
        if (!extension_loaded('gd') && !extension_loaded('gd2')){
            DyPhpBase::throwException('GD is not loaded');
        }
    }

    /**
     * 利用PHP的GD库生成缩略图。
     *
     * @param string 源图片地址$_FILES['files']['tmp_name']
     * @param string 保存生成图像的地址
     * @param string 生成图像的文件名
     * @param int    新图的宽度
     * @param int    新图的高度
     * @param int    截图距原图左上角的宽
     * @param int    截图距原图左上角的高
     * @param bool   是否裁图，true按坐标及宽高生成缩略图(此方法适合用于前端可视化切图)，false则按照原图比例生成缩略图
     *
     * 实例
     * $image = new DyImage();
     * $image->resize('/tmp/a.jpg','/www/upload/','b.jpg',125,125,30,50,true);
     **/
    public function resize($srcimg,$save_path,$save_name,$width,$heigth,$srcx=0,$srcy=0,$cut=false){
        $save_path = rtrim($save_path,'/');
        $this->srcimg = $srcimg;
        $this->dstimg = $save_path.'/'.$save_name;
        $this->resize_save_path = $save_path;
        $this->resize_width = $width;
        $this->resize_height = $heigth;
        $this->cut = $cut;
        $this->src_x = $srcx;
        $this->src_y = $srcy;

        //图片信息
        $this->imageInfo($this->srcimg);

        //初始化图象
        $this->imImg();

        //生成图象
        $this->getResize();
    }

    /**
     * 上传图片
     *
     * @param string     表单控件名称
     * @return bool|int
     */
    public function upload($upFileName){
        //上传表单存在性判断
        if(!isset($_FILES[$upFileName])){
            return 10;
        }

        //上传出错判断 1~7
        //0; 文件上传成功。
        //1; 超过了文件大小php.ini中即系统设定的大小。
        //2; 超过了文件大小MAX_FILE_SIZE 选项指定的值。
        //3; 文件只有部分被上传。
        //4; 没有文件被上传。
        //5; 服务器临时文件夹丢失
        //6; 找不到临时文件夹。PHP 4.3.10 和 PHP 5.0.3 引进。
        //7; 文件写入失败。PHP 5.1.0 引进。
        $upPic = $_FILES[$upFileName];
        if($upPic['error'] > 0){
            return $upPic['error'];
        }

        //文件大小判断(自定义阀值)
        if ($upPic['size'] > $this->maxSize) {
            return 11;
        }

        //文件类型判断
        $info = getimagesize($upPic['tmp_name']);
        switch($info['mime']){
        case 'image/pjpeg' :
        case 'image/jpeg' :
            $fileType = 'jpg';break;
        case 'image/gif':
            $fileType = 'gif';break;
        case 'image/x-png' :
        case 'image/png':
            $fileType = 'png';break;
        case 'image/bmp':
            $fileType = 'bmp';break;
        default:
            $fileType = 'unknown';break;
        }
        if (!in_array($fileType, $this->extensions)) {
            return 12;
        }

        //文件移动
        if (is_uploaded_file($upPic['tmp_name'])) {
            $this->save_path = rtrim($this->save_path,'/');
            $this->path_name = $this->save_path . '/' . $this->save_name.'.'.$fileType;
            if ($this->moveUpload($upPic['tmp_name'], $this->path_name)) {
                $this->fileSaveName = $this->save_name.'.'.$fileType;
                return 0;
            } else {
                return 13;
            }
        } else {
            return 14;
        }
    }



    /**
     * resize方法
     **/
    private function getResize(){
        //裁图
        if($this->cut){
            $resize_width = $this->resize_width;
            $resize_height = $this->resize_height;
            $src_x = $this->src_x;
            $src_y = $this->src_y;
            $newimg = imagecreatetruecolor($resize_width,$resize_height);
            imagecopy($newimg, $this->im,0,0,$src_x,$src_y,$resize_width,$resize_height);
        }else{
            //改变后的图象的比例
            $resize_ratio = $this->resize_width/$this->resize_height;
            //实际图象的比例
            $ratio = $this->width/$this->height;
            $src_x = $this->src_x;
            $src_y = $this->src_y;
            $width = $this->width;
            $height = $this->height;
            if($ratio>=$resize_ratio){
                $resize_width = $this->resize_width;
                $resize_height = ceil($this->resize_width/$ratio);
            }else{
                $resize_width = ceil($this->resize_height*$ratio);
                $resize_height = $this->resize_height;
            }
            $newimg = imagecreatetruecolor($resize_width,$resize_height);
            imagecopyresampled($newimg, $this->im, 0, 0, $src_x, $src_y, $resize_width,$resize_height, $width, $height);
        }

        $this->createImg($newimg);
        ImageDestroy($this->im);
    }

    /**
     * 获取文件信息
     * @param 源图像地址
     */
    private function imageInfo($srcimg){
        $picType = array('1'=>'gif','2'=>'jpg','3'=>'png','6'=>'bmp');
        list($width, $height, $type, $attr) = getimagesize($srcimg);
        $this->width = $width;
        $this->height = $height;
        $this->type = $picType[$type];
    }

    //初始化图象
    private function imImg(){
        switch($this->type){
        case "jpg":
            $this->im = imagecreatefromjpeg($this->srcimg);
            break;
        case "gif":
            $this->im = imagecreatefromgif($this->srcimg);
            break;
        case "png":
            $this->im = imagecreatefrompng($this->srcimg);
            break;
        case "bmp":
            $this->im = imagecreatefromwbmp($this->srcimg);
            break;
        }
    }

    //创建图象
    private function createImg($newimg){
        $this->checkDir($this->resize_save_path);
        switch($this->type){
        case "jpg":
            imagejpeg ($newimg,$this->dstimg,80);
            break;
        case "gif":
            imagegif ($newimg,$this->dstimg);
            break;
        case "png":
            imagepng ($newimg,$this->dstimg);
            break;
        case "bmp":
            imagewbmp ($newimg,$this->dstimg);
            break;
        }
    }

    /**
     * 移动上传的临时文件到指定位置
     *
     * @param string $tmp_file
     * @param string $new_file
     * @return bool
     */
    private function moveUpload($tmp_file, $new_file){
        umask(0);
        if ($this->checkDir($this->save_path)) {
            if (move_uploaded_file($tmp_file, $new_file)) {
                chmod($new_file, 0644);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 检测目录是否存在
     *
     * @param string $dir
     * @return bool
     */
    private function checkDir($dir){
        if (is_dir($dir)) {
            return true;
        } else {
            umask(0);
            return mkdir($dir, 0755, true);
        }
    }

}
