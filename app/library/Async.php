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
use app\library\Ip;
use app\library\Base64;
use app\model\ApiApp as ModelApiApp;

class Async
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
    protected $debug = true;

    /**
     * 浏览器数据缓存开关
     * @var bool
     */
    protected $cache = true;

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
    public function __construct()
    {
        ini_set('display_errors', 'Off');
        ini_set('memory_limit', '16M');
        set_time_limit(15);
        header('X-Powered-By: NIAPI');

        $max_input_vars = (int)ini_get('max_input_vars');
        if (count($_POST) + count($_FILES) >= $max_input_vars - 5) {
            $this->error('[Async] request max params number error');
        }
    }

    /**
     * 运行
     * @access protected
     * @param
     * @return Response
     */
    protected function run()
    {
        $exec =
            $this->analysisHeader()
            ->checkAppId()
            ->checkSign()
            ->checkTimestamp()
            ->analysisMethod();

        // 执行类方法
        $result = call_user_func_array([(new $exec['class']), $exec['action']], []);

        if (!is_array($result) && empty($result['msg'])) {
            $this->error('[Async] return data error');
        }

        // 调试与缓存设置
        // 调试模式 返回数据没有指定默认关闭
        $this->debug = isset($result['debug']) ? !!$result['debug'] : false;

        // 浏览器缓存 返回数据没有指定默认开启
        $this->cache = isset($result['cache']) ? !!$result['cache'] : true;
        // 当调试模式开启时关闭缓存
        $this->cache = $this->debug ? false : $this->cache;

        // 浏览器缓存时间
        $this->expire = isset($result['expire']) ? $result['expire'] : Config::get('cache.expire');
        $this->expire = (int)$this->expire;
        $this->expire = $this->expire <= 0 ? 0 : $this->expire;

        $this->success($result['msg'], isset($result['data']) ? $result['data'] : []);
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
        if ($this->method && preg_match('/^[a-z.]+$/u', $this->method)) {
            list($logic, $class, $action) = explode('.', $this->method, 3);

            $method = 'app\logic\\' . $this->module . '\\';
            $method .= $this->openVersion ? 'v' . implode('_', $this->version) . '\\' : '';
            $method .= $logic . '\\' . ucfirst($class);

            // 校验类是否存在
            if (!class_exists($method)) {
                $this->debugLog['method not found'] = $method;
                $this->error('[Async] method not found');
            }

            // 校验类方法是否存在
            if (!method_exists($method, $action)) {
                $this->error('[Async] ' . $action . ' does not have a method');
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
            $this->error('[Async] params-method error');
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
        $this->timestamp = (int)Request::param('timestamp/f', time());
        if (!$this->timestamp || date('ymd', $this->timestamp) !== date('ymd')) {
            $this->error('[Async] request timeout');
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
                    $this->error('[Async] params-sign check error');
                }
            } else {
                $this->debugLog['sign'] = $this->sign;
                $this->error('[Async] params-sign error');
            }
        } else {
            $this->debugLog['sign_type'] = $this->signType;
            $this->error('[Async] params-sign_type error');
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
                ->where([
                    ['id', '=', $this->appid]
                ])
                ->find();

            if ($result) {
                $result = $result->toArray();
                $this->appsecret = $result['secret'];
                $this->module    = $result['module'];
            } else {
                $this->error('[Async] auth-appid error');
            }
        } else {
            $this->error('[Async] auth-appid error');
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
        // 解析token令牌和session_id
        $this->authorization = Request::header('authorization');
        if ($this->authorization && preg_match('/^[A-Za-z0-9]+$/u', $this->authorization)) {
            $this->authorization = Base64::decrypt($this->authorization, 'authorization');

            // 单token值
            if (false === strpos($this->authorization, '.')) {
                $this->token = $this->authorization;
                $this->debugLog['token'] = $this->token;
            }

            // token和session_id
            else {
                list($this->token, $this->sid) = explode('.', $this->authorization);
                $this->debugLog['token'] = $this->token;
                $this->debugLog['sid'] = $this->sid;
            }

            // 校验token合法性
            $referer = Request::header('USER-AGENT') . Request::ip() .
                app()->getRootPath() . strtotime(date('Ymd'));
            $referer = hash_hmac('sha1', $referer, Config::get('app.authkey'));
            if (!hash_equals($referer, $this->token)) {
                $this->debugLog['referer'] = $referer;
                $this->debugLog['this::token'] = $this->token;
                $this->error('[Async] header-authorization token error');
            }

            // 开启session
            if ($this->sid && preg_match('/^[A-Za-z0-9]{32,40}$/u', $this->sid)) {
                Session::setId($this->sid);
            }
        } else {
            $this->debugLog['authorization'] = $this->authorization;
            $this->error('[Async] header-authorization error');
        }


        // 解析版本号与返回数据类型
        $this->accept = Request::header('accept');
        if ($this->accept && preg_match('/^[A-Za-z0-9.\/\+]+$/u', $this->accept)) {
            // 过滤多余信息
            $accept = str_replace('application/vnd.', '', $this->accept);

            // 校验域名合法性
            list($domain, $accept) = explode('.', $accept, 2);
            list($root) = explode('.', Request::rootDomain(), 2);
            if (!hash_equals($domain, $root)) {
                $this->error('[Async] header-accept domain error');
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
                $this->error('[Async] header-accept version error');
            }
            // 校验返回数据类型
            if (!in_array($this->format, ['json', 'jsonp', 'xml'])) {
                $this->debugLog['format'] = $this->format;
                $this->error('[Async] header-accept format error');
            }

            unset($accept);
        } else {
            $this->debugLog['accept'] = $this->accept;
            $this->error('[Async] header-accept error');
        }

        return $this;
    }

    /**
     * 操作成功返回的数据
     * @access protected
     * @param  string  $msg  提示信息
     * @param  mixed   $data 要返回的数据
     * @param  integer $code 错误码，默认为SUCCESS
     * @return void
     */
    protected function success(string $_msg, $_data = [], string $_code = 'SUCCESS'): void
    {
        $response = $this->result($_msg, $_data, $_code);
        throw new HttpResponseException($response);
    }
    /**
     * 操作失败返回的数据
     * @access protected
     * @param  string  $msg  提示信息
     * @param  integer $code 错误码，默认为ERROR
     * @return void
     */
    protected function error(string $_msg, string $_code = 'ERROR'): void
    {
        $response = $this->result($_msg, [], $_code);
        throw new HttpResponseException($response);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param  mixed  $msg    提示信息
     * @param  mixed  $data   要返回的数据
     * @param  int    $code   错误码，默认为0
     * @return Response
     */
    protected function result(string $_msg, $_data = [], string $_code = 'SUCCESS'): Response
    {
        $result = [
            'code'    => $_code,
            'data'    => $_data,
            'message' => $_msg
        ];
        $result = array_filter($result);

        $ipinfo = (new Ip)->info();
        $result['expire']  = $ipinfo['ip'] . ' ' . date('YmdHis', time() + $this->expire + 60);

        // 记录日志
        if (true === $this->debug) {
            $this->writeLog($result);
        }

        $response = Response::create($result, $this->format);

        if (false === Config::get('app.app_debug') && true === $this->cache && $this->expire && $_code == 'SUCCESS') {
            $response
                ->allowCache(true)
                ->cacheControl('public, max-age=' . $this->expire)
                ->expires(gmdate('D, d M Y H:i:s', time() + $this->expire) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT');
        }

        return $response;
    }

    /**
     * 调试日志
     * @access private
     * @param
     * @return void
     */
    private function writeLog(array $_result = []): void
    {
        $log = '[API] METHOD:' . $this->method .
            ' TIME:' . number_format(microtime(true) - Container::pull('app')->getBeginTime(), 2) . 's' .
            ' MEMORY:' . number_format((memory_get_usage() - Container::pull('app')->getBeginMem()) / 1024 / 1024, 2) . 'MB' .
            ' CACHE:' . Container::pull('cache')->getReadTimes() . 'reads,' . Container::pull('cache')->getWriteTimes() . 'writes';

        $log .= PHP_EOL . 'PARAM:' . json_encode(Request::param('', '', 'trim'), JSON_UNESCAPED_UNICODE);
        $log .= PHP_EOL . 'DEBUG:' . json_encode($this->debugLog, JSON_UNESCAPED_UNICODE);
        $log .= PHP_EOL . 'RESULT:' . json_encode($_result, JSON_UNESCAPED_UNICODE);

        Log::record($log, 'alert')->save();
    }
}
