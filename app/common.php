<?php

/**
 *
 * 应用公共文件
 *
 * @package   NICMS
 * @category  app
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

use think\Image;
use think\facade\Config;
use think\facade\Route;
use think\facade\Session;
use app\common\library\Base64;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
/**
 * 是否微信请求
 * @param
 * @return boolean
 */
function isWechat(): bool
{
    return strpos(app('request')->server('HTTP_USER_AGENT'), 'MicroMessenger') !== false ? true : false;
}

if (!function_exists('client_mac')) {
    /**
     * 客户端网卡物理MAC值
     * @return string
     */
    function client_mac()
    {
        $os = strtolower(PHP_OS);
        if (!in_array($os, ['unix', 'solaris', 'aix'])) {
            if ('linux' == $os) {
                @exec('ifconfig -a', $result);
            } else {
                @exec('ipconfig /all', $result);
                if (!$result) {
                    $ipconfig = DIRECTORY_SEPARATOR . 'system32' . DIRECTORY_SEPARATOR . 'ipconfig.exe';
                    if (is_file($_SERVER['WINDIR'] . $ipconfig)) {
                        @exec($_SERVER['WINDIR'] . $ipconfig . " /all", $result);
                    } else {
                        @exec($_SERVER['WINDIR'] . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'ipconfig.exe /all', $result);
                    }
                }
            }

            foreach ($result as $value) {
                if (preg_match('/([0-9a-f]{2}[\:\-]{1}){5}[0-9a-f]{2}/i', $value, $mac)) {
                    return $mac[0];
                }
            }
        }
    }
}

if (!function_exists('emoji_encode')) {
    /**
     * Emoji原形转换为String
     * @param  string $_str
     * @return string
     */
    function emoji_encode($_str): string
    {
        return json_decode(preg_replace_callback('/(\\\u[ed][0-9a-f]{3})/si', function ($matches) {
            return '[EMOJI:' . base64_encode($matches[0]) . ']';
        }, json_encode($_str)));
    }
}

if (!function_exists('emoji_decode')) {
    /**
     * Emoji字符串转换为原形
     * @param  string $_str
     * @return string
     */
    function emoji_decode($_str)
    {
        return json_decode(preg_replace_callback('/(\[EMOJI:[A-Za-z0-9]{8}\])/', function ($matches) {
            return base64_decode(str_replace(['[EMOJI:', ']'], '', $matches[0]));
        }, json_encode($_str)));
    }
}

if (!function_exists('emoji_clear')) {
    /**
     * Emoji字符串清清理
     * @param  string $_str
     * @return string
     */
    function emoji_clear($_str): string
    {
        return preg_replace_callback('/./u', function (array $matches) {
            return strlen($matches[0]) >= 4 ? '' : $matches[0];
        }, $_str);
    }
}

if (!function_exists('format_size')) {
    /**
     * 格式化文件大小
     * @param  int $_file_size
     * @return string
     */
    function format_size(int $_file_size): string
    {
        if ($_file_size >= 1073741824) {
            $_file_size = round($_file_size / 1073741824 * 100) / 100 . ' GB';
        } elseif ($_file_size >= 1048576) {
            $_file_size = round($_file_size / 1048576 * 100) / 100 . ' MB';
        } elseif ($_file_size >= 1024) {
            $_file_size = round($_file_size / 1024 * 100) / 100 . ' KB';
        } else {
            $_file_size = $_file_size . ' 字节';
        }

        return $_file_size;
    }
}


if (!function_exists('remove_img')) {
    /**
     * 删除图片
     * @param  string $_img 图片路径
     * @return bool
     */
    function remove_img(string $_img): bool
    {
        // 网络图片直接返回
        if (false !== stripos($_img, 'http')) {
            return true;
        }

        $path = app()->getRootPath() . Config::get('filesystem.disks.public.visibility') . DIRECTORY_SEPARATOR;
        $_img = str_replace('/', DIRECTORY_SEPARATOR, ltrim($_img, '/'));
        $ext = '.' . pathinfo($_img, PATHINFO_EXTENSION);
        $_img = str_replace($ext, '_skl' . $ext, $_img);

        if (is_file($path . $_img)) {
            for ($i = 1; $i <= 8; $i++) {
                $size = $i * 100;
                $thumb = str_replace($ext, '_' . $size . $ext, $_img);
                if (is_file($path . $thumb)) {
                    @unlink($path . $thumb);
                }
            }
            @unlink($path . $_img);
        }

        return true;
    }
}

