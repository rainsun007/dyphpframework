<?php
/**
 * 验证码类
 *
 * 建议自定义背景图片与扭曲功能不要同时使用 因为此版本背景也会被扭曲
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
class DyCaptcha
{
    /** 图片宽度(px) **/
    public $width = 150;
    
    /** 图片高度(px) **/
    public $height = 40;
    
    /**
     * 内建图像比例 用于生成品质较高的图片
     * (1低 2标准 3高)
     **/
    public $scale = 2;
    
    /** 图片格式类型 png 或 jpeg **/
    public $format = 'png';
   
    /** 验证码个数 **/
    public $wordLength = array(4,6);
    
    /**
     * 验证码模式
     * 0只字母  1只数字  2字母和数字 3为+ - x运算
     **/
    public $model = 0;
    //model为3时运算最小数值
    public $model3Min = 1;
    //model为3时运算最大数值
    public $model3Max = 10;
    
    /** 最大旋转 **/
    public $maxRotation = 8;
    
    /**
     * 干扰线条数
     * 0为不使用干扰线
     **/
    public $noiseLine = 5;
    
    /**
     * 干扰点个数
     * 0为不使用干扰点
     **/
    public $noise = 15;

    /**
     * 验证码扭曲设置
     * 不使用验证码扭曲 设置该属性为false
     **/
    public $waveWord = array('Yperiod'=>12,'Yamplitude'=>14,'Xperiod'=>11,'Xamplitude'=>5);
    
    /**
     * 背景(背景图路径字符串 或 RGB色值数组eg array(27,78,181))
     * 默认为随机生成(rand_letter_color 或 rand_color)
     * rand:默认  rand_letter:杂乱的字母数字(背景为白色)  rand_color:纯色  rand_letter_color:rand_letter+rand_color
     **/
    public $background = 'rand';
    
    /** 字体 **/
    public $fonts = array(
        array('spacing' => -3, 'minSize' => 27, 'maxSize' => 30, 'font' => 'AntykwaBold.ttf'),
        array('spacing' =>-1.5,'minSize' => 28, 'maxSize' => 31, 'font' => 'Candice.ttf'),
        array('spacing' => -2, 'minSize' => 24, 'maxSize' => 30, 'font' => 'AHGBold.ttf'),
        array('spacing' => -2, 'minSize' => 30, 'maxSize' => 38, 'font' => 'Duality.ttf'),
        array('spacing' => -2, 'minSize' => 28, 'maxSize' => 32, 'font' => 'Jura.ttf'),
        array('spacing' =>-1.5,'minSize' => 28, 'maxSize' => 32, 'font' => 'StayPuft.ttf'),
        array('spacing' => -2, 'minSize' => 28, 'maxSize' => 34, 'font' => 'TimesNewRomanBold.ttf'),
        array('spacing' => -1, 'minSize' => 20, 'maxSize' => 28, 'font' => 'VeraSansBold.ttf'),
    );
    
    /** 字体颜色 **/
    public $colors = array(
        array(27,78,181), // blue
        array(22,163,35), // green
        array(214,36,7),  // red
    );
    
    /** 存储方式:cookie, session, cache **/
    public $saveType = 'cookie';
    
    /** cookie或session键名 **/
    public $saveName = 'rs_c_code';

    /** 过期时间(second) **/
    public $expire = 300;

    //内比宽度
    private $sWidth;
    //内比高度
    private $sHeight;
    //resource
    private $im;
    //imagecolorallocate实例，应用于验证码结果、干扰线、噪点颜色
    private $gdFgColor;

    //验证码结果
    private $verifyCodeResult;
    //验证码字符
    private $verifyCodeText;
    
    /**
     * 创建并输出验证码
     **/
    public function createImage()
    {
        //初始化图像
        $this->initImage();

        //创建验证码
        if($this->verifyCodeResult == ''){
            $this->createText();
        }
        $this->createWord();

        //扭曲验证码
        $this->waveWord();
        //加干扰点
        $this->drawNoise();
        //加干扰线
        $this->noiseLine();
        //保存验证码
        $this->saveWord();
        //图片输出
        $this->outputImage();
    }

    /**
     * 获取验证码结果
     * createImage方法调用之前调用此方法
     *
     * @return string
     */
    public function getVerifyCodeResult()
    {
        $this->createText();
        return $this->verifyCodeResult;
    }
    
    /**
     * 初始化图片资源
     **/
    protected function initImage()
    {
        //清除处理
        if (!empty($this->im)) {
            imagedestroy($this->im);
        }
        
        $this->sWidth = $this->width*$this->scale;
        $this->sHeight = $this->height*$this->scale;
        
        $this->im = imagecreatetruecolor($this->sWidth, $this->sHeight);
        $this->background();
    }

    /**
     * 创建验证码
     */
    protected function createWord()
    {
        //获取到验证码
        $text = $this->verifyCodeText;
        
        //获取字体
        $fontcfg  = $this->fonts[array_rand($this->fonts)];
        $fontfile = $this->getResources('fonts', $fontcfg['font']);

        //获取颜色
        $color = $this->colors[array_rand($this->colors)];
        $this->gdFgColor = imagecolorallocate($this->im, $color[0], $color[1], $color[2]);

        //旋转及写入处理
        $lettersMissing = $this->wordLength[1]-strlen($text);
        $fontSizefactor = 1+($lettersMissing*0.09);
        $x = 22*$this->scale;
        $y = round(($this->height*27/35)*$this->scale);
        $length = strlen($text);
        for ($i=0; $i<$length; $i++) {
            $degree   = rand($this->maxRotation*-1, $this->maxRotation);
            $fontsize = rand($fontcfg['minSize'], $fontcfg['maxSize'])*$this->scale*$fontSizefactor;
            $letter   = substr($text, $i, 1);

            $coords = imagettftext($this->im, $fontsize, $degree, $x, $y, $this->gdFgColor, $fontfile, $letter);
            $x += ($coords[2]-$x) + ($fontcfg['spacing']*$this->scale);
        }
    }

    /**
     * 验证码扭曲处理
     */
    protected function waveWord()
    {
        if (!$this->waveWord) {
            return;
        }
        // X-axis wave generation
        $xp = $this->scale*$this->waveWord['Xperiod']*rand(1, 3);
        $k = rand(0, 100);
        for ($i = 0; $i < ($this->sWidth); $i++) {
            imagecopy($this->im, $this->im, $i-1, sin($k+$i/$xp) * ($this->scale*$this->waveWord['Xamplitude']), $i, 0, 1, $this->sHeight);
        }
        // Y-axis wave generation
        $k = rand(0, 100);
        $yp = $this->scale*$this->waveWord['Yperiod']*rand(1, 2);
        for ($i = 0; $i < ($this->sHeight); $i++) {
            imagecopy($this->im, $this->im, sin($k+$i/$yp) * ($this->scale*$this->waveWord['Yamplitude']), $i-1, 0, $i, $this->sWidth, 1);
        }
    }
    
    /**
     * 加干扰点
     **/
    protected function drawNoise()
    {
        if ($this->noise <= 0) {
            return;
        }

        for ($i = 0; $i < $this->noise; ++$i) {
            imagefilledarc($this->im, rand(10, $this->sWidth), rand(10, $this->sHeight), rand(7, 10), rand(7, 10), rand(0, 90), rand(180, 360), $this->gdFgColor, IMG_ARC_PIE);
        }
    }
    
    /**
     * 加干扰线
     **/
    protected function noiseLine()
    {
        if ($this->noiseLine<=0) {
            return;
        }
        
        for ($line = 0; $line < $this->noiseLine; ++ $line) {
            $x = $this->sWidth * (1 + $line) / ($this->noiseLine + 1);
            $x += (0.5 - $this->frand()) * $this->sWidth / $this->noiseLine;
            $y = rand($this->sHeight * 0.1, $this->sHeight * 0.9);
            
            $theta = ($this->frand() - 0.5) * M_PI * 0.7;
            $w = $this->sWidth;
            $len = rand($w * 0.4, $w * 0.7);
            $lwid = rand(0, 2);
            
            $k = $this->frand() * 0.6 + 0.2;
            $k = $k * $k * 0.5;
            $phi = $this->frand() * 6.28;
            $step = 0.5;
            $dx = $step * cos($theta);
            $dy = $step * sin($theta);
            $n = $len / $step;
            $amp = 1.5 * $this->frand() / ($k + 5.0 / $len);
            $x0 = $x - 0.5 * $len * cos($theta);
            $y0 = $y - 0.5 * $len * sin($theta);
            
            for ($i = 0; $i < $n; ++ $i) {
                $x = $x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi);
                $y = $y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi);
                imagefilledrectangle($this->im, $x, $y, $x + $lwid, $y + $lwid, $this->gdFgColor);
            }
        }
    }
     
    /**
     * 输出图像
     **/
    public function outputImage()
    {
        //大小还原
        $this->restoreImage();
        
        header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        if ($this->format == 'png' && function_exists('imagepng')) {
            header("Content-type: image/png");
            imagepng($this->im);
        } else {
            header("Content-type: image/jpeg");
            imagejpeg($this->im, null, 90);
        }
        imagedestroy($this->im);
        exit;
    }

    /**
     * 保存验证码
     **/
    protected function saveWord()
    {
        $time = time()+$this->expire;
        if ($this->saveType == 'session') {
            DySession::set($this->saveName, array($this->verifyCodeResult,$time));
        } elseif ($this->saveType == 'cookie') {
            if (DyCookie::is_set($this->saveName)) {
                DyCookie::delete($this->saveName);
            }
            DyCookie::set($this->saveName, json_encode(array($this->verifyCodeResult,$time)), $this->expire);
        }
    }
    
    /**
     * 背景设置
     **/
    protected function background()
    {
        $randModel = array('rand','rand_letter','rand_color','rand_letter_color');
        if (in_array($this->background, $randModel)) {
            switch ($this->background) {
               case 'rand':
                    $this->colorBackground();
                    if (rand(0, 1)) {
                        $this->letterBackground();
                    }
                    return;
               case 'rand_letter':
                    $randBg = imagecolorallocate($this->im, 255, 255, 255);
                    imagefill($this->im, 0, 0, $randBg);
                    $this->letterBackground();
                    return;
               case 'rand_color':
                    $this->colorBackground();
                    return;
               case 'rand_letter_color':
                    $this->colorBackground();
                    $this->letterBackground();
                    return;
            }
        }
        
        if (is_array($this->background)) {
            $bg = imagecolorallocate($this->im, $this->background[0], $this->background[1], $this->background[2]);
            imagefill($this->im, 0, 0, $bg);
        } else {
            $background = strrpos(str_replace('\\', '/', $this->background), '/') !== false ? $this->background : $this->getResources('backgrounds', $this->background);
            if (empty($this->background) || !is_file($background) || !is_readable($background)) {
                return;
            }
            $dat = @getimagesize($background);
            if ($dat == false) {
                return;
            }

            switch ($dat[2]) {
                case 1:  
                    $bgIm = @imagecreatefromgif($background); 
                    break;
                case 2:  
                    $bgIm = @imagecreatefromjpeg($background); 
                    break;
                case 3:  
                    $bgIm = @imagecreatefrompng($background); 
                    break;
                default: 
                    return;
            }
            if (!$bgIm) {
                return;
            }

            imagecopyresized($this->im,$bgIm,0,0,0,0,$this->sWidth,$this->sHeight,imagesx($bgIm),imagesy($bgIm));
        }
    }
     
    /**
     * 纯色背景
     **/
    protected function colorBackground()
    {
        $randBg = imagecolorallocate($this->im, mt_rand(100, 255), mt_rand(100, 255), mt_rand(100, 255));
        imagefill($this->im, 0, 0, $randBg);
    }
      
    /**
    * 字母背景
    **/
    protected function letterBackground()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        for ($i = 0; $i < 10; $i ++) {
            $fColor = imagecolorallocate($this->im, mt_rand(100, 150), mt_rand(100, 255), mt_rand(0, 255));
            for ($j = 1; $j <= 5; $j ++) {
                imagestring($this->im, $j, mt_rand(-10, $this->sWidth), mt_rand(-10, $this->sHeight), $chars{mt_rand(0, 35)}, $fColor);
            }
        }
    }
    
    /**
     * 生成验证码文本
     **/
    protected function createText()
    {
        //验证码个数
        $wordNum = mt_rand($this->wordLength[0], $this->wordLength[1]);
        //验证码模式 0只字母  1只数字  2字母和数字  3数字运算
        $text = $r = '';
        switch ($this->model) {
           case 0:
                $words  = "bcdfghjklmnpqrstvwxyz";
                $vocals = "aeiou";
                $vocal = rand(0, 1);
                for ($i=0; $i<$wordNum; $i++) {
                    if ($vocal) {
                        $text .= $vocals{mt_rand(0, 4)};
                    } else {
                        $text .= $words{mt_rand(0, 20)};
                    }
                    $vocal = !$vocal;
                }
                break;
           case 1:
                $words = '1234567890';
                for ($i = 0; $i < $wordNum; $i++) {
                    $text .= $words{mt_rand(0, 9)};
                }
                break;
           case 2:
                $words = 'abcdefghijklmnopqrstuvwxyz1234567890';
                for ($i = 0; $i < $wordNum; $i++) {
                    $text .= $words{mt_rand(0, 35)};
                }
                break;
           case 3:
                $signs = array('+', '-', 'x');
                $sign  = $signs[rand(0, 2)];
                $left = rand($this->model3Min, $this->model3Max);
                $right = rand($this->model3Min, $this->model3Max);
                switch ($sign) {
                    case 'x': 
                        $r = $left *  $right; 
                        break;
                    case '-':
                        $right = rand($this->model3Min, $left);
                        $r = $left - $right;
                        break;
                    default:  
                        $r = $left + $right; 
                        break;
                }
                $text = "$left $sign $right =";
                break;
        }
        $this->verifyCodeResult = $this->model == 3 ? $r : $text;
        $this->verifyCodeText = $text;
    }
     
    /**
     * 获取资源
     * @param string resources下的子目录
     * @param string 文件名
     * @return string 完整的文件地址
     **/
    protected function getResources($path, $fileName)
    {
        return dirname(__FILE__).'/resources/'.trim($path, '/').'/'.$fileName;
    }
     
    /**
     * 随机数
     **/
    protected function frand()
    {
        return 0.0001 * rand(0, 9999);
    }
     
    /**
     * 还原图片大小
     **/
    protected function restoreImage()
    {
        $imRestore = imagecreatetruecolor($this->width, $this->height);
        imagecopyresampled($imRestore,$this->im,0,0,0,0,$this->width,$this->height,$this->sWidth,$this->sHeight);
        $this->im = $imRestore;
    }
    
    /**
     * 验证以session模式存储的验证码是否正确 调用验证方法无论正确与否验证码都失效
     * 如不在同一些实例验证 需实例本类并注意$saveName属性的值应与获取验证码时相同
     * @param string 用户输入的验证码
     * @param string 生成验证码时指定的saveName
     * @param bool   验证时是否直接删除记录
     * @return bool
     **/
    public function sessionCheck($verifyCode, $saveName='',$del=true)
    {
        if (!empty($saveName)) {
            $this->saveName = $saveName;
        }
        
        $sVer = DySession::get($this->saveName);

        //删除session
        if($del){
            DySession::delete($this->saveName);
        }
        
        //验证码不合法
        if (empty($verifyCode) || !$sVer || !is_array($sVer) || count($sVer) != 2) {
            return false;
        }
        
        //过期,输入错误
        if (time()>$sVer[1] || strtolower($verifyCode) != $sVer[0]) {
            return false;
        }
        return true;
    }
     
    /**
     * 验证以cookie模式存储的验证码是否正确 调用验证方法无论正确与否验证码都失效
     * 如不在同一些实例验证 需实例本类并注意$saveName的值应与创建验证码时相同
     * @param string 用户输入的验证码
     * @param string 生成验证码时指定的saveName
     * @param bool   验证时是否直接删除记录
     * @return bool
     **/
    public function cookieCheck($verifyCode, $saveName='',$del=true)
    {
        if (!empty($saveName)) {
            $this->saveName = $saveName;
        }
        
        $sVer = DyCookie::get($this->saveName);

        //过期
        if (!$sVer && DyCookie::is_set($this->saveName)) {
            if($del){
                DyCookie::delete($this->saveName);
            }
            return false;
        }
        
        //删除cookie
        if($del){
            DyCookie::delete($this->saveName);
        }
        
        //验证码不合法
        $sVer = json_decode($sVer, true);
        if (empty($verifyCode) || !$sVer || !is_array($sVer) || count($sVer) != 2) {
            return false;
        }
        
        //过期,输入错误验证码
        if (time()>$sVer[1] || strtolower($verifyCode) != $sVer[0]) {
            return false;
        }
        return true;
    }
}
