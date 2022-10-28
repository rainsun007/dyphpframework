<?php 
/**
 * 文件下载
 *
 * @author QingYu.Sun Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com
 * @copyright dyphp.com
 **/
class DyDownload
{
    //类型
    private $extArr = array(
        "default" => "application/force-download",
        "3gp"   => "video/3gpp",
        "a"     => "application/octet-stream",
        "ai"    => "application/postscript",
        "aif"   => "audio/x-aiff",
        "aiff"  => "audio/x-aiff",
        "asc"   => "application/pgp-signature",
        "asf"   => "video/x-ms-asf",
        "asm"   => "text/x-asm",
        "asx"   => "video/x-ms-asf",
        "atom"  => "application/atom+xml",
        "au"    => "audio/basic",
        "avi"   => "video/x-msvideo",
        "bat"   => "application/x-msdownload",
        "bin"   => "application/octet-stream",
        "bmp"   => "image/bmp",
        "bz2"   => "application/x-bzip2",
        "c"     => "text/x-c",
        "cab"   => "application/vnd.ms-cab-compressed",
        "cc"    => "text/x-c",
        "chm"   => "application/vnd.ms-htmlhelp",
        "class" => "application/octet-stream",
        "com"   => "application/x-msdownload",
        "conf"  => "text/plain",
        "cpp"   => "text/x-c",
        "crt"   => "application/x-x509-ca-cert",
        "css"   => "text/css",
        "csv"   => "application/vnd.ms-excel",
        "cxx"   => "text/x-c",
        "deb"   => "application/x-debian-package",
        "der"   => "application/x-x509-ca-cert",
        "diff"  => "text/x-diff",
        "djv"   => "image/vnd.djvu",
        "djvu"  => "image/vnd.djvu",
        "dll"   => "application/x-msdownload",
        "dmg"   => "application/octet-stream",
        "doc"   => "application/msword",
        "dot"   => "application/msword",
        "dtd"   => "application/xml-dtd",
        "dvi"   => "application/x-dvi",
        "ear"   => "application/java-archive",
        "eml"   => "message/rfc822",
        "eps"   => "application/postscript",
        "exe"   => "application/x-msdownload",
        "f"     => "text/x-fortran",
        "f77"   => "text/x-fortran",
        "f90"   => "text/x-fortran",
        "flv"   => "video/x-flv",
        "for"   => "text/x-fortran",
        "gem"   => "application/octet-stream",
        "gemspec" => "text/x-script.ruby",
        "gif"   => "image/gif",
        "gz"    => "application/x-gzip",
        "h"     => "text/x-c",
        "hh"    => "text/x-c",
        "htm"   => "text/html",
        "html"  => "text/html",
        "ico"   => "image/vnd.microsoft.icon",
        "ics"   => "text/calendar",
        "ifb"   => "text/calendar",
        "iso"   => "application/octet-stream",
        "jar"   => "application/java-archive",
        "java"  => "text/x-java-source",
        "jnlp"  => "application/x-java-jnlp-file",
        "jpeg"  => "image/jpeg",
        "jpg"   => "image/jpeg",
        "js"    => "application/javascript",
        "json"  => "application/json",
        "log"   => "text/plain",
        "m3u"   => "audio/x-mpegurl",
        "m4v"   => "video/mp4",
        "man"   => "text/troff",
        "mathml"  => "application/mathml+xml",
        "mbox"  => "application/mbox",
        "mdoc"  => "text/troff",
        "me"    => "text/troff",
        "mid"   => "audio/midi",
        "midi"  => "audio/midi",
        "mime"  => "message/rfc822",
        "mml"   => "application/mathml+xml",
        "mng"   => "video/x-mng",
        "mov"   => "video/quicktime",
        "mp3"   => "audio/mpeg",
        "mp4"   => "video/mp4",
        "mp4v"  => "video/mp4",
        "mpeg"  => "video/mpeg",
        "mpg"   => "video/mpeg",
        "ms"    => "text/troff",
        "msi"   => "application/x-msdownload",
        "odp"   => "application/vnd.oasis.opendocument.presentation",
        "ods"   => "application/vnd.oasis.opendocument.spreadsheet",
        "odt"   => "application/vnd.oasis.opendocument.text",
        "ogg"   => "application/ogg",
        "p"     => "text/x-pascal",
        "pas"   => "text/x-pascal",
        "pbm"   => "image/x-portable-bitmap",
        "pdf"   => "application/pdf",
        "pem"   => "application/x-x509-ca-cert",
        "pgm"   => "image/x-portable-graymap",
        "pgp"   => "application/pgp-encrypted",
        "pkg"   => "application/octet-stream",
        "pl"    => "text/x-script.perl",
        "pm"    => "text/x-script.perl-module",
        "png"   => "image/png",
        "pnm"   => "image/x-portable-anymap",
        "ppm"   => "image/x-portable-pixmap",
        "pps"   => "application/vnd.ms-powerpoint",
        "ppt"   => "application/vnd.ms-powerpoint",
        "ps"    => "application/postscript",
        "psd"   => "image/vnd.adobe.photoshop",
        "py"    => "text/x-script.python",
        "qt"    => "video/quicktime",
        "ra"    => "audio/x-pn-realaudio",
        "rake"  => "text/x-script.ruby",
        "ram"   => "audio/x-pn-realaudio",
        "rar"   => "application/x-rar-compressed",
        "rb"    => "text/x-script.ruby",
        "rdf"   => "application/rdf+xml",
        "roff"  => "text/troff",
        "rpm"   => "application/x-redhat-package-manager",
        "rss"   => "application/rss+xml",
        "rtf"   => "application/rtf",
        "ru"    => "text/x-script.ruby",
        "s"     => "text/x-asm",
        "sgm"   => "text/sgml",
        "sgml"  => "text/sgml",
        "sh"    => "application/x-sh",
        "sig"   => "application/pgp-signature",
        "snd"   => "audio/basic",
        "so"    => "application/octet-stream",
        "svg"   => "image/svg+xml",
        "svgz"  => "image/svg+xml",
        "swf"   => "application/x-shockwave-flash",
        "t"     => "text/troff",
        "tar"   => "application/x-tar",
        "tbz"   => "application/x-bzip-compressed-tar",
        "tcl"   => "application/x-tcl",
        "tex"   => "application/x-tex",
        "texi"  => "application/x-texinfo",
        "texinfo" => "application/x-texinfo",
        "text"  => "text/plain",
        "tif"   => "image/tiff",
        "tiff"  => "image/tiff",
        "torrent" => "application/x-bittorrent",
        "tr"    => "text/troff",
        "txt"   => "text/plain",
        "vcf"   => "text/x-vcard",
        "vcs"   => "text/x-vcalendar",
        "vrml"  => "model/vrml",
        "war"   => "application/java-archive",
        "wav"   => "audio/x-wav",
        "wma"   => "audio/x-ms-wma",
        "wmv"   => "video/x-ms-wmv",
        "wmx"   => "video/x-ms-wmx",
        "wrl"   => "model/vrml",
        "wsdl"  => "application/wsdl+xml",
        "xbm"   => "image/x-xbitmap",
        "xhtml" => "application/xhtml+xml",
        "xls"   => "application/vnd.ms-excel",
        "xml"   => "application/xml",
        "xpm"   => "image/x-xpixmap",
        "xsl"   => "application/xml",
        "xslt"  => "application/xslt+xml",
        "yaml"  => "text/yaml",
        "yml"   => "text/yaml",
        "zip"   => "application/zip",
    );