if (!function_exists('get_img_url')) {
    /**
     * 拼接图片地址
     * 生成缩略图
     * @param  string      $_img   图片路径
     * @param  int|integer $_size  缩略图宽高
     * @param  string      $_water 水印文字
     * @return string
     */
    function get_img_url(string $_img, int $_size = 300, string $_water = '', bool $_base64 = false): string
    {
        // 网络图片直接返回
        if (false !== stripos($_img, 'http')) {
            return $_img;
        }

        $path = app()->getRootPath() . Config::get('filesystem.disks.public.visibility') . DIRECTORY_SEPARATOR;
        $_img = str_replace('/', DIRECTORY_SEPARATOR, ltrim($_img, '/'));
        $ext = '.' . pathinfo($_img, PATHINFO_EXTENSION);

        if (!is_file($path . $_img)) {
            return 'data:image/svg+xml;base64,' .
                base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="800" width="800"><rect fill="rgb(221,221,221)" x="0" y="0" width="800" height="800"></rect><text x="400" y="400" font-size="50" text-copy="fast" fill="#FFFFFF" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . app('request')->rootDomain() . '</text></svg>');
        }

        // 缩略图
        $_size = $_size > 800 ? 800 : intval($_size / 100) * 100;
        $thumb = str_replace($ext, '_' . $_size . $ext, $_img);

        if (!is_file($path . $thumb)) {
            // 创建缩略图
            @ini_set('memory_limit', '256M');
            $image = Image::open($path . $_img);
            // 原始尺寸大于指定缩略尺寸
            if ($image->width() > $_size) {
                $image->thumb($_size, $_size, Image::THUMB_SCALING);
            }
            // 添加水印
            $_water = $_water ? $_water : app('request')->rootDomain();
            $font_path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR . 'simhei.ttf';
            $image->text($_water, $font_path, 15, '#00000000', Image::WATER_SOUTHEAST);
            $image->save($path . $thumb, null, 40);
        }

        if (true === $_base64) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $path . $thumb);
            finfo_close($finfo);
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path . $thumb));
        } else {
            return Config::get('app.cdn_host') . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $thumb);
        }
    }
}

if (!function_exists('avatar')) {
    /**
     * 首字符头像
     * 用户未上传头像时,根据用户名生成头像
     * @param  string $_img      头像地址
     * @param  string $_username 用户名
     * @return string
     */
    function avatar(string $_img, string $_username = 'avatar'): string
    {
        $path = app()->getRootPath() . Config::get('filesystem.disks.public.visibility') . DIRECTORY_SEPARATOR;
        $_img = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($_img, '/'));

        if ($_img && is_file($path . $_img)) {
            return Config::get('app.cdn_host') . str_replace(DIRECTORY_SEPARATOR, '/', $_img);
        }

        $length = mb_strlen($_username);
        $salt = mb_strlen(app('request')->rootDomain());
        $bg = (intval($length * $salt) % 255) . ',' . (intval($length * $salt * 3) % 255) . ',' . (intval($length * $salt * 9) % 255);

        return 'data:image/svg+xml;base64,' .
            base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="rgb(' . $bg . ')" x="0" y="0" width="100" height="100"></rect><text x="50" y="65" font-size="50" text-copy="fast" fill="#FFFFFF" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . mb_strtoupper(mb_substr($_username, 0, 1)) . '</text></svg>');
    }
}

if (!function_exists('create_authorization')) {
    /**
     * API授权字符串
     * @param
     * @return string
     */
    function create_authorization(): string
    {
        $time = app('request')->time();
        $jti  = Base64::encrypt(Session::getId(false));
        $uid  = Session::has('client_token') ? Session::get('client_token') : md5(app('request')->ip());

        $key  = app('request')->ip();
        $key .= $key . app('request')->rootDomain();
        $key .= $key . app('request')->server('HTTP_USER_AGENT');
        $key = md5(Base64::encrypt($key));

        $token = (new Builder)
            ->issuedBy(app('request')->rootDomain())                // Configures the issuer (iss claim)
            ->permittedFor(parse_url(app('request')->url(true), PHP_URL_HOST))   // Configures the audience (aud claim)
            ->identifiedBy($jti, false)                             // Configures the id (jti claim), replicating as a header item
            ->issuedAt($time)                                       // Configures the time that the token was issue (iat claim)
            ->canOnlyBeUsedAfter($time + 60)                        // Configures the time that the token can be used (nbf claim)
            ->expiresAt($time + 28800)                              // Configures the expiration time of the token (exp claim)
            ->withClaim('uid', $uid)                                // Configures a new claim, called "uid"
            ->getToken(new Sha256, new Key($key));                  // Retrieves the generated token

        return 'Bearer ' . (string) $token;
    }
}

if (!function_exists('url')) {
    /**
     * Url生成
     * @param  string $_url  路由地址
     * @param  array  $_vars 变量
     * @param  string $_sub  子域名
     * @return string
     */
    function url(string $_url = '', array $_vars = []): string
    {
        return (string) Route::buildUrl('/' . $_url, $_vars)->suffix(true)->domain(false);
    }
}
