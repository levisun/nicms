<?php

/**
 *
 * 控制层
 * 查询API
 *
 * @package   NICMS
 * @category  app\api\controller
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Session;

use app\common\library\Base64;
use app\common\library\Filter;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;


class Secret
{

    public function index()
    {
        $referer = parse_url(request()->server('HTTP_REFERER'), PHP_URL_HOST);
        if (!$referer || false === stripos($referer, request()->rootDomain())) {
            trace('MISS ' . request()->ip(), 'error');
            return miss(404, false);
        }

        $app_name = base64_decode(request()->param('app_name'));
        $app_name = Filter::htmlDecode(Filter::strict($app_name));

        $token = base64_decode(request()->param('token'));
        $token = Filter::htmlDecode(Filter::strict($token));

        $version = request()->param('version');
        $version = Filter::htmlDecode(Filter::strict($version));

        if (!!preg_match('/[^a-z]+/', $app_name) || !!preg_match('/[^a-zA-Z_\d\{\}\[\]":,]+/i', $token) || !preg_match('/[\d]+\.[\d]+\.[\d]+/', $version)) {
            trace('MISS ' . $app_name . $token . $version, 'error');
            return miss(404, false);
        }



        $authorization = (string) (new Builder)
            // 签发者
            ->issuedBy(request()->rootDomain())
            // 接收者
            ->permittedFor($referer)
            // 身份标识(SessionID)
            ->identifiedBy(Base64::encrypt(Session::getId()), false)
            // 签发时间
            ->issuedAt(request()->time())
            // 令牌使用时间
            ->canOnlyBeUsedAfter(request()->time() + 2880)
            // 签发过期时间
            ->expiresAt(request()->time() + 28800)
            // 客户端ID
            ->withClaim('uid', client_id())
            // 生成token
            ->getToken(new Sha256, new Key(Base64::asyncSecret()));



        $from_token = request()->buildToken('__token__', 'md5');
        $secret = app_secret($app_name);

        $script = 'const NICMS = {
            domain:"//"+window.location.host+"/",
            rootDomain:"//"+window.location.host.substr(window.location.host.indexOf(".")+1)+"/",
            url:"//"+window.location.host+window.location.pathname,
            api_uri:"' . config('app.api_host') . '",
            api_version:"' . request()->param('version') . '",
            app_name:"' . config('app.app_name') . '",
            app_id:"' . $secret['id'] . '",
            param:' . $token . '
        };
        let ip = document.createElement("script");
        ip.src = "' . config('app.api_host') . 'tools/ip.do?token=' . md5(request()->server('HTTP_REFERER')) . '";
        var script = document.getElementsByTagName("script")[0];
        script.parentNode.insertBefore(ip, script);
        document.cookie = "CSRF_TOKEN=' . $from_token . ';expires=0;path=/;SameSite=lax;domain="+window.location.host+";";
        window.sessionStorage.setItem("XSRF_AUTHORIZATION", "' . trim(base64_encode($authorization), '=') . '");
        window.sessionStorage.setItem("XSRF_TOKEN", "' . sha1($secret['secret'] . Base64::asyncSecret()) . '");';

        if ('admin' != $app_name) {
            $script .= 'let record = document.createElement("script");
            record.src = "' . config('app.api_host') . 'tools/record.do?url=' . urlencode(request()->server('HTTP_REFERER')) . '";
            var script = document.getElementsByTagName("script")[0];
            script.parentNode.insertBefore(record, script);';
        }

        $script = preg_replace('/\s+/', ' ', $script);

        return \think\Response::create($script)->header([
            'Content-Type'   => 'application/javascript',
            'Content-Length' => strlen($script),
        ]);
    }
}
