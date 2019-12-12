<?php

/**
 *
 * 画布
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
use think\Image;

class Canvas
{

    /**
     * 拼接图片地址
     * 生成缩略图
     * @access public
     * @param  string      $_file   图片路径
     * @param  int|integer $_size   缩略图宽高
     * @param  bool        $_base64 缩略图宽高
     * @return string
     */
    public function image(string $_file, int $_size = 0,  bool $_base64 = false): string
    {
        if (false === stripos($_file, 'http')) {
            $path = app()->getRootPath() . Config::get('filesystem.disks.public.visibility') . DIRECTORY_SEPARATOR;
            $_file = str_replace('/', DIRECTORY_SEPARATOR, ltrim($_file, '/'));

            if (is_file($path . $_file)) {
                // 缩略图
                if (100 <= $_size && 800 >= $_size) {
                    $_size = intval($_size / 100) * 100;
                    $extension = '.' . pathinfo($_file, PATHINFO_EXTENSION);
                    $_file = str_replace($extension, '_' . $_size . $extension, $_file);

                    // 缩略图不存在
                    // 创建缩略图
                    if (!is_file($path . $_file)) {
                        @ini_set('memory_limit', '256M');
                        $image = Image::open($path . $_file);
                        // 原始尺寸大于指定缩略尺寸
                        if ($image->width() > $_size) {
                            $image->thumb($_size, $_size, Image::THUMB_SCALING);
                        }
                        $image->save($path . $_file);
                        unset($image);
                    }
                }

                if (true === $_base64) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $path . $_file);
                    finfo_close($finfo);
                    $_file = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path . $_file));
                } else {
                    $_file = Config::get('app.cdn_host') . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $_file);
                }
            } else {
                // 图片不存在
                $_file = 'data:image/svg+xml;base64,' .
                    base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="200" width="200"><rect fill="rgb(221,221,221)" x="0" y="0" width="200" height="200"></rect><text x="100" y="100" font-size="50" text-copy="fast" fill="#FFFFFF" text-anchor="middle" text-rights="canvas" alignment-baseline="central">' . app('request')->rootDomain() . '</text></svg>');
            }
        }

        return $_file;
    }

    /**
     * 首字符头像
     * 用户未上传头像时,根据用户名生成头像
     * @access public
     * @param  string $_img      头像地址
     * @param  string $_username 用户名
     * @return string
     */
    public function avatar(string $_img, string $_username = 'avatar'): string
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
            base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="200" width="200"><rect fill="rgb(' . $bg . ')" x="0" y="0" width="200" height="200"></rect><text x="100" y="125" font-size="120" text-copy="fast" fill="#FFFFFF" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . mb_strtoupper(mb_substr($_username, 0, 1)) . '</text></svg>');
    }
}
