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

use app\common\controller\BaseApi;

use think\facade\Cookie;
use app\common\library\Base64;
use app\common\library\Filter;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;


class Query extends BaseApi
{

    public function index()
    {
        if ($result = $this->run()) {
            // 请勿开启缓存
            // 如要开启缓存请在方法中单独定义
            return $this->response(
                $result['msg'],
                $result['data'],
                $result['code']
            );
        }

        return miss(404, false);
    }

    public function secret(string $app_name, string $token)
    {
        $app_name = base64_decode($this->request->param('app_name'));
        $app_name = Filter::htmlDecode(Filter::strict($app_name));

        $token = base64_decode($this->request->param('token'));
        $token = Filter::htmlDecode(Filter::strict($token));

        $version = $this->request->param('version');
        $version = Filter::htmlDecode(Filter::strict($version));

        if (!!preg_match('/[^a-z]+/', $app_name) || !!preg_match('/[^a-zA-Z\d\{\}\[\]":,]+/i', $token) || preg_match('/[^\d\.]+/', $version)) {
            return miss(404, false);
        }

        $host = parse_url($this->request->server('HTTP_REFERER'), PHP_URL_HOST);
        $authorization = (string) (new Builder)
            // 签发者
            ->issuedBy($this->request->rootDomain())
            // 接收者
            ->permittedFor($host)
            // 身份标识(SessionID)
            ->identifiedBy(Base64::encrypt($this->session->getId()), false)
            // 签发时间
            ->issuedAt($this->request->time())
            // 令牌使用时间
            ->canOnlyBeUsedAfter($this->request->time() + 2880)
            // 签发过期时间
            ->expiresAt($this->request->time() + 28800)
            // 客户端ID
            ->withClaim('uid', client_id())
            // 生成token
            ->getToken(new Sha256, new Key(Base64::asyncSecret()));



        $from_token = $this->request->buildToken('__token__', 'md5');
        $secret = app_secret($app_name);

        $script = 'const NICMS = {
            domain:"//"+window.location.host+"/",
            rootDomain:"//"+window.location.host.substr(window.location.host.indexOf(".")+1)+"/",
            url:"//"+window.location.host+window.location.pathname,
            api_uri:"' . config('app.api_host') . '",
            api_version:"' . $this->request->param('version') . '",
            app_name:"' . config('app.app_name') . '",
            app_id:"' . $secret['id'] . '",
            param:' . $token . '
        };
        let ip = document.createElement("script");
        ip.src = "' . config('app.api_host') . 'tools/ip.do?token=' . md5($this->request->server('HTTP_REFERER')) . '";
        var script = document.getElementsByTagName("script")[0];
        script.parentNode.insertBefore(ip, script);
        if("admin" != "' . $app_name . '"){
        let record = document.createElement("script");
        record.src = "' . config('app.api_host') . 'tools/record.do?url=' . urlencode($this->request->server('HTTP_REFERER')) . '";
        var script = document.getElementsByTagName("script")[0];
        script.parentNode.insertBefore(record, script);
        }
        document.cookie = "CSRF_TOKEN=' . $from_token . ';expires=0;path=/;SameSite=lax;domain="+window.location.host+";";
        window.sessionStorage.setItem("XSRF_AUTHORIZATION", "' . trim(base64_encode($authorization), '=') . '");
        window.sessionStorage.setItem("XSRF_TOKEN", "' . sha1($secret['secret'] . Base64::asyncSecret()) . '");';

        $script = preg_replace('/\s+/', ' ', $script);

        return \think\Response::create($script)->header([
            'Content-Type'   => 'application/javascript',
            'Content-Length' => strlen($script),
        ]);
    }
}
