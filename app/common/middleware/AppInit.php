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

use think\facade\Config;
use think\facade\Cookie;
use think\facade\Session;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;

use app\common\library\Base64;
use app\common\model\ApiApp as ModelApiApp;

class AppInit
{
    private $request = null;

    public function handle(Request $request, Closure $next)
    {
        $this->request = $request;

        # TODO

        $response = $next($request);

        // 纪念日全网灰色
        // -webkit-filter:grayscale(100%);

        $this->app_secret();
        $this->csrf_token();
        $this->authorization();

        return $response;
    }

    /**
     * API POST请求表单签名
     * @access private
     * @return void
     */
    private function csrf_token()
    {
        $app_token = $this->request->buildToken('__token__', 'md5');
        Cookie::set('CSRF_TOKEN', $app_token, ['httponly' => false]);
        return $app_token;
    }

    /**
     * API请求sign签名加密密钥
     * @access private
     * @return void
     */
    private function app_secret()
    {
        if ($app_name = Config::get('app.domain_bind.' . $this->request->subDomain())) {
            $api_app = ModelApiApp::field('id, secret')
                ->where([
                    ['name', '=', $app_name],
                    ['status', '=', 1]
                ])
                ->cache('app secret' . $app_name)
                ->find();
            if ($api_app && $api_app = $api_app->toArray()) {
                $key = date('Ymd') . $this->request->ip() . $this->request->rootDomain() . $this->request->server('HTTP_USER_AGENT');
                $app_secret = sha1($api_app['secret'] . $key);
                Cookie::set('XSRF_TOKEN', $app_secret, ['httponly' => false]);
                return $app_secret;
            }
        }
    }

    /**
     * API请求认证标识
     * @access private
     * @return void
     */
    private function authorization()
    {
        // 密钥
        $key = date('Ymd') . $this->request->ip() . $this->request->rootDomain() . $this->request->server('HTTP_USER_AGENT');
        $key = sha1(Base64::encrypt($key));

        $authorization = (string) (new Builder)
            // 签发者
            ->issuedBy($this->request->rootDomain())
            // 接收者
            ->permittedFor($this->request->host())
            // 身份标识(SessionID)
            ->identifiedBy(Base64::encrypt(Session::getId()), false)
            // 签发时间
            ->issuedAt($this->request->time())
            // 令牌使用时间
            ->canOnlyBeUsedAfter($this->request->time() + 2880)
            // 签发过期时间
            ->expiresAt($this->request->time() + 28800)
            // 客户端ID
            ->withClaim('uid', client_id())
            // 生成token
            ->getToken(new Sha256, new Key($key));

        Cookie::set('XSRF_AUTHORIZATION', $authorization, ['httponly' => false]);

        return $authorization;
    }
}
