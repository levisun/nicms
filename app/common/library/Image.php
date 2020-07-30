<?php

/**
 *
 * 图像
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Config;
use think\facade\Request;
use think\Image as ThinkImage;
use app\common\library\Filter;

class Image
{

    /**
     * 获得缩略图访问地址
     * @access public
     * @static
     * @param  string $_file
     * @param  int    $_size 整十数
     * @return string
     */
    public static function thumb(string $_img, int $_size = 0): string
    {
        if (self::has($_img) && $_size >= 10 && $_size <= 800) {
            $_img = Filter::safe($_img);
            $_img = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $_img);

            $_size = intval($_size / 10) * 10;

            $new_file = md5($_img . $_size) . '.' . pathinfo($_img, PATHINFO_EXTENSION);

            $path = public_path('storage/uploads/thumb');
            is_dir($path) or mkdir($path, 0755, true);

            if (!is_file($path . $new_file)) {
                @ini_set('memory_limit', '128M');
                $origin = root_path() . Config::get('filesystem.disks.public.visibility') . DIRECTORY_SEPARATOR;
                $image = ThinkImage::open($origin . $_img);
                if ($image->width() > $_size) {
                    $image->thumb($_size, $_size, ThinkImage::THUMB_SCALING);
                }
                $image->save($path . $new_file);
                unset($image);
            }

            $_img = Config::get('app.img_host') . '/storage/uploads/thumb/' .
                str_replace(DIRECTORY_SEPARATOR, '/', $new_file);
        } else {
            $_img = self::miss();
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
    public static function path(string $_img): string
    {
        if (!$_img = self::has($_img)) {
            $_img = self::miss();
        }

        return $_img;
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
        if (!$_img = self::has($_img)) {
            $length = mb_strlen($_username);
            $salt = mb_strlen(Request::rootDomain());
            $bg = (intval($length * $salt) % 255) . ',' . (intval($length * $salt * 3) % 255) . ',' . (intval($length * $salt * 9) % 255);

            $_img = 'data:image/svg+xml;base64,' .
                base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="rgb(' . $bg . ')" x="0" y="0" width="100" height="100"></rect><text x="50" y="65" font-size="50" text-copy="fast" fill="#FFFFFF" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . mb_strtoupper(mb_substr($_username, 0, 1)) . '</text></svg>');
        }

        return $_img;
    }

    /**
     * 校验图片是否存在
     * @access private
     * @static
     * @param  string $_img 头像地址
     * @return string|false
     */
    private static function has(string $_img)
    {
        if (0 === stripos($_img, 'http')) {
            return $_img;
        }

        $path = root_path() . Config::get('filesystem.disks.public.visibility') . DIRECTORY_SEPARATOR;

        $_img = Filter::safe($_img);
        $_img = str_replace(DIRECTORY_SEPARATOR, '/', $_img);

        if ($_img && is_file($path . $_img)) {
            return Config::get('app.img_host') . '/' . $_img;
        }

        return false;
    }

    /**
     * 默认图像
     * @access private
     * @static
     * @return string
     */
    private static function miss(): string
    {
        return 'data:image/svg+xml;base64,' .
            base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="rgb(221,221,221)" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="15" text-copy="fast" fill="#000000" text-anchor="middle" text-rights="canvas" alignment-baseline="central">' . Request::rootDomain() . '</text></svg>');
    }
}
