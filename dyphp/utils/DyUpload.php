<?php
/**
 * 文件上传工具类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
 **/
class DyUpload{
    public $savePath = '';
    public $saveName = '';
    public $maxSize = 2;  //上传文件大小限制  单位MB
    private $extensions = array();

    /**
     * @brief    清除所有可以上传类型
     * @return
     **/
    public function cleanAllowExt(){
        $this->extensions = array();
        return $this;
    }

    /**
     * @brief    设置扩展名
     * @param    $ext
     * @param    $type
     * @return
     **/
    public function setAllowExt($ext){
        if(empty($ext)){
            return false;
        }

        if(is_array($ext)){
            array_merge($this->extensions,$ext);
            return;
        }

        if(!in_array($ext,$this->extensions)){
            $this->extensions[] = $ext;
        }
    }

    /**
     * @brief    上传文件
     * @param    $savePath    上传后文件保存路径
     * @param    $saveName    上传后文件名
     * @param    $upFileName  表单控件名称
     * @return
     **/
    public function up($savePath,$saveName,$upFileName){
        $this->savePath = $savePath;
        $this->saveName = $saveName;

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
        if ($upPic['size'] > $this->maxSize*1024*1024) {
            return 11;
        }

        //文件类型判断
        $fileExtension = strtolower(substr(strrchr($upPic['name'],"."),1));
        if(!in_array($fileExtension,$this->extensions)){
            return 12;
        }

        //文件移动
        if (is_uploaded_file($upPic['tmp_name'])) {
            $this->savePath = rtrim($this->savePath,'/');
            $pathName = $this->savePath . '/' . $this->saveName;
            if ($this->moveUpload($upPic['tmp_name'], $pathName)) {
                return 0;
            } else {
                return 13;
            }
        } else {
            return 14;
        }
    }

    /**
     * 无页面刷新框架上传 callback处理
     * @param string callback
     * @param string 返回上传状态
     * @param string 返回上传信息
     */
    public function iframJsCallBack($callbackFunName='',$type='seccess',$msg="upload seccess"){
        return '<script language="javascript" type="text/javascript">window.top.window.'.$callbackFunName.'(\''.$type."','".$msg.'\');</script>';
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
        if ($this->checkDir($this->savePath)) {
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

    /**
     * @brief    设置扩展名
     * @return
     **/
    private function setExtensions(){
        $this->extensions = array(
            'apk',
            '3gp',
            'ai',
            'aif',
            'aifc',
            'aiff',
            'asc',
            'atom',
            'au',
            'avi',
            'bcpio',
            'bin',
            'bmp',
            'cdf',
            'cgm',
            'class',
            'cpio',
            'cpt',
            'csh',
            'css',
            'dcr',
            'dif',
            'dir',
            'djv',
            'djvu',
            'dll',
            'dmg',
            'dms',
            'doc',
            'dtd',
            'dv',
            'dvi',
            'dxr',
            'eps',
            'etx',
            'exe',
            'ez',
            'flv',
            'gif',
            'gram',
            'grxml',
            'gtar',
            'gz',
            'hdf',
            'hqx',
            'htm',
            'html',
            'ice',
            'ico',
            'ics',
            'ief',
            'ifb',
            'iges',
            'igs',
            'jnlp',
            'jp2',
            'jpe',
            'jpeg',
            'jpg',
            'js',
            'kar',
            'latex',
            'lha',
            'lzh',
            'm3u',
            'm4a',
            'm4p',
            'm4u',
            'm4v',
            'mac',
            'man',
            'mathml',
            'me',
            'mesh',
            'mid',
            'midi',
            'mif',
            'mov',
            'movie',
            'mp2',
            'mp3',
            'mp4',
            'mpe',
            'mpeg',
            'mpg',
            'mpga',
            'ms',
            'msh',
            'mxu',
            'nc',
            'oda',
            'ogg',
            'ogv',
            'pbm',
            'pct',
            'pdb',
            'pdf',
            'pgm',
            'pgn',
            'pic',
            'pict',
            'png',
            'pnm',
            'pnt',
            'pntg',
            'ppm',
            'ppt',
            'ps',
            'qt',
            'qti',
            'qtif',
            'ra',
            'ram',
            'ras',
            'rdf',
            'rgb',
            'rm',
            'roff',
            'rtf',
            'rtx',
            'sgm',
            'sgml',
            'sh',
            'shar',
            'silo',
            'sit',
            'skd',
            'skm',
            'skp',
            'skt',
            'smi',
            'smil',
            'snd',
            'so',
            'spl',
            'src',
            'sv4cpio',
            'sv4crc',
            'svg',
            'swf',
            't',
            'tar',
            'tcl',
            'tex',
            'texi',
            'texinfo',
            'tif',
            'tiff',
            'tr',
            'tsv',
            'txt',
            'ustar',
            'vcd',
            'vrml',
            'vxml',
            'wav',
            'wbmp',
            'wbxml',
            'webm',
            'wml',
            'wmlc',
            'wmls',
            'wmlsc',
            'wmv',
            'wrl',
            'xbm',
            'xht',
            'xhtml',
            'xls',
            'xml',
            'xpm',
            'xsl',
            'xslt',
            'xul',
            'xwd',
            'xyz',
            'zip',
            'a',
            'asf',
            'asm',
            'asx',
            'bat',
            'bz2',
            'c',
            'cab',
            'cc',
            'chm',
            'com',
            'conf',
            'cpp',
            'crt',
            'csv',
            'cxx',
            'deb',
            'der',
            'diff',
            'dot',
            'ear',
            'eml',
            'f',
            'f77',
            'f90',
            'for',
            'gem',
            'gemspec',
            'h',
            'hh',
            'iso',
            'jar',
            'java',
            'json',
            'log',
            'mbox',
            'mdoc',
            'mime',
            'mml',
            'mng',
            'mp4v',
            'msi',
            'odp',
            'ods',
            'odt',
            'p',
            'pas',
            'pem',
            'pgp',
            'pkg',
            'pl',
            'pm',
            'pps',
            'psd',
            'py',
            'rake',
            'rar',
            'rb',
            'rpm',
            'rss',
            'ru',
            's',
            'sig',
            'svgz',
            'tbz',
            'text',
            'torrent',
            'vcf',
            'vcs',
            'war',
            'wma',
            'wmx',
            'wsdl',
            'yaml',
            'yml',
            'sh',
            'exe',
            'bat',
            'vbs',
        );
    }

}
