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

declare(strict_types=1);

namespace app\library;

use app\library\Base64;

class JWT
{
    /**
     * The token headers
     * @var array
     */
    private $headers = ['typ' => 'JWT', 'alg' => 'sha256'];

    private $playload = [];

    public function __construct()
    {
        $this->setheaders('alg', 'sha256')
            ->issuedBy(app('request')->rootDomain())
            ->issuedAt((int) app('request')->time())
            ->expiresAt((int) app('request')->time() + 1440)
            ->identifiedBy(app('session')->getId(false))
            ->audience($this->playload['iat'] . app('request')->baseUrl());
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
        $this->playload['exp'] = $_expiration + 300;
        return $this;
    }

    /**
     * 身份标识
     * @access public
     * @param  string $_id
     * @return JWT
     */
    public function identifiedBy(string $_id = '')
    {
        $this->playload['jti'] = Base64::encrypt($_id);
        return $this;
    }

    /**
     * 接收者
     * @access public
     * @param  string $_audience
     * @return mixed
     */
    public function audience(string $_audience, $_aud = false)
    {
        $_audience .= app('request')->ip() . app('request')->rootDomain() . app('request')->server('HTTP_USER_AGENT');
        $this->playload['aud'] = hash_hmac('sha256', Base64::encrypt($_audience), date('Ymd'));
        return (true === $_aud) ? $this->playload['aud'] : $this;
    }

    /**
     * 获得TOKEN
     * @access public
     * @param
     * @return string
     */
    public function getToken(): string
    {
        $headers   = trim(base64_encode(json_encode($this->headers)), '=');
        $playload  = trim(base64_encode(json_encode($this->playload)), '=');
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
        $_authorization = str_replace('&#43;', '+', $_authorization);
        if ($_authorization && preg_match('/^Bearer [A-Za-z0-9\+\/]+\.[A-Za-z0-9\+\/]+\.[A-Za-z0-9]+$/u', $_authorization)) {
            $_authorization = str_replace('Bearer ', '', $_authorization);
            list($headers, $playload, $signature) = explode('.', $_authorization, 3);

            // 签名验
            $alg = (json_decode(base64_decode($headers)))->alg;
            if (hash_equals($signature, $this->getSignature($alg, $headers . '.' . $playload))) {
                $playload = json_decode(base64_decode($playload), true);
                $playload['jti'] = $playload['jti'] ? Base64::decrypt($playload['jti']) : null;

                // 接受者验证
                $audience = $this->audience($playload['iat'] . parse_url(app('request')->server('HTTP_REFERER'), PHP_URL_PATH), true);
                if (!hash_equals($audience, $playload['aud'])) {
                    return false;
                }

                // 签发者验证
                elseif (!hash_equals(app('request')->rootDomain(), $playload['iss'])) {
                    return false;
                }

                // 签发时间验证
                elseif ($playload['iat'] >= app('request')->time()) {
                    return false;
                }

                // 有效期验证
                elseif ($playload['exp'] <= app('request')->time()) {
                    return false;
                }

                // 身份标识验证
                elseif (!$playload['jti'] || !preg_match('/^[A-Za-z0-9]{32,40}$/u', $playload['jti'])) {
                    return false;
                }

                // 验证通过
                else {
                    return $playload;
                }
            }
        }

        return false;
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
        return hash_hmac($_alg, $_data, app('config')->get('app.secretkey'));
    }
}
