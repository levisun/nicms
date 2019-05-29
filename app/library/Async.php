<?php
/**
 *
 * 异步请求实现
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

use think\Container;
use think\Response;
use think\exception\HttpResponseException;
use think\facade\Config;
use think\facade\Lang;
use think\facade\Log;
use think\facade\Request;
use think\facade\Session;
use app\library\Jwt;
use app\model\ApiApp as ModelApiApp;

abstract class Async
{

    /**
     * HEADER 指定接收类型
     * 包含[域名 版本 返回类型]
     * application/vnd.nicms.v1.0.1+json
     * @var string
     */
    protected $accept;

    /**
     * 开启版本控制
     * @var bool
     */
    protected $openVersion = false;

    /**
     * 版本号
     * 解析[accept]获得
     * @var array
     */
    protected $version = [
        'major' => '1',
        'minor' => '0'
    ];

    /**
     * 返回数据类型
     * 解析[accept]获得
     * @var string
     */
    protected $format = 'json';

    /**
     * HEADER 授权信息
     * 包含[token sessid]
     * f0c4b4105d740747d44ac6dcd78624f906202706.
     * @var string
     */
    protected $authorization;

    /**
     * 请求令牌
     * 解析[authentication]获得
     * @var string
     */
    protected $token;

    /**
     * session_id
     * 解析[authentication]获得
     * @var string
     */
    protected $sid;

    /**
     * 签名类型
     * @var string
     */
    protected $signType;

    /**
     * 签名
     * @var string
     */
    protected $sign;

    /**
     * 模块名
     * @var string
     */
    protected $module = '';

    /**
     * 调试信息
     * @var array
     */
    protected $debugLog = [];

    /**
     * 调试开关
     * @var bool
     */
    protected $debug = false;

    /**
     * 浏览器数据缓存开关
     * @var bool
     */
    protected $cache = false;

    /**
     * 浏览器数据缓存时间
     * @var int
     */
    protected $expire = 1440;


    /**
     * APPID
     * @var int
     */
    protected $appid;

    /**
     * APP密钥
     * @var int
     */
    protected $appsecret;

    /**
     * 请求时间戳
     * @var int
     */
    protected $timestamp;

    /**
     * API方法
     * @var int
     */
    protected $method;


    /**
     * 构造方法
     * @access public
     * @param
     * @return void
     */

    /**
     * 运行
     * @access protected
     * @param
     * @return array
     */
    protected function run(): array
    {
        $exec = $this->analysisHeader()
            ->checkAppId()
            ->checkSign()
            ->checkTimestamp()
            ->analysisMethod();

        // 执行类方法
        $result = call_user_func_array([(new $exec['class']), $exec['action']], []);

        if (!is_array($result) && empty($result['msg'])) {
            $this->error('缺少参数', 40001);
        }

        // 调试与缓存设置
        // 调试模式 返回数据没有指定默认关闭
        if (isset($result['debug'])) {
            $this->debug($result['debug']);
        }

        // 缓存
        if (isset($result['cache'])) {
            $this->cache($result['cache']);
        }

        // 缓存时间
        if (isset($result['expire'])) {
            $this->expire($result['expire']);
        }

        $result['data'] = isset($result['data']) ? $result['data'] : [];
        $result['code'] = isset($result['code']) ? $result['code'] : 10000;

        return $result;
    }

    /**
     * 设置缓存时间
     * @access protected
     * @param
     * @return void
     */
    protected function expire(int $_expire = 0)
    {
        $this->expire = $_expire > 0 ? $_expire : (int)Config::get('cache.expire');
        return $this;
    }

    /**
     * 开启调试
     * @access protected
     * @param
     * @return void
     */
    protected function debug(bool $_debug)
    {
        $this->debug = $_debug;
        return $this;
    }

    /**
     * 设置缓存
     * @access protected
     * @param
     * @return void
     */
    protected function cache(bool $_cache = false)
    {
        $this->cache = (true === $this->debug) ? false : $_cache;
        return $this;
    }

    /**
     * 初始化
     * @access protected
     * @param
     * @return $this
     */
    protected function analysisMethod()
    {
        // 校验API方法
        $this->method = Request::param('method');
        if ($this->method && preg_match('/^[a-z]+\.[a-z]+\.[a-z]+$/u', $this->method)) {
            list($logic, $class, $action) = explode('.', $this->method, 3);

            $method = 'app\logic\\' . $this->module . '\\';
            $method .= $this->openVersion ? 'v' . implode('_', $this->version) . '\\' : '';
            $method .= $logic . '\\' . ucfirst($class);

            // 校验类是否存在
            if (!class_exists($method)) {
                $this->debugLog['method not found'] = $method;
                $this->error('非法参数', 20002);
            }

            // 校验类方法是否存在
            if (!method_exists($method, $action)) {
                $this->debugLog['action not found'] = $method . '->' . $action . '();';
                $this->error('非法参数', 20002);
            }

            // 加载语言包
            $lang = app()->getAppPath() . 'lang' . DIRECTORY_SEPARATOR . $this->module . DIRECTORY_SEPARATOR;
            $lang .= $this->openVersion ? 'v' . implode('_', $this->version) . DIRECTORY_SEPARATOR : '';
            $lang .= Lang::getLangSet() . '.php';
            Lang::load($lang);

            return [
                'class'  => $method,
                'action' => $action
            ];
        } else {
            $this->error('非法参数', 20002);
        }
    }

    /**
     * 校验请求时间
     * @access protected
     * @param
     * @return $this
     */
    protected function checkTimestamp()
    {
        $this->timestamp = (int)Request::param('timestamp/f', Request::time());
        if (!$this->timestamp || date('ymd', $this->timestamp) !== date('ymd')) {
            $this->error('请求超时', 20000);
        }

        return $this;
    }

    /**
     * 校验签名类型与签名合法性
     * @access protected
     * @param
     * @return $this
     */
    protected function checkSign()
    {
        // 校验签名类型
        $this->signType = Request::param('sign_type', 'md5');
        if ($this->signType && function_exists($this->signType)) {
            // 校验签名合法性
            $this->sign = Request::param('sign');
            if ($this->sign && preg_match('/^[A-Za-z0-9]+$/u', $this->sign)) {
                $params = Request::param('', '', 'trim');
                ksort($params);

                $str = '';
                $c_f = ['appid', 'sign_type', 'timestamp', 'method'];
                foreach ($params as $key => $value) {
                    if (is_string($value) && !is_null($value) && in_array($key, $c_f)) {
                        $str .= $key . '=' . $value . '&';
                    }
                }
                $str = rtrim($str, '&');
                $str .= $this->appsecret;

                if (!hash_equals(call_user_func($this->signType, $str), $this->sign)) {
                    $this->debugLog['sign_str'] = $str;
                    $this->debugLog['sign'] = call_user_func($this->signType, $str);
                    Log::record('[Async] params-sign error', 'alert')->save();
                    $this->error('授权权限不足', 20001);
                }
            } else {
                $this->debugLog['sign'] = $this->sign;
                Log::record('[Async] params-sign error', 'alert')->save();
                $this->error('非法参数', 20002);
            }
        } else {
            $this->debugLog['sign_type'] = $this->signType;
            Log::record('[Async] params-sign_type error', 'alert')->save();
            $this->error('非法参数', 20002);
        }

        return $this;
    }

    /**
     * 校验APPID
     * @access protected
     * @param
     * @return $this
     */
    protected function checkAppId()
    {
        $this->appid = (int)Request::param('appid/f', 1000001);
        $this->appid -= 1000000;
        if ($this->appid && $this->appid >= 1) {
            $result = (new ModelApiApp)
                ->field('secret, module')
                ->where([
                    ['id', '=', $this->appid]
                ])
                ->find();

            if ($result) {
                $result = $result->toArray();
                $this->appsecret = $result['secret'];
                $this->module    = $result['module'];
            } else {
                Log::record('[Async] auth-appid error', 'alert')->save();
                $this->error('权限不足', 20001);
            }
        } else {
            Log::record('[Async] auth-appid not', 'alert')->save();
            $this->error('非法参数', 20002);
        }

        return $this;
    }

    /**
     * 解析header信息
     * @access private
     * @param
     * @return $this
     */
    private function analysisHeader()
    {
        $this->authorization = Request::header('authorization');
        if ($this->authorization && $this->authorization = (new Jwt)->verify($this->authorization)) {
            if (!empty($this->authorization['jti'])) {
                Session::setId($this->authorization['jti']);
            }
        } else {
            $this->debugLog['authorization'] = $this->authorization;
            Log::record('[Async] header-authorization params error', 'alert')->save();
            $this->error('权限不足', 20001);
        }

        // 解析版本号与返回数据类型
        $this->accept = Request::header('accept');
        if ($this->accept && preg_match('/^application\/vnd\.[A-Za-z0-9]+\.v[0-9]{1,3}\.[0-9]{1,3}\.[0-9]+\+[A-Za-z]{3,5}+$/u', $this->accept)) {
            // 过滤多余信息
            $accept = str_replace('application/vnd.', '', $this->accept);

            // 校验域名合法性
            list($domain, $accept) = explode('.', $accept, 2);
            list($root) = explode('.', Request::rootDomain(), 2);
            if (!hash_equals($domain, $root)) {
                Log::record('[Async] header-accept domain error', 'alert')->save();
                $this->error('权限不足', 20001);
            }
            unset($doamin, $root);

            // 取得版本与数据类型
            list($version, $this->format) = explode('+', $accept, 2);
            if ($version && preg_match('/^[v0-9.]+$/u', $version)) {
                $version = substr($version, 1);
                list($major, $minor) = explode('.', $version, 3);
                $this->version = [
                    'major' => $major,
                    'minor' => $minor
                ];
                unset($version, $major, $minor);
            } else {
                $this->debugLog['version'] = $version;
                Log::record('[Async] header-accept version error', 'alert')->save();
                $this->error('非法参数', 20002);
            }
            // 校验返回数据类型
            if (!in_array($this->format, ['json', 'jsonp', 'xml'])) {
                $this->debugLog['format'] = $this->format;
                Log::record('[Async] header-accept format error', 'alert')->save();
                $this->error('非法参数', 20002);
            }

            unset($accept);
        } else {
            $this->debugLog['accept'] = $this->accept;
            Log::record('[Async] header-accept error', 'alert')->save();
            $this->error('非法参数', 20002);
        }

        return $this;
    }

    /**
     * 操作成功返回的数据
     * @access protected
     * @param  string  $msg  提示信息
     * @param  array   $data 要返回的数据
     * @param  integer $code 错误码，默认为SUCCESS
     * @return void
     */
    protected function success(string $_msg, array $_data = [], int $_code = 10000): void
    {
        $response = $this->result($_msg, $_data, $_code);
        throw new HttpResponseException($response);
    }
    /**
     * 操作失败返回的数据
     * 10000 成功
     * 20000 服务不可用
     * 20001 授权权限不足
     * 20002 非法参数
     * 40001 缺少参数
     * 40002 非法参数
     * 40006 权限不足
     * @access protected
     * @param  string  $msg  提示信息
     * @param  integer $code 错误码，默认为40001
     * @return void
     */
    protected function error(string $_msg, int $_code = 40001): void
    {
        $response = $this->result($_msg, [], $_code);
        throw new HttpResponseException($response);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param  string $msg    提示信息
     * @param  array  $data   要返回的数据
     * @param  string $code   错误码
     * @return Response
     */
    protected function result(string $_msg, array $_data = [], int $_code = 10000): Response
    {
        $result = [
            'code'    => $_code,
            'data'    => $_data,
            'message' => $_msg,
            'expire'  => Request::ip() . ';' . date('Y-m-d H:i:s') . ';'
        ];
        $result = array_filter($result);

        if (Request::isGet() && true === $this->cache && $this->expire && 10000 === $_code) {
            $result['expire'] .= $this->expire . 's';
        } else {
            $result['expire'] .= 'close';
        }

        // 记录日志
        if (true === $this->debug) {
            $result['debug'] = $this->writeLog();
        }

        $response = Response::create($result, $this->format)->allowCache(false);
        if (Request::isGet() && true === $this->cache && $this->expire && 10000 === $_code) {
            $response->allowCache(true)
                ->cacheControl('public, max-age=' . $this->expire)
                ->expires(gmdate('D, d M Y H:i:s', time() + $this->expire) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
                ->header(['X-Powered-By' => 'NIAPI']);
        }

        return $response;
    }

    /**
     * 调试日志
     * @access private
     * @param
     * @return string
     */
    private function writeLog(): string
    {
        $log = '[API] METHOD:' . Request::param('method', 'NULL') .
            ' TIME:' . number_format(microtime(true) - Container::pull('app')->getBeginTime(), 2) . 's' .
            ' MEMORY:' . number_format((memory_get_usage() - Container::pull('app')->getBeginMem()) / 1024 / 1024, 2) . 'MB' .
            ' CACHE:' . Container::pull('cache')->getReadTimes() . 'reads,' . Container::pull('cache')->getWriteTimes() . 'writes';

        $log .= PHP_EOL . 'PARAM:' . json_encode(Request::param('', '', 'trim'), JSON_UNESCAPED_UNICODE);
        $log .= PHP_EOL . 'DEBUG:' . json_encode($this->debugLog, JSON_UNESCAPED_UNICODE);
        // $log .= PHP_EOL . 'RESULT:' . json_encode($_result, JSON_UNESCAPED_UNICODE);

        Log::record($log, 'alert')->save();

        return $log;
    }
}