    //默认允许下载类型
    private $allowArr = array(
        "jpg",
        "png",
        "rar",
        "zip",
        "tar",
    );

    /**
     * @brief    清除所有可以下载类型
     * @return
     **/
    public function cleanAllowExt()
    {
        $this->allowArr = array();
        return $this;
    }

    /**
     * @brief    设置扩展类型
     * @param    $ext
     * @param    $type
     * @return
     **/
    public function setExt($ext, $type)
    {
        if (empty($ext) || empty($type)) {
            return false;
        }
        $this->extArr[$ext] = $type;
    }

    /**
     * @brief    获取扩展类型
     * @param    $ext
     * @return
     **/
    public function getExt($ext, $useDefault=false)
    {
        if (empty($ext) || !isset($this->extArr[$ext])) {
            return $useDefault ? $this->extArr['default'] : null;
        }
        return $this->extArr[$ext];
    }

    /**
     * @brief    设置允许下载扩展名
     * @param    $ext
     * @return
     **/
    public function setAllowExt($ext)
    {
        if (empty($ext)) {
            return false;
        }

        if (is_array($ext)) {
            array_merge($this->allowArr, $ext);
            return;
        }

        if (!in_array($ext, $this->allowArr)) {
            $this->allowArr[] = $ext;
        }
    }

    /**
     * 文件下载
     * @param string 文件名
     * @param string 下载文件所在目录
     * @param bool   获取不到文件类型时是否使用默认文件类型
     * @param bool   是否为本地文件
     **/
    public function down($fileName='', $fileDir='', $useDefault=false, $isLocal=true)
    {
        if (empty($fileName)) {
            return array('status'=>false,'message'=>"file name is empty");
        }

        $fileDir = $fileDir ? $fileDir : APP_PARENT_PATH.'/';
        $filePath = $fileDir . $fileName;
        $fileExtension = strtolower(substr(strrchr($fileName, "."), 1));

        if (!in_array($fileExtension, $this->allowArr)) {
            return array('status'=>false,'message'=>"forbid download");
        }

        $type = $this->getExt($fileExtension, $useDefault);
        
        if (!$type) {
            return array('status'=>false,'message'=>"extension not exist");
        }
        
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Cache-Control: public", false);
        header("Content-Description: File Transfer");
        header("Content-type: {$type}");
        header("Accept-Ranges: bytes");
        header("Connection: close");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Content-Transfer-Encoding: binary");
        
        if ($isLocal) {
            if (!file_exists($filePath)) {
                return array('status'=>false,'message'=>"file not exist");
            }
            header("Accept-Length=> ".filesize($filePath));
        }

        $file = fopen($filePath, "rb");
        if (!$file) {
            return array('status'=>false,'message'=>"file read error");
        }

        while (!feof($file) && connection_status() == 0) {
            echo fread($file, 8192);
            flush();
        }
        fclose($file);
        return array('status'=>true,'message'=>"success");
    }
}
