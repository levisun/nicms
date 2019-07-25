<?php

/**
 *
 * 请求缓存
 *
 * @package   NICMS
 * @category  app\middleware
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Config;
use think\Request;
use think\Response;

/**
 * 请求缓存处理
 */
class CheckRequestCache
{

    /**
     * 设置当前地址的请求缓存
     * @access public
     * @param  Request $request
     * @param  Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next, Config $config)
    {
        if ($request->isGet() && $ms = $request->server('HTTP_IF_MODIFIED_SINCE')) {
            if (strtotime($ms) + 1440 > $request->server('REQUEST_TIME')) {
                return Response::create()->code(304);   // 读取缓存
            }
        }

        $response = $next($request);

        // 调试模式关闭浏览器缓存
        // API有定义缓存,请勿开启缓存
        if (true === $config->get('app.debug')) {
            $response->allowCache(false);
        }

        if (200 == $response->getCode() && $request->isGet() && $response->isAllowCache()) {
            $header = [
                'Cache-control' => 'max-age=1440,must-revalidate',
                'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
                'Expires'       => gmdate('D, d M Y H:i:s', time() + 1440) . ' GMT',
                'X-Powered-By'  => 'NICMS'
            ];
            $header = array_merge($header, $response->getHeader());

            $response->allowCache(true)->header($header);
        }

        return $response;
    }

    /**
     * 输出压缩
     * @access private
     * @param  Response $response
     * @return Response
     */
    private function gzip($response): Response
    {
        if ($this->request->isGet() && !headers_sent() && function_exists('gzencode')) {
            $content = $response->getContent();
            $content = gzencode($content, 2, FORCE_GZIP);
            $response->content($content);
            $response->header([
                'Content-Encoding' => 'gzip',
                'Content-Length'   => strlen($content)
            ]);
        }
        return $response;
    }
}
