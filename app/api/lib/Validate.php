<?php

/**
 *
 * 解析
 *
 * @package   NICMS
 * @category  app\api\logic
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\api\lib;

use app\api\lib\BaseLogic;

class Validate extends BaseLogic
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
        $signType = $this->request->param('sign_type', 'md5');
        if (!function_exists($signType)) {
            $this->abort('错误请求', 22001);
        }

        // 校验签名合法性
        $sign = $this->request->param('sign');
        if (!$sign || !preg_match('/^[A-Za-z0-9]+$/u', $sign)) {
            $this->abort('错误请求', 22002);
        }

        // 请求时间
        $timestamp = $this->request->param('timestamp/d', $this->request->time(), 'abs');
        if (!$timestamp || $timestamp <= strtotime('-1 days')) {
            $this->abort('错误请求', 23001);
        }

        // 获得原始数据
        $params = $this->request->param('', '', 'trim');
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
        // $str .= $this->request->server('HTTP_USER_AGENT') . $this->request->server('HTTP_REFERER') . $this->request->ip();

        if (!hash_equals(call_user_func($signType, $str), $sign)) {
            $this->abort('错误请求', 22003);
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
                    $this->abort('错误请求', 26001);
                }
            }
        }
    }
}
