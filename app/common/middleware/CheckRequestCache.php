<?php

/**
 *
 * 请求缓存
 *
 * @package   NICMS
 * @category  app\common\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\facade\Config;
use think\Request;
use think\Response;

class CheckRequestCache
{

    /**
     * 设置当前地址的请求缓存
     * 缓存为浏览器
     * 安全原因不写文件缓存,文件缓存无法根据客户记录
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->isGet() && $ms = $request->server('HTTP_IF_MODIFIED_SINCE')) {
            if (strtotime($ms) >= $request->server('REQUEST_TIME')) {
                return Response::create()->code(304);   // 读取缓存
            }
        }

        // 模板静态缓存路径
        $html_path = app()->getRuntimePath() . 'compile' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        // $html_path .= str_replace('/', '_', trim(app('request')->baseUrl(), '/'));
        $html_path .= md5(app('request')->baseUrl()) . '.html';

        // 读取模板静态缓存
        if (is_file($html_path) && filemtime($html_path) > strtotime('-3 hour')) {
            $time = $request->server('REQUEST_TIME') + 1440;
            $content = file_get_contents($html_path);
            if (function_exists('gzcompress')) {
                $content = gzuncompress($content);
            }
            return Response::create($content)
                ->allowCache(true)
                ->cacheControl('max-age=1440,must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', $time) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s', $time) . ' GMT')
                ->header(['X-Powered-By' => 'NICMS']);
        }

        $response = $next($request);

        // API有独立缓存定义,请勿开启缓存
        // API缓存在\app\common\controller\Async::result方法定义
        if ('api' !== app('http')->getName()) {
            // 调试模式关闭缓存
            $response->allowCache(!app()->isDebug());

            $response->header(array_merge(['X-Powered-By' => 'NICMS'], $response->getHeader()));
            if (200 == $response->getCode() && $request->isGet() && $response->isAllowCache()) {
                $time = $request->server('REQUEST_TIME') + 1440;
                $response->allowCache(true)
                    ->cacheControl('max-age=1440,must-revalidate')
                    ->expires(gmdate('D, d M Y H:i:s', $time) . ' GMT')
                    ->lastModified(gmdate('D, d M Y H:i:s', $time) . ' GMT');

                // 生成模板静态缓存
                if (!is_file($html_path) || filemtime($html_path) < strtotime('-3 hour')) {
                    is_dir(dirname($html_path)) or mkdir(dirname($html_path), 0755, true);
                    if (function_exists('gzcompress')) {
                        $content = gzcompress($response->getContent(), 3);
                    }
                    file_put_contents($html_path, $content);
                }
            }
        }

        return $response;
    }
}
