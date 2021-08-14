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

use think\facade\Request;
use think\facade\Session;

use app\common\library\Base64;
use app\common\library\Filter;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class AppConfig
{

    public function index()
    {
        Request::filter('\app\common\library\Filter::strict');
        $referer = parse_url(Request::server('HTTP_REFERER'), PHP_URL_HOST);
        if (!$referer || false === stripos($referer, Request::rootDomain())) {
            trace('MISS ' . Request::ip(), 'warning');
            return miss(404);
        }

        $app_name = Request::param('name', '', 'urldecode');
        $app_name = str_replace(' ', '+', $app_name);
        $app_name = base64_decode($app_name);
        if ($app_name && !preg_match('/^[a-z]+$/', $app_name)) {
            return miss(404);
        }

        $version = Request::param('version');
        if (!preg_match('/^[\d]+\.[\d]+\.[\d]+$/', $version)) {
            trace('MISS ' . $version, 'warning');
            return miss(404);
        }

        $param = Request::param('token', '', 'urldecode');
        $param = str_replace(' ', '+', $param);
        $param = base64_decode($param);
        if ($param) {
            $param = Filter::htmlDecode($param);
            if (!preg_match('/^[\w\d\{\}\[\]\\\":,\+=]+$/i', $param)) {
                return miss(404);
            }
        }
        $param = json_decode($param, true);
        $haystack = ['id', 'pass', 'attribute', 'status', 'model_id', 'limit', 'page', 'date_format', 'sort', 'key', 'category_id', 'type_id', 'book_id', 'book_type_id', 'lang', 'logic', 'action', 'method'];
        foreach ($param as $key => $value) {
            if (!in_array($key, $haystack)) {
                unset($param[$key]);
            }
        }
        $param = json_encode($param, JSON_UNESCAPED_UNICODE);



        $authorization = (string) (new Builder)
            // 签发者
            ->issuedBy(Request::rootDomain())
            // 接收者
            ->permittedFor($referer)
            // 身份标识(SessionID)
            ->identifiedBy(Base64::encrypt(Session::getId()), false)
            // 签发时间
            ->issuedAt(Request::time())
            // 令牌使用时间
            ->canOnlyBeUsedAfter(Request::time())
            // 签发过期时间
            ->expiresAt(Request::time() + 2880)
            // 客户端ID
            ->withClaim('uid', client_id())
            // 生成token
            ->getToken(new Sha256, new Key(Base64::asyncSecret()));



        $secret = app_secret($app_name);

        // 区分用户缓存
        $user_type = $user_id = $user_role_id = 'guest';
        if ('admin' === $app_name && Session::has('admin_auth_key')) {
            $user_type = 'admin_auth_key';
        } elseif (Session::has('user_auth_key')) {
            $user_type = 'user_auth_key';
        }
        if ('guest' !== $user_type) {
            $user_id = (int) Session::get($user_type);
            $user_role_id = (int) Session::get($user_type . '_role');
        }

        $script = 'const APP_CONFIG = {
            domain:"//"+window.location.host+"/",
            rootDomain:"//"+window.location.host.substr(window.location.host.indexOf(".")+1)+"/",
            url:"//"+window.location.host+window.location.pathname,
            api_uri:"' . config('app.api_host') . '",
            api_version:"' . $version . '",
            app_name:"' . config('app.app_name') . '",
            app_id:"' . $secret['id'] . '",
            param:' . $param . '
        };
        window.sessionStorage.setItem("XSRF_AUTHORIZATION", "' . trim(base64_encode($authorization), '=') . '");
        window.sessionStorage.setItem("XSRF_SECRET", "' . sha1($secret['secret'] . Base64::asyncSecret()) . '");
        window.sessionStorage.setItem("FROM_TOKEN", "' . Request::buildToken('__token__', 'md5') . '");
        window.sessionStorage.setItem("API_TOKEN", "' . sha1($user_id . $user_role_id . $user_type . date('Ymd')) . '");
        let ip = document.createElement("script");
        ip.src = "' . config('app.api_host') . 'tools/ip.do?token=' . md5(Request::server('HTTP_REFERER')) . '";
        var script = document.getElementsByTagName("script")[0];
        script.parentNode.insertBefore(ip, script);';

        if (!env('app_debug')) {
            $script = preg_replace('/\s+/', ' ', $script);
        }

        return \think\Response::create($script)->header([
            'Content-Type' => 'application/javascript',
            // 'Content-Length' => strlen($script),
        ]);
    }
}
