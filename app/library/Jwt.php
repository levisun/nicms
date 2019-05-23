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

class JWT
{
    /**
     * The token headers
     *
     * @var array
     */
    private $headers = ['typ' => 'JWT', 'alg' => 'none'];

    private $identityType = 'session';

    private $playload = [];

    public function __construct()
    {
        $this->setheaders('alg', 'sha256')
            ->issuedBy(Request::rootDomain())
            ->issuedAt(Request::time())
            ->expiresAt(Request::time() + 7200)
            ->identifiedBy(Session::getId(false), 'session')
            ->audience(Request::ip() . Request::server('HTTP_USER_AGENT'));
    }

    /**
     * 设置头
     * @access public
     * @param  string $_name
     * @param  string $_value
     * @return JWT
     */
    public function setheaders(string $_name, string $_value)
    {
        $this->headers[$_name] = $_value;
        return $this;
    }

    /**
     * 签发者
     * @access public
     * @param  string $_issuer
     * @return JWT
     */
    public function issuedBy(string $_issuer)
    {
        $this->playload['iss'] = $_issuer;
        return $this;
    }

    /**
     * 签发时间
     * @access public
     * @param  string $_issuedAt
     * @return JWT
     */
    public function issuedAt(int $_issuedAt)
    {
        $this->playload['iat'] = $_issuedAt;
        return $this;
    }

    /**
     * 过期时间
     * @access public
     * @param  string $_expiration
     * @return JWT
     */
    public function expiresAt(int $_expiration)
    {
        $this->playload['exp'] = $_expiration;
        return $this;
    }

    /**
     * 身份标识
     * @access public
     * @param  string $_id
     * @return JWT
     */
    public function identifiedBy(string $_id = '', string $_type = 'session')
    {
        $this->identityType = $_type;
        $this->playload['jti'] = Base64::encrypt($_id);
        return $this;
    }

    /**
     * 接收者
     * @access public
     * @param  string $_audience
     * @return JWT
     */
    public function audience(string $_audience)
    {
        $this->playload['aud'] = md5($_audience);
        return $this;
    }

    /**
     * 获得TOKEN
     * @access public
     * @param
     * @return string
     */
    public function getToken(): string
    {
        $headers    = base64_encode(json_encode($this->headers));
        $playload  = base64_encode(json_encode($this->playload));
        $signature = $this->getSignature($this->headers['alg'], $headers . '.' . $playload);
        return 'Bearer ' . $headers . '.' . $playload . '.' . $signature;
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
            $_authorization = str_replace('Bearer ', '', $_authorization);
            list($headers, $playload, $signature) = explode('.', $_authorization, 3);

            // 签名验
            $alg = (json_decode(base64_decode($headers)))->alg;
            if (!hash_equals($signature, $this->getSignature($alg, $headers . '.' . $playload))) {
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
            } else {
                $result = true;
            }

            if (false === $result && $playload['jti']) {
                if ('session' === $this->identityType) {
                    Session::setId($playload['jti']);
                    Session::destroy();
                }
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
