<?php

/**
 *
 * 异步请求实现
 *
 * @package   NICMS
 * @category  app\common\controller
 * @author    失眠小枕头 [levisun.mail@gmail.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */

declare(strict_types=1);

namespace app\common\controller;

use think\App;
use think\Response;
use think\exception\HttpResponseException;
use app\common\library\Rbac;
use app\common\library\Base64;
use app\common\model\ApiApp as ModelApiApp;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Hmac\Sha256;

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
        'minor'    => '0',
        'revision' => '',
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
     * @var string
     */
    protected $authorization;

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
     * APPID
     * @var int
     */
    protected $appId;

    /**
     * APP密钥
     * @var string
     */
    protected $appSecret;

    /**
     * 应用名
     * @var string
     */
    protected $appName = '';

    /**
     * 应用方法
     * @var array
     */
    protected $appMethod = [
        'logic'  => null,
        'method' => null,
        'action' => null,
        'class'  => null
    ];

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
     * 返回表单令牌
     * @var bool
     */
    protected $apiFromToken = false;

    /**
     * 请求时间戳
     * @var int
     */
    protected $timestamp;

    /**
     * API方法
     * @var string
     */
    protected $method;

    /**
     * 权限认证KEY
     * @var string
     */
    protected $appAuthKey = 'user_auth_key';

    /**
     * 用户ID
     * @var int
     */
    protected $uid = 0;

    /**
     * 用户组ID
     * @var int
     */
    protected $urole = 0;

    /**
     * logic层返回数据
     * @var array
     */
    protected $result = [];

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
     * 构造方法
     * @access public
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
        $this->session  = $this->app->session;

        // 请勿开启调试模式
        $this->app->debug(false);
        // 设置请求默认过滤方法
        $this->request->filter('\app\common\library\DataFilter::filter');
        // 请勿更改参数
        @ini_set('memory_limit', '8M');
        set_time_limit(10);
    }

    /**
     * 运行
     * @access protected
     * @return $this
     */
    protected function run()
    {
        // 验证表单令牌
        $this->checkFromToken();
        // 解析method参数
        $this->analysisMethod();
        // 验证method权限
        $this->checkAuth();
        // 加载语言包
        $this->loadLang();

        // 执行METHOD
        $this->result = call_user_func([
            $this->app->make($this->appMethod['class']),
            $this->appMethod['method']
        ]);

        // 校验返回数据
        if (!is_array($this->result) && array_key_exists('msg', $this->result)) {
            $this->abort('返回数据格式错误', 30001);
        }

        // 调试模式设置
        // 返回数据没有指定默认关闭
        $this->debug(isset($this->result['debug']) ? $this->result['debug'] : false);
        // 缓存设置
        // 返回数据没有指定默认开启
        $this->cache(isset($this->result['cache']) ? $this->result['cache'] : true);

        $this->result['data'] = isset($this->result['data']) ? $this->result['data'] : [];
        $this->result['code'] = isset($this->result['code']) ? $this->result['code'] : 10000;

        return $this;
    }

    /**
     * 开启调试
     * @access protected
     * @param  bool $_debug 开启或关闭调试模式
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
     * @param  bool $_cache
     * @return $this
     */
    protected function cache(bool $_cache)
    {
        $this->apiCache = (true === $this->apiDebug) ? false : $_cache;
        return $this;
    }

    /**
     * 加载语言包
     * @access protected
     * @return void
     */
    protected function loadLang(): void
    {
        // 加载语言包
        $common_lang  = $this->app->getBasePath() . 'common' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
        $common_lang .= $this->lang->getLangSet() . '.php';
        $lang  = $this->app->getBasePath() . $this->appName . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
        $lang .= $this->lang->getLangSet() . '.php';
        $this->lang->load([$common_lang, $lang]);
    }

    /**
     * 验证请求来源
     * @access protected
     * @param  bool $_strict 严格
     * @return bool
     */
    protected function isReferer(bool $_strict = true): bool
    {
        // 验证请求来源
        $refere = $this->request->server('HTTP_REFERER');
        if (!$refere || false === stripos($refere, $this->request->rootDomain())) {
            $this->abort('错误请求', 27001);
        }
        unset($refere);

        // 验证请求参数
        $max_input_vars = (int) ini_get('max_input_vars');
        if (count($_POST) + count($_FILES) + count($_GET) >= $max_input_vars - 5) {
            $this->abort('错误请求', 27002);
        }
        unset($max_input_vars);

        // 检查客户端token
        // token由\app\common\controller\BaseController::class签发
        if (true === $_strict && !$this->session->has('client_token')) {
            $this->abort('错误请求', 27003);
        }

        return true;
    }

    /**
     * 请求合法验证
     * @access protected
     * @return $this
     */
    protected function analysis()
    {
        // 解析header数据
        $this->analysisHeader();
        // 验证APPID
        $this->checkAppId();
        // 验证签名
        $this->checkSign();
        // 验证时间戳
        $this->checkTimestamp();

        return $this;
    }

    /**
     * 验证权限
     * @access private
     * @return void
     */
    private function checkAuth(): void
    {
        // 需要鉴权应用
        if (in_array($this->appName, ['admin', 'my'])) {
            // 不需要鉴权方法
            // 登录 登出 找回密码
            if (in_array($this->appMethod['method'], ['login', 'logout', 'forget'])) {
                return;
            }

            // 设置会话信息(用户ID,用户组)
            if ($this->session->has($this->appAuthKey) && $this->session->has($this->appAuthKey . '_role')) {
                $this->uid = (int) $this->session->get($this->appAuthKey);
                $this->urole = (int) $this->session->get($this->appAuthKey . '_role');
            }

            // 验证权限
            $result = (new Rbac)->authenticate(
                $this->uid,
                $this->appName,
                $this->appMethod['logic'],
                $this->appMethod['action'],
                $this->appMethod['method'],
                $this->notAuth
            );
            if (false === $result) {
                $this->abort('错误请求', 26001);
            }
        }
    }

    /**
     * 解析method参数
     * @access private
     * @return void
     */
    private function analysisMethod(): void
    {
        // 校验API方法
        $this->method = $this->request->param('method');
        if (!$this->method || !preg_match('/^[a-z]+\.[a-z]+\.[a-z]+$/u', $this->method)) {
            $this->abort('错误请求', 25001);
        }

        list($logic, $action, $method) = explode('.', $this->method, 3);

        $class  = '\app\\' . $this->appName . '\logic\\';
        $class .= $this->openVersion ? 'v' . implode('_', $this->version) . '\\' : '';
        $class .= $logic . '\\' . ucfirst($action);

        // 校验类是否存在
        if (!class_exists($class)) {
            $this->debugLog['method not found'] = $class;
            $this->log->record('[Async] method not found ' . $class, 'error');
            $this->abort('错误请求', 25002);
        }
        // 校验类方法是否存在
        if (!method_exists($class, $method)) {
            $this->debugLog['action not found'] = $class . '->' . $method . '();';
            $this->log->record('[Async] action not found ' . $class . '->' . $method . '();', 'error');
            $this->abort('错误请求', 25003);
        }

        $this->appMethod = [
            'logic'  => $logic,
            'action' => $action,
            'method' => $method,
            'class'  => $class,
        ];
    }

    /**
     * 验证表单令牌
     * @access private
     * @return void
     */
    private function checkFromToken(): void
    {
        // POST请求 表单令牌验证
        if ($this->request->isPost()) {
            $this->apiFromToken = true;
            if (false === $this->request->checkToken()) {
                $this->abort('错误请求', 24001);
            }
        }
    }

    /**
     * 验证请求时间
     * @access private
     * @return void
     */
    private function checkTimestamp(): void
    {
        $this->timestamp = $this->request->param('timestamp/d', $this->request->time());
        if (!$this->timestamp || date('ymd', $this->timestamp) !== date('ymd')) {
            $this->abort('错误请求', 23001);
        }
    }

    /**
     * 验证签名类型与签名合法性
     * @access private
     * @return void
     */
    private function checkSign(): void
    {
        // 校验签名类型
        $this->signType = $this->request->param('sign_type', 'md5');
        if (!$this->signType || !function_exists($this->signType)) {
            $this->debugLog['sign_type'] = $this->signType;
            $this->log->record('[Async] params-sign_type error', 'error');
            $this->abort('错误请求', 22001);
        }

        // 校验签名合法性
        $this->sign = $this->request->param('sign');
        if (!$this->sign || !preg_match('/^[A-Za-z0-9]+$/u', $this->sign)) {
            $this->debugLog['sign'] = $this->sign;
            $this->log->record('[Async] params-sign error', 'error');
            $this->abort('错误请求', 22002);
        }



        $params = $this->request->param('', '', 'trim');
        $params = array_merge($params, $_FILES);
        ksort($params);

        $str = '';
        $c_f = ['appid', 'sign_type', 'timestamp', 'method'];
        foreach ($params as $key => $value) {
            if (in_array($key, ['sign', 'sign_type'])) {
                continue;
            }
            if (is_array($value)) {
                continue;
                // $str .= $key . '=Array&';
            } elseif (is_numeric($value) || $value) {
                $str .= $key . '=' . $value . '&';
            }
        }
        $str = rtrim($str, '&');
        $str .= $this->appSecret;

        if (!hash_equals(call_user_func($this->signType, $str), $this->sign)) {
            $this->debugLog['sign_str'] = $str;
            $this->debugLog['sign'] = call_user_func($this->signType, $str);
            $this->log->record('[Async] params-sign error:' . $str, 'error');
            $this->abort('错误请求', 22003);
        }
    }

    /**
     * 验证APPID
     * @access private
     * @return void
     */
    private function checkAppId(): void
    {
        $this->appId = $this->request->param('appid/f', 1000001);
        if (!$this->appId || $this->appId < 1000001) {
            $this->log->record('[Async] auth-appid not', 'error');
            $this->abort('错误请求', 21001);
        }

        $this->appId -= 1000000;
        $result = (new ModelApiApp)
            ->field('name, secret, authkey')
            ->where([
                ['id', '=', $this->appId]
            ])
            ->cache('APPID' . $this->appId)
            ->find();

        if (null !== $result && $result = $result->toArray()) {
            $this->appName = $result['name'];
            $this->appSecret = $result['secret'];
            $this->appAuthKey = $result['authkey'];
        } else {
            $this->log->record('[Async] auth-appid error', 'error');
            $this->abort('错误请求', 21002);
        }
    }

    /**
     * 解析header信息
     * JWT校验
     * Session初始化
     * version与format解析
     * @access private
     * @return void
     */
    private function analysisHeader(): void
    {
        $this->authorization = $this->request->header('authorization');
        $this->authorization = str_replace('&#43;', '+', $this->authorization);
        $this->authorization = str_replace('Bearer ', '', $this->authorization);
        if (!$this->authorization) {
            $this->log->record('[Async] header-authorization params error', 'error');
            $this->abort('错误请求', 20001);
        }

        $token = (new Parser)->parse($this->authorization);

        $key  = $this->request->ip();
        $key .= $key . $this->request->rootDomain();
        $key .= $key . $this->request->server('HTTP_USER_AGENT');
        $key = md5(Base64::encrypt($key));

        $data = new ValidationData;
        $data->setIssuer($this->request->rootDomain());
        $data->setAudience(parse_url($this->request->server('HTTP_REFERER'), PHP_URL_HOST));
        $data->setId($token->getClaim('jti'));
        $data->setCurrentTime(time() + 60);

        if (false === $token->verify(new Sha256, $key) || false === $token->validate($data)) {
            $this->log->record('[Async] header-authorization params error', 'error');
            $this->abort('错误请求', 20002);
        }



        // Session初始化
        // 规定sessionID
        $jti = Base64::decrypt($token->getClaim('jti'));
        if ($jti && is_file($this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'session' . DIRECTORY_SEPARATOR . 'sess_' . $jti)) {
            $this->session->setId($jti);
            $this->session->init();
            $this->request->withSession($this->session);
        } else {
            $this->log->record('[Async] header-authorization params error', 'error');
            $this->abort('错误请求', 20003);
        }



        // 解析版本号与返回数据类型
        $this->accept = $this->request->header('accept');
        $pattern = '/^application\/vnd\.[A-Za-z0-9]+\.v[0-9]{1,3}\.[0-9]{1,3}\.[a-zA-Z0-9]+\+[A-Za-z]{3,5}+$/u';
        if (!$this->accept || !preg_match($pattern, $this->accept)) {
            $this->debugLog['accept'] = $this->accept;
            $this->log->record('[Async] header-accept error', 'error');
            $this->abort('错误请求', 20004);
        }



        // 过滤多余信息
        // application/vnd.nicms.v1.0.1+json
        $accept = str_replace('application/vnd.', '', $this->accept);
        // 校验域名合法性
        list($domain, $accept) = explode('.', $accept, 2);
        list($root) = explode('.', $this->request->rootDomain(), 2);
        if (!hash_equals($domain, $root)) {
            $this->log->record('[Async] header-accept domain error', 'error');
            $this->abort('错误请求', 20005);
        }
        unset($doamin, $root);



        // 取得版本与数据类型
        list($version, $this->format) = explode('+', $accept, 2);
        if (!$version || !preg_match('/^[a-zA-Z0-9.]+$/u', $version)) {
            $this->debugLog['version'] = $version;
            $this->log->record('[Async] header-accept version error', 'error');
            $this->abort('错误请求', 20006);
        }
        // 去掉"v"
        $version = substr($version, 1);
        list($major, $minor, $revision) = explode('.', $version, 3);
        $this->version = [
            'major'    => $major,
            'minor'    => $minor,
            'revision' => $revision,
        ];
        unset($version, $major, $minor, $revision);



        // 校验返回数据类型
        if (!in_array($this->format, ['json', 'jsonp', 'xml'])) {
            $this->debugLog['format'] = $this->format;
            $this->log->record('[Async] header-accept format error', 'error');
            $this->abort('错误请求', 20007);
        }
    }

    /**
     * 操作成功
     * @access protected
     * @param  string  $msg  提示信息
     * @param  array   $data 要返回的数据
     * @param  integer $code 错误码，默认为10000
     * @return void
     */
    protected function success(string $_msg, array $_data = [], int $_code = 10000): Response
    {
        return $this->response($_msg, $_data, $_code);
    }

    /**
     * 操作失败
     * 10000 成功
     * 200xx 权限|授权|参数等错误
     * 3000x 请求类型等错误
     * 40001 缺少参数
     * 40002 错误请求
     * @access protected
     * @param  string  $msg  提示信息
     * @param  integer $code 错误码，默认为40001
     * @return void
     */
    protected function error(string $_msg, int $_code = 40001): Response
    {
        return $this->response($_msg, [], $_code);
    }

    /**
     * 抛出异常
     * @access protected
     * @param  string  $msg  提示信息
     * @param  integer $code 错误码，默认为40001
     * @return void
     */
    protected function abort(string $_msg, int $_code = 40001): void
    {
        if ($this->request->isAjax() || $this->request->isPjax()) {
            $response = $this->response($_msg, [], $_code);
        } else {
            $response = miss(404);
        }

        throw new HttpResponseException($response);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param  string $msg    提示信息
     * @param  array  $data   要返回的数据
     * @param  string $code   返回码
     * @return Response
     */
    protected function response(string $_msg, array $_data = [], int $_code = 10000): Response
    {
        $result = [
            'code'    => $_code,
            'data'    => $_data,
            'message' => true === $this->apiDebug ? $_msg : (10000 === $_code ? 'success' : $_msg),
            'runtime' => number_format(microtime(true) - $this->app->getBeginTime(), 3) . 's,' .
                number_format((memory_get_usage() - $this->app->getBeginMem()) / 1048576, 3) . 'mb,' .
                count(get_included_files()),

            // 返回地址
            'return_url' => $this->session->has('return_url')
                ? $this->session->pull('return_url')
                : '',

            // 表单令牌
            'token' => $this->apiFromToken
                ? $this->request->buildToken('__token__', 'md5')
                : '',

            // 调试数据
            'debug' => true === $this->apiDebug ? [
                'query'   => $this->app->db->getQueryTimes(),
                'cache'   => $this->app->cache->getReadTimes(),
                'log'     => $this->debugLog,
                'method'  => $this->method,
                'version' => implode('.', $this->version),
            ] : '',
        ];
        $result = array_filter($result);

        $response = Response::create($result, $this->format)->allowCache(false);
        $response->header(array_merge(['X-Powered-By' => 'NIAPI'], $response->getHeader()));
        if ($this->request->isGet() && true === $this->apiCache && 10000 === $_code) {
            $time = time() + $this->apiExpire;
            $response->allowCache(true)
                ->cacheControl('max-age=' . $this->apiExpire . ',must-revalidate')
                ->expires(gmdate('D, d M Y H:i:s', $time) . ' GMT')
                ->lastModified(gmdate('D, d M Y H:i:s', $time) . ' GMT')
                ->eTag(md5($this->method ?: $this->request->ip()));
        }

        $this->log->save();

        if ($this->session) {
            $this->session->save();
        }

        return $response;
    }
}
