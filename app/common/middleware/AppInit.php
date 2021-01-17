<?php

/**
 *
 * 应用维护
 * 清除应用垃圾
 * 数据库维护
 *
 * @package   NICMS
 * @category  app\common\event
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;

use think\facade\Cookie;
use think\facade\Session;
use app\common\library\Base64;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class AppInit
{

    public function handle(Request $request, Closure $next)
    {
        if (!$request->rootDomain()) {
            return miss(404, false);
        }

        // IP进入显示空页面
        if ($request->isValidIP($request->host(true), 'ipv4') || $request->isValidIP($request->host(true), 'ipv6')) {
            return miss(404, false);
        }

        $from_token = $request->buildToken('__token__', 'md5');
        Cookie::set('CSRF_TOKEN', $from_token, ['domain' => $request->host(), 'httponly' => false]);

        $response = $next($request);

        $authorization = (string) (new Builder)
            // 签发者
            ->issuedBy($request->rootDomain())
            // 接收者
            ->permittedFor($request->host())
            // 身份标识(SessionID)
            ->identifiedBy(Base64::encrypt(Session::getId()), false)
            // 签发时间
            ->issuedAt($request->time())
            // 令牌使用时间
            ->canOnlyBeUsedAfter($request->time() + 2880)
            // 签发过期时间
            ->expiresAt($request->time() + 28800)
            // 客户端ID
            ->withClaim('uid', client_id())
            // 生成token
            ->getToken(new Sha256, new Key(Base64::asyncSecret()));

        $secret = app_secret();
        $secret = sha1($secret['secret'] . Base64::asyncSecret());


        $content = $response->getContent();

        $app_init = '<script type="text/javascript">window.localStorage.setItem("XSRF_AUTHORIZATION", "' . trim(base64_encode($authorization), '=') . '");window.localStorage.setItem("XSRF_TOKEN", "' . $secret . '");</script>';

        $content = false !== strripos($content, '</head>')
            ? str_replace('</head>', $app_init . '</head>', $content)
            : $content . $app_init;

        $response->content($content);

        return $response;
    }
}
