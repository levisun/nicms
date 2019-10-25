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

use think\App;
use think\facade\Config;

class Canvas
{

    private $storagePath = '';
    private $CDNHost = '';

    public function __construct()
    {
        $this->storagePath = app()->getRootPath() . Config::get('filesystem.disks.public.visibility') . DIRECTORY_SEPARATOR;

        $this->CDNHost = Config::get('app.cdn_host');
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
    public function avatar(string $_username = 'avatar'): string
    {
        $length = mb_strlen($_username);
        $salt = mb_strlen(app('request')->rootDomain());
        $rgb = (intval($length * $salt) % 255) . ',' . (intval($length * $salt * 3) % 255) . ',' . (intval($length * $salt * 9) % 255);

        return $this->textBase64(mb_strtoupper(mb_substr($_username, 0, 1)), $rgb);
    }

    public function imgBase64(string $_file): string
    {
        if (false !== stripos($_file, 'http')) {
            # code...
        } elseif ($_file && is_file($this->storagePath . $_file)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $this->storagePath . $_file);
            finfo_close($finfo);
            $_file = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($this->storagePath . $_file));
        } else {
            $_file = $this->textBase64(app('request')->rootDomain(), '221,221,221', 50);
        }

        return $_file;
    }

    public function textBase64(string $_text, string $_rgb = '221,221,221', int $_font_size = 250): string
    {
        $y = 200 + ceil($_font_size / 3.5);
        return 'data:image/svg+xml;base64,' .
            base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="400" width="400"><rect fill="rgb(' . $_rgb . ')" x="0" y="0" width="400" height="400"></rect><text x="200" y="' . $y . '" font-size="' . $_font_size . '" text-copy="fast" fill="#FFFFFF" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . $_text . '</text></svg>');
    }
}
