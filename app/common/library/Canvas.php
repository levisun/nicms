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
     * @param  string      $_img   图片路径
     * @param  int|integer $_size  缩略图宽高
     * @param  string      $_water 水印文字
     * @return string
     */
    public function image(string $_img, int $_size = 300, string $_water = '', bool $_base64 = false): string
    {
        // 网络图片直接返回
        if (false !== stripos($_img, 'http')) {
            return $_img;
        }

        $path = app()->getRootPath() . Config::get('filesystem.disks.public.visibility') . DIRECTORY_SEPARATOR;
        $_img = str_replace('/', DIRECTORY_SEPARATOR, ltrim($_img, '/'));

        // 图片不存在
        if (!is_file($path . $_img)) {
            return 'data:image/svg+xml;base64,' .
                base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="200" width="200"><rect fill="rgb(221,221,221)" x="0" y="0" width="200" height="200"></rect><text x="100" y="100" font-size="50" text-copy="fast" fill="#FFFFFF" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . app('request')->rootDomain() . '</text></svg>');
        }

        // 缩略图
        $_size = $_size > 800 ? 800 : intval($_size / 100) * 100;
        $ext = '.' . pathinfo($_img, PATHINFO_EXTENSION);
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
            base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="200" width="200"><rect fill="rgb(' . $bg . ')" x="0" y="0" width="100" height="100"></rect><text x="50" y="65" font-size="50" text-copy="fast" fill="#FFFFFF" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . mb_strtoupper(mb_substr($_username, 0, 1)) . '</text></svg>');
    }
}
