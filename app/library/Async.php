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

use think\App;
use think\Container;
use think\exception\HttpResponseException;
use app\library\Ip;
use app\library\Jwt;
use app\model\ApiApp as ModelApiApp;

abstract class Async
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * Cache实例
     * @var \think\Cache
     */
    protected $cache;

    /**
     * Config实例
     * @var \think\Config
     */
    protected $config;

    /**
     * Lang实例
     * @var \think\Lang
     */
    protected $lang;

    /**
     * log实例
     * @var \think\Log
     */
    protected $log;

    /**
     * request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * response实例
     * @var \think\Response
     */
    protected $response;

    /**
     * session实例
     * @var \think\Session
     */
    protected $session;

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
        // 'paradigm' => '1',
        'major'    => '1',
        'minor'    => '0'
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
    protected $apiDebug = false;

    /**
     * 浏览器数据缓存开关
     * @var bool
     */
    protected $apiCache = false;

    /**
     * 浏览器数据缓存时间
     * @var int
     */
    protected $apiExpire = 1440;


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
     * 请求
     * @var bool
     */
    protected $referer = false;

    /**
     * IP信息
     * @var array
     */
    protected $ipinfo = [];


    /**
     * 构造方法
     * @access public
     * @param
     * @return void
     */
    public function __construct(App $_app)
    {
        $this->app      = $_app;
        $this->cache    = $this->app->cache;
        $this->config   = $this->app->config;
        $this->lang     = $this->app->lang;
        $this->log      = $this->app->log;
        $this->request  = $this->app->request;
        $this->response = $this->app->response;
        $this->session  = $this->app->session;

        $this->app->debug($this->config->get('app.debug'));
        $this->request->filter('defalut_filter');

        $max_input_vars = (int)ini_get('max_input_vars');
        if (count($_POST) + count($_FILES) + count($_GET) >= $max_input_vars - 5) {
            $this->error('非法参数', 40002);
        }

        $this->referer = $this->request->server('HTTP_REFERER') && $this->request->param('method');

        $this->ipinfo = Ip::info();
    }

    /**
     * 运行
     * @access protected
     * @param
     * @return array
     */
    protected function run(): array
    {
        $exec = $this->analysisMethod();

        // 执行类方法
        $class = Container::getInstance()->make($exec['class']);
        $result = call_user_func([$class, $exec['action']]);

        if (!is_array($result) && empty($result['msg'])) {
            $this->error('缺少参数', 40001);
        }

        // 调试模式设置 返回数据没有指定默认关闭
        $this->debug(isset($result['debug']) ? $result['debug'] : false);

        // 缓存设置 返回数据没有指定默认开启
        $this->cache(isset($result['cache']) ? $result['cache'] : true);

        $result['data'] = isset($result['data']) ? $result['data'] : [];
        $result['code'] = isset($result['code']) ? $result['code'] : 10000;

        return $result;
    }

    /**
     * 验证
     * @access protected
     * @param
     * @return $this
     */
    protected function validate()
    {
        $this->analysisHeader()->checkAppId()->checkSign()->checkTimestamp();
        return $this;
    }

    /**
     * 开启调试
     * @access protected
     * @param
     * @return $this
     */
    protected function debug(bool $_debug)
    {
        $this->apiDebug = $_debug;
        return $this;
    }

    /**
     * 设置缓存
     * @access protected
     * @param
     * @return $this
     */
    protected function cache(bool $_cache)
    {
        $this->apiCache = (true === $this->apiDebug) ? false : $_cache;
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
        $this->method = $this->request->param('method');
        if ($this->method && preg_match('/^[a-z]+\.[a-z]+\.[a-z]+$/u', $this->method)) {
            list($logic, $class, $action) = explode('.', $this->method, 3);

            $method  = 'app\service\\' . $this->module . '\\';
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
            $lang  = $this->app->getAppPath() . 'lang' . DIRECTORY_SEPARATOR . $this->module . DIRECTORY_SEPARATOR;
            $lang .= $this->openVersion ? 'v' . implode('_', $this->version) . DIRECTORY_SEPARATOR : '';
            $lang .= $this->lang->getLangSet() . '.php';
            $this->lang->load($lang);

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
        $this->timestamp = (int)$this->request->param('timestamp/f', $this->request->time());
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
        $this->signType = $this->request->param('sign_type', 'md5');
        if ($this->signType && function_exists($this->signType)) {
            // 校验签名合法性
            $this->sign = $this->request->param('sign');
            if ($this->sign && preg_match('/^[A-Za-z0-9]+$/u', $this->sign)) {
                $params = $this->request->param('', '', 'trim');
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
                    $this->log->record('[Async] params-sign error', 'alert')->save();
                    $this->error('授权权限不足', 20001);
                }
            } else {
                $this->debugLog['sign'] = $this->sign;
                $this->log->record('[Async] params-sign error', 'alert')->save();
                $this->error('非法参数', 20002);
            }
        } else {
            $this->debugLog['sign_type'] = $this->signType;
            $this->log->record('[Async] params-sign_type error', 'alert')->save();
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
        $this->appid  = (int)$this->request->param('appid/f', 1000001);
        $this->appid -= 1000000;
        if ($this->appid && $this->appid >= 1) {
            $result = (new ModelApiApp)
                ->field('secret, module')
                ->where([
                    ['id', '=', $this->appid]
                ])
                ->cache(__METHOD__ . $this->appid, 1440, 'library')
                ->find();

            if ($result) {
                $result = $result->toArray();
                $this->appsecret = $result['secret'];
                $this->module    = $result['module'];
            } else {
                $this->log->record('[Async] auth-appid error', 'alert')->save();
                $this->error('权限不足', 20001);
            }
        } else {
            $this->log->record('[Async] auth-appid not', 'alert')->save();
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
        $this->authorization = $this->request->header('authorization');
        if ($this->authorization && $this->authorization = (new Jwt)->verify($this->authorization)) {
            if (!empty($this->authorization['jti'])) {
                $this->session->setId($this->authorization['jti']);
            }
        } else {
            $this->log->record('[Async] header-authorization params error', 'alert')->save();
            $this->error('权限不足', 20001);
        }

        // 解析版本号与返回数据类型
        $this->accept = $this->request->header('accept');
        if ($this->accept && preg_match('/^application\/vnd\.[A-Za-z0-9]+\.v[0-9]{1,3}\.[0-9]{1,3}\.[0-9]+\+[A-Za-z]{3,5}+$/u', $this->accept)) {
            // 过滤多余信息
            $accept = str_replace('application/vnd.', '', $this->accept);

            // 校验域名合法性
            list($domain, $accept) = explode('.', $accept, 2);
            list($root) = explode('.', $this->request->rootDomain(), 2);
            if (!hash_equals($domain, $root)) {
                $this->log->record('[Async] header-accept domain error', 'alert')->save();
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
                $this->log->record('[Async] header-accept version error', 'alert')->save();
                $this->error('非法参数', 20002);
            }
            // 校验返回数据类型
            if (!in_array($this->format, ['json', 'jsonp', 'xml'])) {
                $this->debugLog['format'] = $this->format;
                $this->log->record('[Async] header-accept format error', 'alert')->save();
                $this->error('非法参数', 20002);
            }

            unset($accept);
        } else {
            $this->debugLog['accept'] = $this->accept;
            $this->log->record('[Async] header-accept error', 'alert')->save();
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
        $this->result($_msg, $_data, $_code);
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
        $this->result($_msg, [], $_code);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param  string $msg    提示信息
     * @param  array  $data   要返回的数据
     * @param  string $code   错误码
     * @return Response
     */
    protected function result(string $_msg, array $_data = [], int $_code = 10000)
    {
        $result = [
            'code'    => $_code,
            'data'    => $_data,
            'message' => $_msg,
            'expire'  => $this->ipinfo['ip'] . ';' . date('Y-m-d H:i:s') . ';',
            'token'   => $this->request->isPost() ? $this->request->buildToken('__token__', 'md5') : '',
            'debug'   => true === $this->apiDebug ? $this->debugLog : '',
        ];

        $result = array_filter($result);

        $response = $this->response->create($result, $this->format)->allowCache(false);
        if ($this->request->isGet() && true === $this->apiCache && 10000 === $_code) {
            $response->allowCache(true)
                ->cacheControl('max-age=' . $this->apiExpire . ',must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', time() + $this->apiExpire) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s') . ' GMT')
                ->header(['X-Powered-By' => 'NIAPI']);
        }
        throw new HttpResponseException($response);
    }
}
