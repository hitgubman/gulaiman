<?php

namespace Dompdf;

class Helpers
{
    
    public static function pre_r($mixed, $return = false)
    {
        if ($return) {
            return "<pre>" . print_r($mixed, true) . "</pre>";
        }

        if (php_sapi_name() !== "cli") {
            echo "<pre>";
        }

        print_r($mixed);

        if (php_sapi_name() !== "cli") {
            echo "</pre>";
        } else {
            echo "\n";
        }

        flush();

        return null;
    }

    
    public static function build_url($protocol, $host, $base_path, $url, $chrootDirs = [])
    {
        $protocol = mb_strtolower($protocol);
        if (empty($protocol)) {
            $protocol = "file://";
        }
        if ($url === "") {
            return null;
        }

        $url_lc = mb_strtolower($url);

        
        
        if (
            (
                mb_strpos($url_lc, "://") !== false
                && !in_array(substr($url_lc, 0, 7), ["file://", "phar://"], true)
            )
            || mb_substr($url_lc, 0, 1) === "#"
            || mb_strpos($url_lc, "data:") === 0
            || mb_strpos($url_lc, "mailto:") === 0
            || mb_strpos($url_lc, "tel:") === 0
        ) {
            return $url;
        }

        $res = "";
        if (strpos($url_lc, "file://") === 0) {
            $url = substr($url, 7);
            $protocol = "file://";
        } elseif (strpos($url_lc, "phar://") === 0) {
            $res = substr($url, strpos($url_lc, ".phar")+5);
            $url = substr($url, 7, strpos($url_lc, ".phar")-2);
            $protocol = "phar://";
        }

        $ret = "";

        $is_local_path = in_array($protocol, ["file://", "phar://"], true);

        if ($is_local_path) {
            
            
            
            
            if ($url[0] !== '/' && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' || (mb_strlen($url) > 1 && $url[0] !== '\\' && $url[1] !== ':'))) {
                
                $ret .= realpath($base_path) . '/';
            }
            $ret .= $url;
            $ret = preg_replace('/\?(.*)$/', "", $ret);

            $filepath = realpath($ret);
            if ($filepath !== false) {
                $ret = "$protocol$filepath$res";

                return $ret;
            }

            if ($url[0] == '/' && !empty($chrootDirs)) {
                foreach ($chrootDirs as $dir) {
                    $ret = realpath($dir) . $url;
                    $ret = preg_replace('/\?(.*)$/', "", $ret);

                    if ($filepath = realpath($ret)) {
                        $ret = "$protocol$filepath$res";

                        return $ret;
                    }
                }
            }

            return null;
        }

        $ret = $protocol;
        
        if (strpos($url, '//') === 0) {
            $ret .= substr($url, 2);
            
        } elseif ($url[0] === '/' || $url[0] === '\\') {
            
            $ret .= $host . $url;
        } else {
            
            
            $ret .= $host . $base_path . $url;
        }

        
        $parsed_url = parse_url($ret);

        
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        
        
        
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for ($n=1; $n>0; $path=preg_replace($re, '/', $path, -1, $n)) {}

        $ret = "$scheme$user$pass$host$port$path$query$fragment";

        return $ret;
    }

    
    public static function buildContentDispositionHeader($dispositionType, $filename)
    {
        $encoding = mb_detect_encoding($filename);
        $fallbackfilename = mb_convert_encoding($filename, "ISO-8859-1", $encoding);
        $fallbackfilename = str_replace("\"", "", $fallbackfilename);
        $encodedfilename = rawurlencode($filename);

        $contentDisposition = "Content-Disposition: $dispositionType; filename=\"$fallbackfilename\"";
        if ($fallbackfilename !== $filename) {
            $contentDisposition .= "; filename*=UTF-8''$encodedfilename";
        }

        return $contentDisposition;
    }

    
    public static function dec2roman($num): string
    {

        static $ones = ["", "i", "ii", "iii", "iv", "v", "vi", "vii", "viii", "ix"];
        static $tens = ["", "x", "xx", "xxx", "xl", "l", "lx", "lxx", "lxxx", "xc"];
        static $hund = ["", "c", "cc", "ccc", "cd", "d", "dc", "dcc", "dccc", "cm"];
        static $thou = ["", "m", "mm", "mmm"];

        if (!is_numeric($num)) {
            throw new Exception("dec2roman() requires a numeric argument.");
        }

        if ($num >= 4000 || $num <= 0) {
            return (string) $num;
        }

        $num = strrev((string)$num);

        $ret = "";
        switch (mb_strlen($num)) {
            
            case 4:
                $ret .= $thou[$num[3]];
            
            case 3:
                $ret .= $hund[$num[2]];
            
            case 2:
                $ret .= $tens[$num[1]];
            
            case 1:
                $ret .= $ones[$num[0]];
            default:
                break;
        }

        return $ret;
    }

    
    public static function clamp(float $length, float $min, float $max): float
    {
        return max($min, min($length, $max));
    }

    
    public static function is_percent($value): bool
    {
        return is_string($value) && false !== mb_strpos($value, "%");
    }

    
    public static function parse_data_uri($data_uri)
    {
        $expression = '/^data:(?P<mime>[a-z0-9\/+-.]+)(;charset=(?P<charset>[a-z0-9-])+)?(?P<base64>;base64)?\,(?P<data>.*)?/is';
        if (!preg_match($expression, $data_uri, $match)) {
            $parts = explode(",", $data_uri);
            $parts[0] = preg_replace('/\\s/', '', $parts[0]);
            if (preg_match('/\\s/', $data_uri) && !preg_match($expression, implode(",", $parts), $match)) {
                return false;
            }
        }

        $match['data'] = rawurldecode($match['data']);
        $result = [
            'charset' => $match['charset'] ? $match['charset'] : 'US-ASCII',
            'mime' => $match['mime'] ? $match['mime'] : 'text/plain',
            'data' => $match['base64'] ? base64_decode($match['data']) : $match['data'],
        ];

        return $result;
    }

    
    public static function encodeURI($uri) {
        $unescaped = [
            '%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!', '%7E'=>'~',
            '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
        ];
        $reserved = [
            '%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
            '%40'=>'@','%26'=>'&','%3D'=>'=','%2B'=>'+','%24'=>'$'
        ];
        $score = [
            '%23'=>'#'
        ];
        return preg_replace(
            '/%25([a-fA-F0-9]{2,2})/',
            '%$1',
            strtr(rawurlencode($uri), array_merge($reserved, $unescaped, $score))
        );
    }

    
    public static function rle8_decode($str, $width)
    {
        $lineWidth = $width + (3 - ($width - 1) % 4);
        $out = '';
        $cnt = strlen($str);

        for ($i = 0; $i < $cnt; $i++) {
            $o = ord($str[$i]);
            switch ($o) {
                case 0: 
                    $i++;
                    switch (ord($str[$i])) {
                        case 0: 
                            $padCnt = $lineWidth - strlen($out) % $lineWidth;
                            if ($padCnt < $lineWidth) {
                                $out .= str_repeat(chr(0), $padCnt); 
                            }
                            break;
                        case 1: 
                            $padCnt = $lineWidth - strlen($out) % $lineWidth;
                            if ($padCnt < $lineWidth) {
                                $out .= str_repeat(chr(0), $padCnt); 
                            }
                            break 3;
                        case 2: 
                            $i += 2;
                            break;
                        default: 
                            $num = ord($str[$i]);
                            for ($j = 0; $j < $num; $j++) {
                                $out .= $str[++$i];
                            }
                            if ($num % 2) {
                                $i++;
                            }
                    }
                    break;
                default:
                    $out .= str_repeat($str[++$i], $o);
            }
        }
        return $out;
    }

    
    public static function rle4_decode($str, $width)
    {
        $w = floor($width / 2) + ($width % 2);
        $lineWidth = $w + (3 - (($width - 1) / 2) % 4);
        $pixels = [];
        $cnt = strlen($str);
        $c = 0;

        for ($i = 0; $i < $cnt; $i++) {
            $o = ord($str[$i]);
            switch ($o) {
                case 0: 
                    $i++;
                    switch (ord($str[$i])) {
                        case 0: 
                            while (count($pixels) % $lineWidth != 0) {
                                $pixels[] = 0;
                            }
                            break;
                        case 1: 
                            while (count($pixels) % $lineWidth != 0) {
                                $pixels[] = 0;
                            }
                            break 3;
                        case 2: 
                            $i += 2;
                            break;
                        default: 
                            $num = ord($str[$i]);
                            for ($j = 0; $j < $num; $j++) {
                                if ($j % 2 == 0) {
                                    $c = ord($str[++$i]);
                                    $pixels[] = ($c & 240) >> 4;
                                } else {
                                    $pixels[] = $c & 15;
                                }
                            }

                            if ($num % 2 == 0) {
                                $i++;
                            }
                    }
                    break;
                default:
                    $c = ord($str[++$i]);
                    for ($j = 0; $j < $o; $j++) {
                        $pixels[] = ($j % 2 == 0 ? ($c & 240) >> 4 : $c & 15);
                    }
            }
        }

        $out = '';
        if (count($pixels) % 2) {
            $pixels[] = 0;
        }

        $cnt = count($pixels) / 2;

        for ($i = 0; $i < $cnt; $i++) {
            $out .= chr(16 * $pixels[2 * $i] + $pixels[2 * $i + 1]);
        }

        return $out;
    }

    
    public static function explode_url($url)
    {
        $protocol = "";
        $host = "";
        $path = "";
        $file = "";
        $res = "";

        $arr = parse_url($url);
        if ( isset($arr["scheme"]) ) {
            $arr["scheme"] = mb_strtolower($arr["scheme"]);
        }

        if (isset($arr["scheme"]) && $arr["scheme"] !== "file" && $arr["scheme"] !== "phar" && strlen($arr["scheme"]) > 1) {
            $protocol = $arr["scheme"] . "://";

            if (isset($arr["user"])) {
                $host .= $arr["user"];

                if (isset($arr["pass"])) {
                    $host .= ":" . $arr["pass"];
                }

                $host .= "@";
            }

            if (isset($arr["host"])) {
                $host .= $arr["host"];
            }

            if (isset($arr["port"])) {
                $host .= ":" . $arr["port"];
            }

            if (isset($arr["path"]) && $arr["path"] !== "") {
                
                if ($arr["path"][mb_strlen($arr["path"]) - 1] === "/") {
                    $path = $arr["path"];
                    $file = "";
                } else {
                    $path = rtrim(dirname($arr["path"]), '/\\') . "/";
                    $file = basename($arr["path"]);
                }
            }

            if (isset($arr["query"])) {
                $file .= "?" . $arr["query"];
            }

            if (isset($arr["fragment"])) {
                $file .= "#" . $arr["fragment"];
            }

        } else {

            $protocol = "";
            $host = ""; 

            $i = mb_stripos($url, "://");
            if ($i !== false) {
                $protocol = mb_strtolower(mb_substr($url, 0, $i + 3));
                $url = mb_substr($url, $i + 3);
            } else {
                $protocol = "file://";
            }

            if ($protocol === "phar://") {
                $res = substr($url, stripos($url, ".phar")+5);
                $url = substr($url, 7, stripos($url, ".phar")-2);
            }

            $file = basename($url);
            $path = dirname($url) . "/";
        }

        $ret = [$protocol, $host, $path, $file,
            "protocol" => $protocol,
            "host" => $host,
            "path" => $path,
            "file" => $file,
            "resource" => $res];
        return $ret;
    }

    
    public static function dompdf_debug($type, $msg)
    {
        global $_DOMPDF_DEBUG_TYPES, $_dompdf_show_warnings, $_dompdf_debug;
        if (isset($_DOMPDF_DEBUG_TYPES[$type]) && ($_dompdf_show_warnings || $_dompdf_debug)) {
            $arr = debug_backtrace();

            echo basename($arr[0]["file"]) . " (" . $arr[0]["line"] . "): " . $arr[1]["function"] . ": ";
            Helpers::pre_r($msg);
        }
    }

    
    public static function record_warnings($errno, $errstr, $errfile, $errline)
    {
        
        if (!($errno & (E_WARNING | E_NOTICE | E_USER_NOTICE | E_USER_WARNING | E_DEPRECATED | E_USER_DEPRECATED))) {
            throw new Exception($errstr . " $errno");
        }

        global $_dompdf_warnings;
        global $_dompdf_show_warnings;

        if ($_dompdf_show_warnings) {
            echo $errstr . "\n";
        }

        $_dompdf_warnings[] = $errstr;
    }

    
    public static function uniord(string $c, ?string $encoding = null)
    {
        if (function_exists("mb_ord")) {
            if (PHP_VERSION_ID < 80000 && $encoding === null) {
                
                $encoding = "UTF-8";
            }
            return mb_ord($c, $encoding);
        }

        if ($encoding != "UTF-8" && $encoding !== null) {
            $c = mb_convert_encoding($c, "UTF-8", $encoding);
        }

        $length = mb_strlen(mb_substr($c, 0, 1), '8bit');
        $ord = false;
        $bytes = [];
        $numbytes = 1;
        for ($i = 0; $i < $length; $i++) {
            $o = ord($c[$i]); 
            if (count($bytes) === 0) { 
                if ($o <= 0x7F) {
                    $ord = $o;
                    $numbytes = 1;
                } elseif (($o >> 0x05) === 0x06) { 
                    $bytes[] = ($o - 0xC0) << 0x06;
                    $numbytes = 2;
                } elseif (($o >> 0x04) === 0x0E) { 
                    $bytes[] = ($o - 0xE0) << 0x0C;
                    $numbytes = 3;
                } elseif (($o >> 0x03) === 0x1E) { 
                    $bytes[] = ($o - 0xF0) << 0x12;
                    $numbytes = 4;
                } else {
                    $ord = false;
                    break;
                }
            } elseif (($o >> 0x06) === 0x02) { 
                $bytes[] = $o - 0x80;
                if (count($bytes) === $numbytes) {
                    
                    $o = $bytes[0];
                    for ($j = 1; $j < $numbytes; $j++) {
                        $o += ($bytes[$j] << (($numbytes - $j - 1) * 0x06));
                    }
                    if ((($o >= 0xD800) and ($o <= 0xDFFF)) or ($o >= 0x10FFFF)) {
                        
                        
                        
                        
                        return false;
                    } else {
                        $ord = $o; 
                    }
                    
                    $bytes = [];
                    $numbytes = 1;
                }
            } else {
                $ord = false;
                break;
            }
        }

        return $ord;
    }

    
    public static function unichr(int $c, ?string $encoding = null)
    {
        if (function_exists("mb_chr")) {
            if (PHP_VERSION_ID < 80000 && $encoding === null) {
                
                $encoding = "UTF-8";
            }
            return mb_chr($c, $encoding);
        }

        $chr = false;
        if ($c <= 0x7F) {
            $chr = chr($c);
        } elseif ($c <= 0x7FF) {
            $chr = chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
        } elseif ($c <= 0xFFFF) {
            $chr = chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F)
            . chr(0x80 | $c & 0x3F);
        } elseif ($c <= 0x10FFFF) {
            $chr = chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F)
            . chr(0x80 | $c >> 6 & 0x3F)
            . chr(0x80 | $c & 0x3F);
        }

        return $chr;
    }

    
    public static function cmyk_to_rgb($c, $m = null, $y = null, $k = null)
    {
        if (is_array($c)) {
            [$c, $m, $y, $k] = $c;
        }

        $c *= 255;
        $m *= 255;
        $y *= 255;
        $k *= 255;

        $r = (1 - round(2.55 * ($c + $k)));
        $g = (1 - round(2.55 * ($m + $k)));
        $b = (1 - round(2.55 * ($y + $k)));

        if ($r < 0) {
            $r = 0;
        }
        if ($g < 0) {
            $g = 0;
        }
        if ($b < 0) {
            $b = 0;
        }

        return [
            $r, $g, $b,
            "r" => $r, "g" => $g, "b" => $b
        ];
    }

    
    public static function dompdf_getimagesize($filename, $context = null)
    {
        static $cache = [];

        if (isset($cache[$filename])) {
            return $cache[$filename];
        }

        [$width, $height, $type] = getimagesize($filename);

        
        $types = [
            IMAGETYPE_JPEG => "jpeg",
            IMAGETYPE_GIF  => "gif",
            IMAGETYPE_BMP  => "bmp",
            IMAGETYPE_PNG  => "png",
            IMAGETYPE_WEBP => "webp",
        ];

        $type = $types[$type] ?? null;

        if ($width == null || $height == null) {
            [$data] = Helpers::getFileContent($filename, $context);

            if ($data !== null) {
                if (substr($data, 0, 2) === "BM") {
                    $meta = unpack("vtype/Vfilesize/Vreserved/Voffset/Vheadersize/Vwidth/Vheight", $data);
                    $width = (int) $meta["width"];
                    $height = (int) $meta["height"];
                    $type = "bmp";
                } elseif (strpos($data, "<svg") !== false) {
                    $doc = new \Svg\Document();
                    $doc->loadFile($filename);

                    [$width, $height] = $doc->getDimensions();
                    $width = (float) $width;
                    $height = (float) $height;
                    $type = "svg";
                }
            }
        }

        return $cache[$filename] = [$width ?? 0, $height ?? 0, $type];
    }

    
    public static function imagecreatefrombmp($filename)
    {
        if (!function_exists("imagecreatetruecolor")) {
            trigger_error("The PHP GD extension is required, but is not installed.", E_ERROR);
            return false;
        }

        if (function_exists("imagecreatefrombmp") && ($im = imagecreatefrombmp($filename)) !== false) {
            return $im;
        }

        
        if (!($fh = fopen($filename, 'rb'))) {
            trigger_error('imagecreatefrombmp: Can not open ' . $filename, E_USER_WARNING);
            return false;
        }

        $bytes_read = 0;

        
        $meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));

        
        if ($meta['type'] != 19778) {
            trigger_error('imagecreatefrombmp: ' . $filename . ' is not a bitmap!', E_USER_WARNING);
            return false;
        }

        
        $meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));
        $bytes_read += 40;

        
        if ($meta['compression'] == 3) {
            $meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));
            $bytes_read += 12;
        }

        
        $meta['bytes'] = $meta['bits'] / 8;
        $meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4) - floor($meta['width'] * $meta['bytes'] / 4)));
        if ($meta['decal'] == 4) {
            $meta['decal'] = 0;
        }

        
        if ($meta['imagesize'] < 1) {
            $meta['imagesize'] = $meta['filesize'] - $meta['offset'];
            
            if ($meta['imagesize'] < 1) {
                $meta['imagesize'] = @filesize($filename) - $meta['offset'];
                if ($meta['imagesize'] < 1) {
                    trigger_error('imagecreatefrombmp: Can not obtain filesize of ' . $filename . '!', E_USER_WARNING);
                    return false;
                }
            }
        }

        
        $meta['colors'] = !$meta['colors'] ? pow(2, $meta['bits']) : $meta['colors'];

        
        $palette = [];
        if ($meta['bits'] < 16) {
            $palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));
            
            if ($palette[1] < 0) {
                foreach ($palette as $i => $color) {
                    $palette[$i] = $color + 16777216;
                }
            }
        }

        
        if ($meta['headersize'] > $bytes_read) {
            fread($fh, $meta['headersize'] - $bytes_read);
        }

        
        $im = imagecreatetruecolor($meta['width'], $meta['height']);
        $data = fread($fh, $meta['imagesize']);

        
        switch ($meta['compression']) {
            case 1:
                $data = Helpers::rle8_decode($data, $meta['width']);
                break;
            case 2:
                $data = Helpers::rle4_decode($data, $meta['width']);
                break;
        }

        $p = 0;
        $vide = chr(0);
        $y = $meta['height'] - 1;
        $error = 'imagecreatefrombmp: ' . $filename . ' has not enough data!';

        
        while ($y >= 0) {
            $x = 0;
            while ($x < $meta['width']) {
                switch ($meta['bits']) {
                    case 32:
                    case 24:
                        if (!($part = substr($data, $p, 3 ))) {
                            trigger_error($error, E_USER_WARNING);
                            return $im;
                        }
                        $color = unpack('V', $part . $vide);
                        break;
                    case 16:
                        if (!($part = substr($data, $p, 2 ))) {
                            trigger_error($error, E_USER_WARNING);
                            return $im;
                        }
                        $color = unpack('v', $part);

                        if (empty($meta['rMask']) || $meta['rMask'] != 0xf800) {
                            $color[1] = (($color[1] & 0x7c00) >> 7) * 65536 + (($color[1] & 0x03e0) >> 2) * 256 + (($color[1] & 0x001f) << 3); 
                        } else {
                            $color[1] = (($color[1] & 0xf800) >> 8) * 65536 + (($color[1] & 0x07e0) >> 3) * 256 + (($color[1] & 0x001f) << 3); 
                        }
                        break;
                    case 8:
                        $color = unpack('n', $vide . substr($data, $p, 1));
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    case 4:
                        $color = unpack('n', $vide . substr($data, floor($p), 1));
                        $color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    case 1:
                        $color = unpack('n', $vide . substr($data, floor($p), 1));
                        switch (($p * 8) % 8) {
                            case 0:
                                $color[1] = $color[1] >> 7;
                                break;
                            case 1:
                                $color[1] = ($color[1] & 0x40) >> 6;
                                break;
                            case 2:
                                $color[1] = ($color[1] & 0x20) >> 5;
                                break;
                            case 3:
                                $color[1] = ($color[1] & 0x10) >> 4;
                                break;
                            case 4:
                                $color[1] = ($color[1] & 0x8) >> 3;
                                break;
                            case 5:
                                $color[1] = ($color[1] & 0x4) >> 2;
                                break;
                            case 6:
                                $color[1] = ($color[1] & 0x2) >> 1;
                                break;
                            case 7:
                                $color[1] = ($color[1] & 0x1);
                                break;
                        }
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    default:
                        trigger_error('imagecreatefrombmp: ' . $filename . ' has ' . $meta['bits'] . ' bits and this is not supported!', E_USER_WARNING);
                        return false;
                }
                imagesetpixel($im, $x, $y, $color[1]);
                $x++;
                $p += $meta['bytes'];
            }
            $y--;
            $p += $meta['decal'];
        }
        fclose($fh);
        return $im;
    }

    
    public static function getFileContent($uri, $context = null, $offset = 0, $maxlen = null)
    {
        $content = null;
        $headers = null;
        [$protocol] = Helpers::explode_url($uri);
        $is_local_path = in_array(strtolower($protocol), ["", "file://", "phar://"], true);
        $can_use_curl = in_array(strtolower($protocol), ["http://", "https://"], true);

        set_error_handler([self::class, 'record_warnings']);

        try {
            if ($is_local_path || ini_get('allow_url_fopen') || !$can_use_curl) {
                if ($is_local_path === false) {
                    $uri = Helpers::encodeURI($uri);
                }
                if (isset($maxlen)) {
                    $result = file_get_contents($uri, false, $context, $offset, $maxlen);
                } else {
                    $result = file_get_contents($uri, false, $context, $offset);
                }
                if ($result !== false) {
                    $content = $result;
                }
                if (isset($http_response_header)) {
                    $headers = $http_response_header;
                }

            } elseif ($can_use_curl && function_exists('curl_exec')) {
                $curl = curl_init($uri);

                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HEADER, true);
                if ($offset > 0) {
                    curl_setopt($curl, CURLOPT_RESUME_FROM, $offset);
                }

                if ($maxlen > 0) {
                    curl_setopt($curl, CURLOPT_BUFFERSIZE, 128);
                    curl_setopt($curl, CURLOPT_NOPROGRESS, false);
                    curl_setopt($curl, CURLOPT_PROGRESSFUNCTION, function ($res, $download_size_total, $download_size, $upload_size_total, $upload_size) use ($maxlen) {
                        return ($download_size > $maxlen) ? 1 : 0;
                    });
                }

                $context_options = [];
                if (!is_null($context)) {
                    $context_options = stream_context_get_options($context);
                }
                foreach ($context_options as $stream => $options) {
                    foreach ($options as $option => $value) {
                        $key = strtolower($stream) . ":" . strtolower($option);
                        switch ($key) {
                            case "curl:curl_verify_ssl_host":
                                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, !$value ? 0 : 2);
                                break;
                            case "curl:max_redirects":
                                curl_setopt($curl, CURLOPT_MAXREDIRS, $value);
                                break;
                            case "http:follow_location":
                                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $value);
                                break;
                            case "http:header":
                                if (is_string($value)) {
                                    curl_setopt($curl, CURLOPT_HTTPHEADER, [$value]);
                                } else {
                                    curl_setopt($curl, CURLOPT_HTTPHEADER, $value);
                                }
                                break;
                            case "http:timeout":
                                curl_setopt($curl, CURLOPT_TIMEOUT, $value);
                                break;
                            case "http:user_agent":
                                curl_setopt($curl, CURLOPT_USERAGENT, $value);
                                break;
                            case "curl:curl_verify_ssl_peer":
                            case "ssl:verify_peer":
                                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $value);
                                break;
                        }
                    }
                }

                $data = curl_exec($curl);

                if ($data !== false && !curl_errno($curl)) {
                    switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
                        case 200:
                            $raw_headers = substr($data, 0, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
                            $headers = preg_split("/[\n\r]+/", trim($raw_headers));
                            $content = substr($data, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
                            break;
                    }
                }
                curl_close($curl);
            }
        } finally {
            restore_error_handler();
        }

        return [$content, $headers];
    }

    
    public static function mb_ucwords(string $str): string
    {
        $max_len = mb_strlen($str);
        if ($max_len === 1) {
            return mb_strtoupper($str);
        }

        $str = mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);

        foreach ([' ', '.', ',', '!', '?', '-', '+'] as $s) {
            $pos = 0;
            while (($pos = mb_strpos($str, $s, $pos)) !== false) {
                $pos++;
                
                if ($pos !== false && $pos < $max_len) {
                    
                    if ($pos + 1 < $max_len) {
                        $str = mb_substr($str, 0, $pos) . mb_strtoupper(mb_substr($str, $pos, 1)) . mb_substr($str, $pos + 1);
                    } else {
                        $str = mb_substr($str, 0, $pos) . mb_strtoupper(mb_substr($str, $pos, 1));
                    }
                }
            }
        }

        return $str;
    }

    
    public static function lengthEqual(float $a, float $b): bool
    {
        
        
        
        
        static $epsilon = 1e-8;
        static $almostZero = 1e-12;

        $diff = abs($a - $b);

        if ($a === $b || $diff < $almostZero) {
            return true;
        }

        return $diff < $epsilon * max(abs($a), abs($b));
    }

    
    public static function lengthLess(float $a, float $b): bool
    {
        return $a < $b && !self::lengthEqual($a, $b);
    }

    
    public static function lengthLessOrEqual(float $a, float $b): bool
    {
        return $a <= $b || self::lengthEqual($a, $b);
    }

    
    public static function lengthGreater(float $a, float $b): bool
    {
        return $a > $b && !self::lengthEqual($a, $b);
    }

    
    public static function lengthGreaterOrEqual(float $a, float $b): bool
    {
        return $a >= $b || self::lengthEqual($a, $b);
    }
}
