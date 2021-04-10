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
            trace('MISS ' . request()->ip(), 'warning');
            return miss(404, false);
        }

        $app_name = base64_decode(request()->param('app_name'));
        $app_name = Filter::htmlDecode(Filter::strict($app_name));

        $param = base64_decode(request()->param('token'));
        $param = Filter::htmlDecode(Filter::strict($param));

        $version = request()->param('version');
        $version = Filter::htmlDecode(Filter::strict($version));

        if (!!preg_match('/[^a-z]+/', $app_name) || !!preg_match('/[^a-zA-Z_\d\{\}\[\]":,]+/i', $param) || !preg_match('/[\d]+\.[\d]+\.[\d]+/', $version)) {
            trace('MISS ' . $app_name . $param . $version, 'warning');
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
            ->canOnlyBeUsedAfter(request()->time())
            // 签发过期时间
            ->expiresAt(request()->time() + 2880)
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
            param:' . $param . '
        };
        let ip = document.createElement("script");
        ip.src = "' . config('app.api_host') . 'tools/ip.do?token=' . md5(request()->server('HTTP_REFERER')) . '";
        var script = document.getElementsByTagName("script")[0];
        script.parentNode.insertBefore(ip, script);
        window.sessionStorage.setItem("XSRF_AUTHORIZATION", "' . trim(base64_encode($authorization), '=') . '");
        window.sessionStorage.setItem("XSRF_SECRET", "' . sha1($secret['secret'] . Base64::asyncSecret()) . '");
        window.sessionStorage.setItem("FROM_TOKEN", "' . $from_token . '");';

        $user_type = 'guest';
        $user_id = $user_role_id = 0;
        if ('admin' === $app_name && Session::has('admin_auth_key')) {
            $user_type = 'admin_auth_key';
            $user_id = (int) Session::get($user_type);
            $user_role_id = (int) Session::get($user_type . '_role');
        } elseif (Session::has('user_auth_key')) {
            $user_type = 'user_auth_key';
            $user_id = (int) Session::get($user_type);
            $user_role_id = (int) Session::get($user_type . '_role');
        }

        $token = sha1(Base64::encrypt($user_id . $user_role_id . $user_type, date('Ymd')));
        $script .= 'window.sessionStorage.setItem("USER_TOKEN", "' . $token . '");';

        /* if ('admin' !== $app_name) {
            $script .= 'let record = document.createElement("script");
            record.src = "' . config('app.api_host') . 'tools/record.do?url=' . urlencode(request()->server('HTTP_REFERER')) . '";
            var script = document.getElementsByTagName("script")[0];
            script.parentNode.insertBefore(record, script);';
        } */


        $script = preg_replace('/\s+/', ' ', $script);

        return \think\Response::create($script)->header([
            'Content-Type'   => 'application/javascript',
            'Content-Length' => strlen($script),
        ]);
    }
}
