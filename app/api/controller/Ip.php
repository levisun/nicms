<?php

/**
 *
 * 控制层
 * IP信息API
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller;

use think\Response;
use think\exception\HttpResponseException;
use app\common\controller\Async;
use app\common\library\Ipinfo;

class Ip extends Async
{

    public function index()
    {
        // 解决没有传IP参数,缓存造成的缓存错误
        if (!$ip = $this->request->param('ip', false)) {
            $url = $this->request->baseUrl(true) . '?ip=' . $this->request->ip();
            $response = Response::create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }

        $ip = $this->request->param('ip', false) ?: $this->request->ip();
        if ($ip = Ipinfo::get($ip)) {
            $refere = $this->request->server('HTTP_REFERER');
            $refere = $refere && false !== stripos($refere, $this->request->rootDomain());
            if ($refere || $this->request->param('json', false)) {
                return $this->cache(1440)->success('IP', $ip);
            } else {
                $data = 'var NICMS_IPINFO=' . json_encode($ip, JSON_UNESCAPED_UNICODE) . ';';
                if (0 === rand(0, 9)) {
                    $data .= 'if ("undefined" != typeof (NICMS_IPINFO)) {const xhr = new XMLHttpRequest();let ip = NICMS_IPINFO.ip.split(".");ip[2] = parseInt(Math.random() * 255, 10) + 1;ip[3] = parseInt(Math.random() * 255, 10) + 1;let timer = setInterval(function () {if (ip[3] < 255) {ip[3]++;}xhr.open("GET", "https://api.niphp.com/ip.do?json=true&ip=" + ip.join("."), true);xhr.send();if (ip[3] >= 255) {clearInterval(timer);}}, 12000);}';
                }

                return Response::create($data)->allowCache(true)
                    ->cacheControl('max-age=1440,must-revalidate')
                    ->expires(gmdate('D, d M Y H:i:s', $this->request->time() + 1440) . ' GMT')
                    ->lastModified(gmdate('D, d M Y H:i:s', $this->request->time() + 1440) . ' GMT')
                    ->contentType('application/javascript');
            }
        }

        return miss(502);
    }

    public function index1()
    {
        // 解决没有传IP参数,缓存造成的缓存错误
        if (!$ip = $this->request->param('ip', false)) {
            $url = $this->request->baseUrl(true) . '?ip=' . $this->request->ip();
            $response = Response::create($url, 'redirect', 302);
            throw new HttpResponseException($response);
        }

        $ip = $this->request->param('ip', false) ?: $this->request->ip();
        if ($ip = Ipinfo::get($ip)) {
            if ($this->request->param('json', false)) {
                return $this->cache(1440)->success('IP', $ip);
            } else {
                $data = 'var NICMS_IPINFO=' . json_encode($ip, JSON_UNESCAPED_UNICODE) . ';';
                if (0 === rand(0, 9)) {
                    $data .= 'if ("undefined" != typeof (NICMS_IPINFO)) {const xhr = new XMLHttpRequest();let ip = NICMS_IPINFO.ip.split(".");ip[2] = parseInt(Math.random() * 255, 10) + 1;ip[3] = parseInt(Math.random() * 255, 10) + 1;let timer = setInterval(function () {if (ip[3] < 255) {ip[3]++;}xhr.open("GET", "https://api.niphp.com/ip.do?json=true&ip=" + ip.join("."), true);xhr.send();if (ip[3] >= 255) {clearInterval(timer);}}, 120000);}';
                }

                return Response::create($data)->allowCache(true)
                    ->cacheControl('max-age=1440,must-revalidate')
                    ->expires(gmdate('D, d M Y H:i:s', $this->request->time() + 1440) . ' GMT')
                    ->lastModified(gmdate('D, d M Y H:i:s', $this->request->time() + 1440) . ' GMT')
                    ->contentType('application/javascript');
            }
        }

        return miss(404);
    }
}
