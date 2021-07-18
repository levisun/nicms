<?php

/**
 *
 * 存储
 *
 * @package   NICMS
 * @category  app\common\library
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2021
 */

declare(strict_types=1);

namespace app\common\library;

use think\facade\Request;
use app\common\library\Base64;

class Storage
{
    private static $mime_type = [
        'css' => 'text/css',
    ];

    public static function static()
    {
        $referer = parse_url(Request::server('HTTP_REFERER'), PHP_URL_HOST);
        if (!$referer || false === stripos($referer, Request::rootDomain())) {
            trace('MISS ' . Request::ip(), 'warning');
            return miss(404);
        }

        $file_path = public_path('theme/' . app('http')->getName());
        $file_path .= str_replace('/', DIRECTORY_SEPARATOR, trim(Request::baseUrl(), '\/'));
        if (!is_file($file_path)) {
            trace('MISS ' . Request::ip(), 'warning');
            return miss(404);
        }

        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $data = file_get_contents($file_path);

        $response = \think\Response::create($data);

        $response->allowCache(true)
            ->contentType(self::$mime_type[$extension])
            ->cacheControl('max-age=2592000,must-revalidate')
            ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
            ->expires(gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');

        return $response;
    }
}
