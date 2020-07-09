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
use think\Config;
use think\Request;
use think\Response;

class RequestCache
{
    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        // 请求缓存规则 true为自动规则
        'request_cache_key'    => true,
        // 请求缓存有效期
        'request_cache_expire' => null,
        // 全局请求缓存排除规则
        'request_cache_except' => [],
        // 请求缓存的Tag
        'request_cache_tag'    => '',
    ];

    public function __construct(Config $config)
    {
        $this->config = array_merge($this->config, $config->get('route'));
    }

    /**
     * 设置当前地址的请求缓存
     * @access public
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        // 304缓存
        if ($request->isGet() && $ms = $request->server('HTTP_IF_MODIFIED_SINCE')) {
            if (strtotime($ms) > $request->server('REQUEST_TIME')) {
                return Response::create()->code(304);
            }
        }

        $response = $next($request);

        if ($this->config['request_cache_key']) {
            if (200 == $response->getCode() && $response->isAllowCache()) {
                if ($expire = $response->getHeader('Cache-Control')) {
                    $expire = (int) str_replace(['max-age=', ',must-revalidate'], '', $expire);
                } else {
                    $expire = $this->config['request_cache_expire'];
                }

                $timestamp = time();
                $header                  = $response->getHeader();
                $header['Cache-Control'] = 'max-age=' . $expire . ',must-revalidate';
                $header['Last-Modified'] = gmdate('D, d M Y H:i:s', $timestamp + $expire) . ' GMT';
                $header['Expires']       = gmdate('D, d M Y H:i:s', $timestamp + $expire) . ' GMT';
                $response->header($header);
            }
        }


        return $response;
    }
}
