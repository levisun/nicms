<?php
/**
 *
 * 加密类
 *
 * @package   NICMS
 * @category  app\library
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
declare (strict_types = 1);

namespace app\library;

use think\facade\Config;
use think\facade\Request;
use think\facade\Session;
use app\library\Base64;

class Jwt
{
    private $header = [
        'typ' => 'JWT',
        'alg' => 'sha256'
    ];

    public function create(array $_playload = [], string $_alg = 'sha256'): string
    {
        $this->header['alg'] = $_alg;

        $header = base64_encode(json_encode($this->header));

        $_playload = base64_encode(json_encode(array_merge($_playload, [
            // 签发者
            'iss' => Request::rootDomain(),
            // 签发时间
            'iat' => Request::time(),
            // 过期时间
            'exp' => Request::time() + 7200,
            // 接收者
            'aud' => md5(Request::ip() . Request::server('HTTP_USER_AGENT')),
            // 唯一身份标识
            'jti' => Base64::encrypt(Session::getId(false)),
        ])));

        $signature = $this->getSignature($this->header['alg'], $header . '.' . $_playload);

        return 'Bearer ' . $header . '.' . $_playload . '.' . $signature;
    }

    /**
     * 验证签名
     * @access public
     * @param  string  $_authorization
     * @return bool|array
     */
    public function verify(string $_authorization)
    {
        if ($_authorization && preg_match('/^Bearer [A-Za-z0-9\+\/\=]+\.[A-Za-z0-9\+\/\=]+\.[A-Za-z0-9]+$/u', $_authorization)) {
            $_authorization = str_replace('Bearer ', '' , $_authorization);
            list($header, $playload, $signature) = explode('.', $_authorization, 3);

            // 签名验
            $alg = (json_decode(base64_decode($header)))->alg;
            if (!hash_equals($signature, $this->getSignature($alg, $header . '.' . $playload))) {
                return false;
            }

            $playload = json_decode(base64_decode($playload), true);

            // 身份标识验证
            $playload['jti'] = Base64::decrypt($playload['jti']);
            if (!$playload['jti'] || !preg_match('/^[A-Za-z0-9]{32,40}$/u', $playload['jti'])) {
                $result = null;
            }

            // 签发者验证
            elseif (!hash_equals(Request::rootDomain(), $playload['iss'])) {
                $result = false;
            }

            // 接受者验证
            elseif (!hash_equals(md5(Request::ip() . Request::server('HTTP_USER_AGENT')), $playload['aud'])) {
                $result = false;
            }

            // 签发时间验证
            elseif ($playload['iat'] > Request::time()) {
                $result = false;
            }

            // 有效期验证
            elseif ($playload['exp'] < Request::time()) {
                $result = false;
            }

            else {
                $result = true;
            }

            if (false === $result && $playload['jti']) {
                Session::setId($playload['jti']);
                Session::destroy();
            }

            return true === $result ? $playload : false;
        }
    }

    /**
     * 生成签名
     * @access private
     * @param  string  $_alg
     * @param  string  $_data
     * @return string
     */
    private function getSignature(string $_alg, string $_data): string
    {
        return hash_hmac($_alg, $_data, Config::get('app.secretkey'));
    }
}
