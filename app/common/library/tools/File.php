<?php

/**
 *
 * 文件
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\tools;

use think\facade\Config;
use think\facade\Request;
use think\Image as ThinkImage;
use app\common\library\Base64;
use app\common\library\Filter;

class File
{

    /**
     * 获得缩略图访问地址
     * @access public
     * @static
     * @param  string $_file
     * @param  int    $_size 整十数
     * @return string
     */
    public static function thumb(string $_img, int $_size = 100): string
    {
        if (!self::imgHas($_img)) {
            return self::imgMiss();
        }

        $_size = intval($_size / 10) * 10;
        $_size = 800 > $_size ? $_size : 800;
        $_size = 10 < $_size ? $_size : 10;

        $_img = trim($_img, '\/.');
        $_img = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_img);

        $thumb_file = md5($_img . $_size) . '.' . pathinfo($_img, PATHINFO_EXTENSION);

        $path = public_path('storage/uploads/thumb/' . substr($thumb_file, 0, 2));
        if (!is_dir($path)) mkdir($path, 0755, true);

        if (!is_file($path . $thumb_file)) {
            @ini_set('memory_limit', '128M');
            $image = ThinkImage::open(public_path() . $_img);
            if ($image->width() > $_size) {
                $image->thumb($_size, $_size, ThinkImage::THUMB_SCALING);
            }
            $image->save($path . $thumb_file);
            unset($image);
        }

        return Config::get('app.img_host') . 'storage/uploads/thumb/' . str_replace(DIRECTORY_SEPARATOR, '/', $thumb_file);
    }



    /**
     * 首字符头像
     * 用户未上传头像时,根据用户名生成头像
     * @access public
     * @static
     * @param  string $_img      头像地址
     * @param  string $_username 用户名
     * @return string
     */
    public static function avatar(string $_img, string $_username = 'avatar'): string
    {
        if (!$_img = self::imgHas($_img)) {
            $length = mb_strlen($_username, 'utf-8');
            $salt = strlen(Request::rootDomain());
            $bg = (intval($length * $salt) % 255) . ',' . (intval($length * $salt * 3) % 255) . ',' . (intval($length * $salt * 9) % 255);

            $_img = 'data:image/svg+xml;base64,' .
                base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="rgb(' . $bg . ')" x="0" y="0" width="100" height="100"></rect><text x="50" y="65" font-size="50" text-copy="fast" fill="#FFFFFF" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . mb_strtoupper(mb_substr($_username, 0, 1)) . '</text></svg>');
        }

        return $_img;
    }



    /**
     * 获得图片访问路径
     * @access public
     * @static
     * @param  string $_file
     * @return string
     */
    public static function imgUrl(string $_img): string
    {
        if (!$_img = self::imgHas($_img)) {
            $_img = self::imgMiss();
        }

        return $_img;
    }

    /**
     * 校验文件是否存在
     * @access private
     * @static
     * @param  string $_file 文件
     * @return string|false
     */
    private static function imgHas(string $_file)
    {
        if (0 === stripos($_file, 'http')) {
            return $_file;
        }

        $_file = Filter::strict($_file);
        $_file = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_file);

        if ($_file && is_file(public_path() . $_file)) {
            $extension = pathinfo($_file, PATHINFO_EXTENSION);
            if (in_array($extension, ['jpg','gif','png','webp'])) {
                return Config::get('app.img_host') . str_replace(DIRECTORY_SEPARATOR, '/', $_file);
            } else {
                return Config::get('app.static_host') . str_replace(DIRECTORY_SEPARATOR, '/', $_file);
            }

        }

        return false;
    }

    /**
     * 默认图像
     * @access private
     * @static
     * @return string
     */
    private static function imgMiss(): string
    {
        return 'data:image/svg+xml;base64,' .
            base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="rgb(221,221,221)" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="15" text-copy="fast" fill="#000000" text-anchor="middle" text-rights="canvas" alignment-baseline="central">' . Request::rootDomain() . '</text></svg>');
    }

    /**
     * 获得文件地址(加密)
     * @access public
     * @param  string $_file
     * @param  bool   $_abs
     * @return string
     */
    public static function pathEncode(string $_file, bool $_abs = false): string
    {
        $_file = Filter::strict($_file);
        $_file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_file);

        $path = Config::get('filesystem.disks.public.root') . DIRECTORY_SEPARATOR;
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $salt = bindec(Request::ip2bin(Request::ip())) . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        $salt = sha1($salt);

        if (is_file($path . $_file)) {
            return $_abs ? Base64::encrypt($path . $_file, $salt) : Base64::encrypt($_file, $salt);
        }

        return '';
    }

    /**
     * 获得文件地址(解密)
     * @access public
     * @param  string $_file
     * @param  bool   $_abs
     * @return string
     */
    public static function pathDecode(string $_file, bool $_abs = false): string
    {
        $salt = bindec(Request::ip2bin(Request::ip())) . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        $salt = sha1($salt);

        $_file = $_file ? Base64::decrypt($_file, $salt) : '';

        if ($_file && false !== preg_match('/^[a-zA-Z0-9_\/\\\]+\.[a-zA-Z0-9]{2,4}$/u', $_file)) {
            $_file = Filter::strict($_file);
            $_file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $_file);

            $path = Config::get('filesystem.disks.public.root') . DIRECTORY_SEPARATOR;
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

            if (is_file($path . $_file)) {
                return $_abs ? $path . $_file : $_file;
            }
        }
        return '';
    }

    /**
     * 获取目录中所有文件
     * yield 生成器
     * @access public
     * @param  string $_dir
     * @return yield
     */
    public static function glob(string $_dir)
    {
        $_dir = rtrim($_dir, '\/*.');
        if (is_readable($_dir)) {
            $dh = opendir($_dir);
            while ($file = readdir($dh)) {
                if ('.' === substr($file, 0, 1))
                    continue;

                $path = $_dir . DIRECTORY_SEPARATOR . $file;

                yield $path;

                if (is_dir($path)) {
                    $sub = self::glob($path);
                    while ($sub->valid()) {
                        yield $sub->current();
                        $sub->next();
                    }
                }
            }
        }
    }
}
