<?php

/**
 *
 * 控制层
 * IP信息API
 *
 * @package   NICMS
 * @category  app\api\controller\tools
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller\tools;

use think\Response;
use app\common\controller\BaseApi;
use app\common\library\tools\IpV4 as ToolsIpV4;

class IpV4 extends BaseApi
{

    public function index()
    {
        // 外部网站限制
        $referer = $this->request->server('HTTP_REFERER');
        if (!$referer || false === stripos($referer, $this->request->rootDomain())) {
            usleep(500000);
        }

        // 解决没有传IP参数,缓存造成的缓存错误
        if (!$ip = $this->request->param('ip', false)) {
            $url = $this->request->baseUrl(true) . '?';
            $url .= 'format=' . $this->request->param('format', '');
            $url .= '&ip=' . $this->request->ip();
            return Response::create($url, 'redirect', 302);
        }

        $ip = $this->request->param('ip', false) ?: $this->request->ip();
        if ($result = (new ToolsIpV4)->get($ip)) {
            $content_type = 'application/json';

            $format = $this->request->param('format', 'json');
            $format = 'json' === $format ? $format : 'html';
            if ('json' !== $format) {
                $result = 'const IP = ' . json_encode($result);
                $content_type = 'application/javascript';
            }

            return Response::create($result, $format)
                ->allowCache(true)
                ->cacheControl('max-age=28800,must-revalidate')
                ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
                ->expires(gmdate('D, d M Y H:i:s', time() + 28800) . ' GMT')
                ->contentType($content_type);
        }

        return miss(404);
    }
}
