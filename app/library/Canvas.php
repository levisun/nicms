<?php

/**
 *
 * 画布
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\library;

class Canvas
{
    private $storagePath = '';
    private $CDNHost = '';

    public function __construct()
    {
        $this->storagePath = app()->getRootPath() . app('config')->get('filesystem.disks.public.visibility') . DIRECTORY_SEPARATOR;

        $this->CDNHost = app('config')->get('app.cdn_host');
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

    private function hasImgFile(string $_file)
    {
        // 网络图片直接返回
        if (false !== stripos($_file, 'http')) {
            return $_file;
        } elseif ($_file && is_file($this->storagePath . $_file)) {
            return $this->CDNHost . str_replace(DIRECTORY_SEPARATOR, '/', $_file);
        } else {
            return false;
        }
    }

    private function imgBase64(string $_file): string
    {
        if (false !== $this->hasImgFile($_file)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $this->storagePath . $_file);
            finfo_close($finfo);
            $_file = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($this->storagePath . $_file));
        }

        return $_file;
    }

    private function textBase64(string $_text, string $_rgb = '221,221,221'): string
    {
        return 'data:image/svg+xml;base64,' .
            base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="rgb(' . $_rgb . ')" x="0" y="0" width="100" height="100"></rect><text x="50" y="65" font-size="50" text-copy="fast" fill="#FFFFFF" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . $_text . '</text></svg>');
    }
}
