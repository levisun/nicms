<?php

/**
 *
 * 解析
 *
 * @package   NICMS
 * @category  app\common\library\api
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\library\api;

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Request;

use app\common\library\Rbac;

class Validate
{
    /**
     * 不用验证
     * @var array
     */
    protected $notAuth = [
        'not_auth_action' => [
            'auth',
            'profile',
            'notice'
        ]
    ];

    /**
     * 验证签名类型与签名合法性
     * @access public
     * @return void
     */
    public function sign(string $_app_secret): void
    {
        // 校验签名类型
        $sign_type = Request::param('sign_type', 'md5');
        if (!function_exists($sign_type)) {
            $this->abort('The signature type is wrong.', 22001);
        }

        // 校验签名合法性
        $sign = Request::param('sign');
        if (!$sign || !!!preg_match('/^[A-Za-z0-9]+$/u', $sign)) {
            $this->abort('The signature is wrong.', 22002);
        }

        // 获得原始数据
        $params = Request::param('', '', 'trim');
        $params = array_merge($params, $_FILES);
        ksort($params);

        $str = '';
        foreach ($params as $key => $value) {
            if ('sign' == $key) {
                continue;
            } elseif (is_array($value)) {
                continue;
            } elseif (is_numeric($value) || $value) {
                $str .= $key . '=' . $value . '&';
            }
        }
        $str  = rtrim($str, '&');

        // $key = date('Ymd') . Request::ip() . Request::rootDomain() . Request::server('HTTP_USER_AGENT');
        // $str .= sha1($_app_secret . $key);
        $str .= $_app_secret;

        if (!hash_equals(call_user_func($sign_type, $str), $sign)) {
            $this->abort('The signature is wrong.', 22003);
        }
    }

    /**
     * 验证请求时间
     * @access public
     * @return void
     */
    public function timestamp(): bool
    {
        $timestamp = Request::param('timestamp/d', Request::time(), 'abs');
        if ($timestamp <= strtotime('-1 minutes') && $timestamp >= strtotime('+30 seconds')) {
            $this->abort('The request timed out.', 23001);
        }

        return true;
    }

    /**
     * 验证表单令牌
     * @access public
     * @return void
     */
    public function fromToken(): bool
    {
        if (Request::isPost() && false === Request::checkToken()) {
            $this->abort('The request form token is wrong.', 24002);
        }

        return true;
    }

    /**
     * 验证请求来源
     * @access public
     * @return bool
     */
    public function referer(): bool
    {
        $referer = Request::server('HTTP_REFERER');
        if (!$referer || false === stripos($referer, Request::rootDomain())) {
            $this->abort('The source request was incorrect.', 24001);
        }

        return true;
    }

    /**
     * 验证权限
     * @access public
     * @return void
     */
    public function RBAC(string $_app_name, array $_app_method, int $_uid): void
    {
        // 需要鉴权应用
        if (in_array($_app_name, ['admin', 'user'])) {
            // 不需要鉴权方法(登录 登出 找回密码)
            if (!in_array($_app_method['method'], ['login', 'logout', 'forget'])) {
                // 验证权限
                $result = (new Rbac)->authenticate(
                    $_uid,
                    $_app_name,
                    $_app_method['logic'],
                    $_app_method['action'],
                    $_app_method['method'],
                    $this->notAuth
                );
                if (false === $result) {
                    $this->abort('No action permissions.', 26001);
                }
            }
        }
    }

    private function abort(string $_msg, int $_code)
    {
        $result = [
            'code'    => $_code,
            'message' => $_msg,
        ];
        $response = Response::create($result, 'json')->allowCache(false);

        ob_start('ob_gzhandler');

        throw new HttpResponseException($response);
    }
}
