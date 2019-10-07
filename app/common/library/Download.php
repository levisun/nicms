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

use think\Response;
use think\exception\HttpResponseException;
use app\common\library\Base64;

class Download
{
    private $extension = [
        'doc',
        'docx',
        'gif',
        'gz',
        'jpeg',
        'mp4',
        'pdf',
        'png',
        'ppt',
        'pptx',
        'rar',
        'xls',
        'xlsx',
        'zip'
    ];

    /**
     * 下载地址
     * @access public
     * @param  string $_file
     * @return string
     */
    public function url(string $_file)
    {
        $_file = DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($_file, ',.\/'));

        $path = app('config')->get('filesystem.disks.public.root');
        if (is_file($path . $_file) && in_array(pathinfo($_file, PATHINFO_EXTENSION), $this->extension)) {
            $_file = Base64::encrypt($_file, app('request')->ip() . date('Ymd'));
            $_file = app('config')->get('app.api_host') . '/download.do?file=' . urlencode($_file);
        } else {
            $_file = '/';
        }

        return $_file;
    }

    /**
     * 文件下载
     * @access public
     * @param
     * @return
     */
    public function file(string $_file)
    {
        $_file = $_file
            ? Base64::decrypt($_file, app('request')->ip() . date('Ymd'))
            : '';

        if ($_file) {
            $_file = DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($_file, ',.\/'));

            $_file = app('config')->get('filesystem.disks.public.root') . $_file;

            if (is_file($_file) && in_array(pathinfo($_file, PATHINFO_EXTENSION), $this->extension)) {
                $response = Response::create($_file, 'file')
                    ->name(md5(pathinfo($_file, PATHINFO_BASENAME) . date('Ymd')))
                    ->isContent(false)
                    ->expire(28800);
            }
        } else {
            $error = '<style type="text/css">*{padding:0; margin:0;}body{background:#fff; font-family:"Century Gothic","Microsoft yahei"; color:#333;font-size:18px;}section{text-align:center;margin-top: 50px;}h2,h3{font-weight:normal;margin-bottom:12px;margin-right:12px;display:inline-block;}</style><title>404</title><section><h2>404</h2><h3>Oops! Page not found.</h3></section>';
            $response = Response::create($error, '', 404);
        }

        $log  = '[API] 下载文件:' . app('request')->param('file', 'null');
        $log .= ' 文件地址:' . $_file . PHP_EOL;
        $log .= 'PARAM:' . json_encode(app('request')->param('', '', 'trim'), JSON_UNESCAPED_UNICODE);
        app('log')->record($log, 'error')->save();

        throw new HttpResponseException($response);
    }
}
