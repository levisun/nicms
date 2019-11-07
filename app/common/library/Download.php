<?php

/**
 *
 * 下载类
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
use think\Response;
use think\exception\HttpResponseException;
use app\common\library\Base64;

class Download
{
    private $extension = [
        'doc', 'docx', 'gif', 'gz', 'jpeg', 'mp4', 'pdf', 'png', 'ppt', 'pptx', 'rar', 'xls', 'xlsx', 'zip',
        'webp',
    ];
    private $salt = '';

    public function __construct()
    {
        $this->salt = md5(date('Ymd'));
    }

    /**
     * 下载地址
     * @access public
     * @param  string $_filename
     * @return string
     */
    public function getUrl(string $_filename): string
    {
        $_filename = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($_filename, " ,._-\t\n\r\0\x0B"));
        $_filename = str_replace(['storage' . DIRECTORY_SEPARATOR, 'uploads' . DIRECTORY_SEPARATOR], '', $_filename);
        $_filename = Base64::encrypt($_filename, $this->salt);
        return Config::get('app.api_host') . '/download.do?file=' . urlencode($_filename);
    }

    /**
     * 文件下载
     * @access public
     * @param  string $_filename
     * @return void
     */
    public function file(string $_filename): void
    {
        $_filename = $_filename ? Base64::decrypt($_filename, $this->salt) : '';

        if ($_filename && !!preg_match('/^[a-zA-Z0-9_\/\\\]+\.[a-zA-Z]{2,4}$/u', $_filename)) {
            $_filename = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($_filename, " ,._-\t\n\r\0\x0B"));

            $path = Config::get('filesystem.disks.public.root') . DIRECTORY_SEPARATOR .
                'uploads' . DIRECTORY_SEPARATOR;
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

            if (is_file($path . $_filename) && in_array(pathinfo($path . $_filename, PATHINFO_EXTENSION), $this->extension)) {
                $response = Response::create($path . $_filename, 'file')
                    ->name(md5(pathinfo($_filename, PATHINFO_BASENAME) . date('Ymd')))
                    ->isContent(false)
                    ->expire(28800);
                throw new HttpResponseException($response);
            }
        }

        // $log  = '[API] 下载文件:' . request()->param('file', 'null');
        // $log .= 'PARAM:' . json_encode(request()->param('', '', 'trim'), JSON_UNESCAPED_UNICODE);
        // Log::record($log, 'error')->save();
    }
}
