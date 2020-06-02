<?php

/**
 *
 * 验证
 *
 * @package   NICMS
 * @category  app\common\async
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\logic;

use think\Response;
use think\exception\HttpResponseException;
use think\facade\Request;

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
        $signType = Request::param('sign_type', 'md5');
        if (!function_exists($signType)) {
            $response = Response::create(['code' => 22001, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }

        // 校验签名合法性
        $sign = Request::param('sign');
        if (!$sign || !preg_match('/^[A-Za-z0-9]+$/u', $sign)) {
            $response = Response::create(['code' => 22002, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }

        // 请求时间
        $timestamp = Request::param('timestamp/d', Request::time(), 'abs');
        if (!$timestamp || $timestamp <= strtotime('-1 days')) {
            $response = Response::create(['code' => 23001, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
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
        $str = rtrim($str, '&');
        $str .= $_app_secret;
        // $str .= Request::server('HTTP_USER_AGENT') . Request::server('HTTP_REFERER') . Request::ip();

        if (!hash_equals(call_user_func($signType, $str), $sign)) {
            $response = Response::create(['code' => 22003, 'message' => '错误请求'], 'json');
            throw new HttpResponseException($response);
        }
    }

    /**
     * 验证权限
     * @access public
     * @return void
     */
    public function RBAC(string $_app_name, array $_app_method, int $_uid): void
    {
        // 需要鉴权应用
        if (in_array($_app_name, ['admin', 'my'])) {
            // 不需要鉴权方法(登录 登出 找回密码)
            if (!in_array($_app_method['method'], ['login', 'logout', 'forget'])) {
                // 验证权限
                $result = Rbac::authenticate(
                    $_uid,
                    $_app_name,
                    $_app_method['logic'],
                    $_app_method['action'],
                    $_app_method['method'],
                    $this->notAuth
                );
                if (false === $result) {
                    $response = Response::create(['code' => 26001, 'message' => '错误请求'], 'json');
                    throw new HttpResponseException($response);
                }
            }
        }
    }
}
